<?php

namespace App\Http\Controllers;

use App\Http\Resources\ArticleCollectionResource;
use App\Http\Resources\TagCollectionResource;
use App\Http\Resources\TagResource;
use App\Interfaces\TagInterface;
use App\Models\Transaction;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Http\Request;
use Shetabit\Multipay\Invoice;
use Shetabit\Payment\Facade\Payment;

class TagController extends Controller
{
    protected $tagRepository;
    public function __construct(TagInterface $tagRepository)
    {
        $this->tagRepository = $tagRepository;
    }

    /**
     * Display a listing of the resource.
     *
     * @return TagCollectionResource
     */
    public function index()
    {
        $tags = $this->tagRepository->all();
        return new TagCollectionResource($tags);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {

    }

    /**
     * Display the specified resource.
     *
     * @param $id
     * @return TagResource
     */
    public function show($id)
    {
        $tag = $this->tagRepository->findOneOrFail($id);
        return new TagResource($tag);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Tag  $tag
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Tag $tag)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Tag  $tag
     * @return \Illuminate\Http\Response
     */
    public function destroy(Tag $tag)
    {
        //
    }

    /**
     * Display a listing of the resource.
     *
     * @param string $name
     * @return ArticleCollectionResource
     */
    public function articles($name)
    {
        /** @var Tag $tag */
        $tag = Tag::where('name', '=', $name)->first();
        $articles = array();
        if($tag) {
            /** @var Tag $tag */
            $tag = $this->tagRepository->findOneOrFail($tag->id);
            $articles = $tag->articles()->get();
        }
        return new ArticleCollectionResource($articles);
    }

}
