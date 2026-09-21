<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTagRequest;
use App\Http\Requests\UpdateTagRequest;
use App\Models\Tag;
use Illuminate\Http\JsonResponse;

class TagController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): JsonResponse
    {
        $tags = Tag::all();

        return response()->json($tags);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreTagRequest $request): JsonResponse
    {
        return response()->json(Tag::create($request->validated()), 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Tag $tag): JsonResponse
    {
        return response()->json($tag);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateTagRequest $request, Tag $tag): JsonResponse
    {
        $tag->update($request->validated());

        return response()->json($tag->fresh());
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Tag $tag): JsonResponse
    {
        $tag->delete();

        return response()->json(['message' => 'Tag deleted successfully']);
    }
}
