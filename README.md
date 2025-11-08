API Request → Controller
→ AggregationService (orchestrator)
    → Strategy (FetchingStrategy)
        - NewsAPIStrategy
            - fetch
            - normalize
        - GuardianStrategy
            - fetch
            - normalize
        - BBCStrategy
            - fetch
            - normalize
    → Repository (ArticleRepository, SourceRepository)
        → Database (PostgreSQL)

### Scheduler: Fetch new articles
-> Cron job
    -> Run fetch command every hour
        -> Fetches active sources from the database (SourceRepository - data access layer)
            -> Run the strategy for each source
                -> Run ArticleAggregationService to store each in the database
                    -> Each dispatches events (cache clearing, indexing, updating last fetched count)

### API Layer: Serve articles
// search queries, filtering criteria (date, category, source), and user preferences (selected sources, categories, authors).
-> HTTP Request with(out) filters
    -> Controller gets the search, filters: date|category|source, pagination, user_preferences: sources: [], categories: [], authors: []
        -> Calls the ArticleRepository (data access layer) with request params
            -> Calls APIResource to return response
                -> Cache (Redis)

### Database Design
articles
    id
    title
    slug
    source_id
    media_id
    author_id
    tag_id
    published_at: timestamp // When the article was published by SOURCE
    description: text // Short summary
    status: enum('active', 'archived', 'deleted') // Soft delete tracking
    category_id
    created_at
    updated_at

article_tags:
    article_id: indexed
    tag_id: indexed

article_authors
    article_id
    author_id

article_images:
    id
    article_id
    image_url
    alt_text
    source_url // Store original too in case we proxy it
    order // For ordering in galleries

tags
    id
    name
    slug
    date

authors
    id
    firstname
    lastname
    profile
    bio
    source_id

sources
    id
    name
    url
    created_at
    updated_at

fetches
    id
    source_id
    page_fetched: int // Which page did we fetch this time?
    articles_in_batch: int // How many articles in THIS fetch
    total_pages_available: int // Total pages the API reported
    http_status: int // 200, 429 (rate limited), 500, etc.
    error_message: nullable // "Rate limited", "Connection timeout", etc.
    was_rate_limited: boolean // Easy flag to check
    retry_after_seconds: nullable // From Rate-Limit-Reset header
    created_at // When this fetch happened
    updated_at

users:
    id
    name
    email
    created_at

user_preferences:
    user_id
    source_id // null = user prefers all sources
    category_id
    author_id
    created_at
------


        Schema::create('article_tags', function (Blueprint $table) {
            $table->foreignId('article_id')->references('id')->on('articles')->onDelete('cascade');
            $table->foreignId('tag_id')->references('id')->on('tags')->onDelete('cascade');
        });

        Schema::create('article_categories', function (Blueprint $table) {
            $table->foreignId('article_id')->references('id')->on('articles')->onDelete('cascade');
            $table->foreignId('category_id')->references('id')->on('categories')->onDelete('cascade');
        });

        Schema::create('article_media', function (Blueprint $table) {
            $table->foreignId('article_id')->references('id')->on('articles')->onDelete('cascade');
            $table->foreignId('media_id')->references('id')->on('media')->onDelete('cascade');
        });

        Schema::create('article_authors', function (Blueprint $table) {
            $table->foreignId('article_id')->references('id')->on('articles')->onDelete('cascade');
            $table->foreignId('author_id')->references('id')->on('authors')->onDelete('cascade');
        });

----

# Strategy & Repository pattern breakdown
Absolutely! Let me break this down piece by piece with concrete examples.

## **1. Repository Pattern - "The Data Access Layer"**

**What it does:** It's like a middleman between your application and the database. Instead of your code directly querying the database everywhere, you ask the Repository.

**Without Repository (BAD):**
```php
// In your controller
$articles = DB::table('articles')
    ->where('source', 'bbc')
    ->where('published_at', '>', now()->subDays(7))
    ->get();
```

Problem: If you change how you query articles later, you have to update code in 10 different places.

**With Repository (GOOD):**
```php
// AuthorRepository.php
class ArticleRepository {
    public function getRecentArticlesBySource($source) {
        return Article::where('source', $source)
            ->where('published_at', '>', now()->subDays(7))
            ->get();
    }
}

// In your controller
$articles = $this->articleRepository->getRecentArticlesBySource('bbc');
```

**Benefit:** Change the query logic in ONE place (the repository), and everywhere using it automatically gets the new behavior.

---

## **2. Service Layer - "The Business Logic Coordinator"**

**What it does:** This is where the actual work happens. It takes requests, uses repositories to get data, processes it, and coordinates everything.

**Example:**
```php
// ArticleAggregationService.php
class ArticleAggregationService {
    private $articleRepository;
    private $sourceRepository;
    
    public function aggregateLatestArticles() {
        // Step 1: Get all sources we should fetch from
        $sources = $this->sourceRepository->getActiveSources();
        
        // Step 2: For each source, fetch articles
        foreach ($sources as $source) {
            $articles = $this->fetchArticlesFromSource($source);
            
            // Step 3: Save them
            $this->articleRepository->saveArticles($articles);
        }
        
        return "Done";
    }
}
```

**Why it's useful:** All the "how do we get articles?" logic is in one place, not scattered across controllers.

---

## **3. Strategy Pattern - "Different Ways to Do the Same Thing"**

**The Problem:** NewsAPI, BBC, and Guardian all have different APIs. They return data in different formats. You need different code to handle each one.

