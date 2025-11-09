<?php

namespace App\Http\Controllers\Api\v1;

use App\Domain\Categories\Repositories\CategoryRepositoryInterface;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    public function __construct(
        protected CategoryRepositoryInterface $categoryRepository
    )
    {
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        return $this->categoryRepository->all();
    }
}
