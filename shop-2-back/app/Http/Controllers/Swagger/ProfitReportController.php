<?php

namespace App\Http\Controllers\Swagger;

class ProfitReportController
{
    /**
     * @OA\Get(
     *     path="/api/profit-report",
     *     tags={"Profit report"},
     *     security={{"bearerAuth": {}}},
     *
     *     @OA\Parameter(
     *         name="start_date",
     *         in="query",
     *         required=false,
     *
     *         @OA\Schema(
     *             type="string",
     *             example="2024-08-01"
     *         ),
     *         description="Start date for the profit report. Default: 6 months ago"
     *     ),
     *
     *     @OA\Parameter(
     *         name="end_date",
     *         in="query",
     *         required=false,
     *
     *         @OA\Schema(
     *             type="string",
     *             example="2024-12-31"
     *         ),
     *         description="End date for the profit report. Default: today"
     *     ),
     *
     *     @OA\Parameter(
     *          name="category_id",
     *          in="query",
     *          required=false,
     *
     *          @OA\Schema(
     *              type="string",
     *              example="1"
     *          ),
     *          description="Category ID for filtering. Optional"
     *      ),
     *
     *     @OA\Parameter(
     *           name="product_id",
     *           in="query",
     *           required=false,
     *
     *           @OA\Schema(
     *               type="string",
     *               example="10"
     *           ),
     *           description="Product ID for filtering. Optional"
     *       ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Profit report",
     *
     *         @OA\JsonContent(
     *             type="object",
     *
     *             @OA\Property(property="total_profit", type="integer", example=1500),
     *             @OA\Property(property="total_quantity", type="integer", example=200),
     *             @OA\Property(
     *                 property="profit_by_category",
     *                 type="array",
     *
     *                 @OA\Items(
     *
     *                     @OA\Property(property="category_name", type="string", example="Electronics"),
     *                     @OA\Property(property="category_profit", type="integer", example=500)
     *                 )
     *             )
     *         )
     *     )
     * )
     */
    public function generateReport() {}

    /**
     * @OA\Get(
     *     path="/api/annual-user-report/{userId}",
     *     tags={"Profit report"},
     *     security={{"bearerAuth": {}}},
     *
     *     @OA\Parameter(
     *         name="userId",
     *         in="path",
     *         required=true,
     *
     *         @OA\Schema(
     *             type="integer",
     *             example=1
     *         ),
     *         description="ID of the user for the annual report"
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Annual user report",
     *
     *         @OA\JsonContent(
     *             type="object",
     *
     *             @OA\Property(property="status", type="string", example="Report generated successfully")
     *         )
     *     )
     * )
     */
    public function getAnnualUsersSpend() {}
}
