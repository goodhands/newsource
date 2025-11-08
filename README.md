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
