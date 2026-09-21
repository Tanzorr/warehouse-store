<?php

namespace App\Http\Controllers\Swagger;

class MediaController
{
    /**
     * @OA\Get(
     *     path="/api/medias",
     *     tags={"Media"},
     *     summary="Get a list of all media records",
     *     security={{"bearerAuth": {}}},
     *     operationId="getMedias",
     *
     *     @OA\Response(
     *         response=200,
     *         description="List of media records",
     *
     *         @OA\JsonContent(
     *             type="array",
     *
     *             @OA\Items(ref="#/components/schemas/Media")
     *         )
     *     )
     * )
     */
    public function index() {}

    /**
     * @OA\Post(
     *     path="/api/medias",
     *     tags={"Media"},
     *     summary="Upload a media file",
     *     security={{"bearerAuth": {}}},
     *     operationId="uploadMedia",
     *
     *     @OA\RequestBody(
     *         required=true,
     *         description="Media file to upload",
     *
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *
     *             @OA\Schema(
     *                 type="object",
     *                 required={"media"},
     *
     *                 @OA\Property(
     *                     property="media",
     *                     type="string",
     *                     format="binary",
     *                     description="The media file to upload"
     *                 )
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=201,
     *         description="Media uploaded successfully",
     *
     *         @OA\JsonContent(ref="#/components/schemas/Media")
     *     ),
     *
     *     @OA\Response(
     *         response=422,
     *         description="Validation error"
     *     )
     * )
     */
    public function store() {}

    /**
     * @OA\Get(
     *     path="/api/medias/{media}",
     *     tags={"Media"},
     *     summary="Get details of a specific media record",
     *     security={{"bearerAuth": {}}},
     *     operationId="getMedia",
     *
     *     @OA\Parameter(
     *         name="media",
     *         in="path",
     *         required=true,
     *         description="Media ID",
     *
     *         @OA\Schema(type="integer")
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Media details",
     *
     *         @OA\JsonContent(ref="#/components/schemas/Media")
     *     ),
     *
     *     @OA\Response(
     *         response=404,
     *         description="Media not found"
     *     )
     * )
     */
    public function show() {}

    /**
     * @OA\Delete(
     *     path="/api/medias/{media}",
     *     tags={"Media"},
     *     summary="Delete a media record",
     *     security={{"bearerAuth": {}}},
     *     operationId="deleteMedia",
     *
     *     @OA\Parameter(
     *         name="media",
     *         in="path",
     *         required=true,
     *         description="Media ID",
     *
     *         @OA\Schema(type="integer")
     *     ),
     *
     *     @OA\Response(
     *         response=204,
     *         description="Media record deleted successfully"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Media not found"
     *     )
     * )
     */
    public function destroy() {}
}
