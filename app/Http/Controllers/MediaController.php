<?php

namespace App\Http\Controllers;

use App\Interfaces\MediaInterface;
use App\Models\Media;
use Illuminate\Http\Request;

class MediaController extends Controller
{
    protected $mediaRepository;
    public function __construct(MediaInterface $mediaRepository)
    {
        $this->mediaRepository = $mediaRepository;
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Media  $post
     * @return \Illuminate\Http\Response
     */
    public function show(Media $post)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Media  $post
     * @return \Illuminate\Http\Response
     */
    public function edit(Media $post)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Media  $post
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Media $post)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Media  $post
     * @return \Illuminate\Http\Response
     */
    public function destroy(Media $post)
    {
        //
    }
}
