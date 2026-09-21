<?php

namespace App\Http\Controllers\Swagger;

/**
 * @OA\Get(
 *     path="/api/export-products",
 *     summary="Export products to Excel",
 *     tags={"Product Export"},
 *     security={{"bearerAuth": {}}},
 *
 *     @OA\Response(
 *         response=200,
 *         description="File successfully exported",
 *
 *         @OA\Header(
 *             header="Content-Disposition",
 *             description="attachment; filename=products.xlsx",
 *
 *             @OA\Schema(type="string")
 *         )
 *     ),
 *
 *     @OA\Response(
 *         response=500,
 *         description="Internal server error"
 *     )
 * )
 */
class ProductExportController
{
    public function export()
    {
        // Export logic here
    }
}
