<?php

namespace App\Http\Controllers\Api\v1;

use App\Domain\Articles\Models\Article;
use App\Domain\Articles\Repositories\ArticleRepositoryInterface;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ArticleController extends Controller
{
    public function __construct(
        protected ArticleRepositoryInterface $articleRepository
    )
    {
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $search = $request->query('search');
        $filter = $request->query('filter');

        return $this->articleRepository->all();
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreArticleRequest $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, $id)
    {
        $include = $request->query('include');
        $include = $include ? explode(',', $include) : [];

        $article = $this->articleRepository->getById($id, $include);

        return $article;
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Article $article)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateArticleRequest $request, Article $article)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Article $article)
    {
        //
    }
}
