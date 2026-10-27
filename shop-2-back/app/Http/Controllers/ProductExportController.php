<?php

namespace App\Http\Controllers;

use App\Exports\ProductsExport;
use Excel;
use PhpOffice\PhpSpreadsheet\Exception;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ProductExportController extends Controller
{

    public function export(): BinaryFileResponse
    {
        return Excel::download(new ProductsExport, 'products.xlsx');
    }
}