**Without Strategy (BAD):**
```php
class ArticleAggregationService {
    public function fetchArticles($source) {
        if ($source == 'newsapi') {
            // NewsAPI specific code
            $response = Http::get('https://newsapi.org/...');
            $articles = $response['articles'];
            return array_map(fn($a) => [...], $articles);
        } 
        elseif ($source == 'bbc') {
            // BBC specific code (completely different)
            $response = Http::get('https://bbc.com/...');
            $articles = json_decode($response)['items'];
            return array_map(fn($a) => [...], $articles);
        }
    }
}
```

Problem: If you add 10 sources, this method becomes 1000 lines of if/else statements. A nightmare to maintain.

**With Strategy (GOOD):**

First, create an interface (contract) that all strategies must follow:

```php
// NewsSourceFetcherStrategy.php - The Contract
interface NewsSourceFetcherStrategy {
    public function fetch(): array; // Returns articles in standard format
}
```

Now create a strategy for each source:

```php
// NewsAPIStrategy.php
class NewsAPIStrategy implements NewsSourceFetcherStrategy {
    public function fetch(): array {
        $response = Http::get('https://newsapi.org/...');
        $articles = $response['articles'];
        
        return array_map(fn($a) => [
            'title' => $a['title'],
            'content' => $a['description'],
            'source' => 'newsapi',
            'published_at' => $a['publishedAt'],
        ], $articles);
    }
}

// BBCStrategy.php
class BBCStrategy implements NewsSourceFetcherStrategy {
    public function fetch(): array {
        $response = Http::get('https://bbc.com/...');
        $articles = json_decode($response)['items'];
        
        return array_map(fn($a) => [
            'title' => $a['headline'],
            'content' => $a['summary'],
            'source' => 'bbc',
            'published_at' => $a['pubDate'],
        ], $articles);
    }
}

// GuardianStrategy.php
class GuardianStrategy implements NewsSourceFetcherStrategy {
    public function fetch(): array {
        $response = Http::get('https://guardian.com/...');
        $articles = $response['response']['results'];
        
        return array_map(fn($a) => [
            'title' => $a['webTitle'],
            'content' => $a['description'],
            'source' => 'guardian',
            'published_at' => $a['webPublicationDate'],
        ], $articles);
    }
}
```

**The key insight:** Each strategy returns data in the SAME format, even though they fetch differently. Now your service doesn't care:

```php
class ArticleAggregationService {
    public function fetchArticles(NewsSourceFetcherStrategy $strategy) {
        $articles = $strategy->fetch(); // Same method, different behavior
        $this->articleRepository->saveArticles($articles);
        return $articles;
    }
}
```

**Benefit:** Adding a new news source? Create a new strategy class. The service code never changes.

---

## **4. Factory Pattern - "Creating the Right Strategy"**

**The Problem:** How do you decide which strategy to use? You need a way to say "I want BBC articles" and automatically get the BBCStrategy.

**Solution:**

```php
// NewsSourceFetcherFactory.php
class NewsSourceFetcherFactory {
    public static function create($sourceType): NewsSourceFetcherStrategy {
        switch($sourceType) {
            case 'newsapi':
                return new NewsAPIStrategy();
            case 'bbc':
                return new BBCStrategy();
            case 'guardian':
                return new GuardianStrategy();
            default:
                throw new Exception("Unknown source: $sourceType");
        }
    }
}
```

**Usage:**
```php
$strategy = NewsSourceFetcherFactory::create('bbc');
$articles = $this->aggregationService->fetchArticles($strategy);
```

**Benefit:** Your code doesn't need to know about all the strategies. The factory handles it.

---

## **5. Observer Pattern - "React to Things Happening"**

**What it does:** When something important happens (like "an article was fetched"), notify other parts of the system to react.

**Example Scenarios:**
- When article is saved → Update search cache
- When article is saved → Send notification to users
- When article is saved → Log audit trail

**Without Observer (BAD):**
```php
class ArticleAggregationService {
    public function saveArticle($article) {
        $this->articleRepository->save($article);
        
        // Clear cache manually
        Cache::flush('articles');
        
        // Send notification manually
        Notification::send($users, new NewArticleNotification($article));
        
        // Log manually
        Log::info("Articles saved: " . $article->id);
    }
}
```

Problem: Service layer is bloated. Every side effect has to be written directly.

**With Observer (GOOD):**

First, create and dispatch an event when article is saved:

```php
class ArticleAggregationService {
    public function saveArticle($article) {
        $saved = $this->articleRepository->save($article);
        
        // That'Models it! Just dispatch an event
        event(new ArticleSaved($article));
    }
}
```

Then create listeners that react to that event:

```php
// CacheInvalidationListener.php
class CacheInvalidationListener {
    public function handle(ArticleSaved $event) {
        Cache::flush('articles');
    }
}

// SendNotificationListener.php
class SendNotificationListener {
    public function handle(ArticleSaved $event) {
        Notification::send($users, new NewArticleNotification($event->article));
    }
}

// AuditLogListener.php
class AuditLogListener {
    public function handle(ArticleSaved $event) {
        Log::info("Articles saved: " . $event->article->id);
    }
}
```

In Laravel config, register these listeners:

```php
// config/events.php
'ArticleSaved' => [
    'CacheInvalidationListener',
    'SendNotificationListener',
    'AuditLogListener',
],
```

**Benefit:** Service stays clean. Want to add a new side effect? Create a new listener. Service code never changes.

---

Does this make more sense now? Which part would you like me to dive deeper into?

### My notes
Fetcher Layer
Things we could improve:
1. Batch or Pool fetching according to laravel documentation
2. Fetch only if we were not rate limited in the last request. //        TODO Add this in the note of things we could have done.
   //        $shouldDelayFetching = $wasRateLimited && $fetch->created_at < ONE_HOUR;

3. 
