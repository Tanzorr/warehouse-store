<?php

namespace App\Http\Controllers\Swagger;

/**
 * @OA\Post(
 *     path="/api/import-products",
 *     summary="Import products from a CSV file",
 *     tags={"Product Import"},
 *     security={{"bearerAuth": {}}},
 *
 *     @OA\RequestBody(
 *         required=true,
 *
 *         @OA\MediaType(
 *             mediaType="multipart/form-data",
 *
 *             @OA\Schema(
 *
 *                 @OA\Property(
 *                     property="file",
 *                     type="string",
 *                     format="binary"
 *                 )
 *             )
 *         )
 *     ),
 *
 *     @OA\Response(
 *         response=200,
 *         description="Products imported successfully"
 *     ),
 *     @OA\Response(
 *         response=400,
 *         description="Invalid file format"
 *     ),
 *     @OA\Response(
 *         response=500,
 *         description="Internal server error"
 *     )
 * )
 */
class ProductImportController
{
    public function import() {}
}
