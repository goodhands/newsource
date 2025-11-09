<?php

namespace App\Http\Controllers\Api\v1;

use App\Domain\Tags\Repositories\TagRepositoryInterface;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class TagController extends Controller
{
    public function __construct(
        protected TagRepositoryInterface $tagRepository
    )
    {
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        return $this->tagRepository->all();
    }
}
