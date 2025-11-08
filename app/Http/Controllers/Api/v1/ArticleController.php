<?php

namespace App\Http\Controllers\Api\v1;

use App\Domain\Articles\Repositories\ArticleRepositoryInterface;
use App\Http\Controllers\Controller;
use App\Http\Requests\IndexArticleRequest;
use Illuminate\Http\Request;

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
    public function index(IndexArticleRequest $request)
    {
        $search = $request->query('search');

        $filters = $request->query('filter', []);

        $include = $request->query('include');
        $include = $include ? explode(',', $include) : [];

        $perPage = (int) $request->query('per_page', 15);
        $perPage = min(max($perPage, 1), 100);

        $user = $request->user();

        $articles = $this->articleRepository->all($search, $filters, $include, $perPage, $user);

        return $articles;
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
}
