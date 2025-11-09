<?php

namespace App\Http\Controllers\Api\v1;

use App\Domain\Articles\Repositories\ArticleRepositoryInterface;
use App\Domain\Authors\Repositories\AuthorRepositoryInterface;
use App\Http\Controllers\Controller;
use App\Http\Requests\IndexArticleRequest;
use Illuminate\Http\Request;

class AuthorController extends Controller
{
    public function __construct(
        protected AuthorRepositoryInterface $authorRepository
    )
    {
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        return $this->authorRepository->all();
    }
}
