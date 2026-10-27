<?php

namespace App\Http\Controllers;

use App\Imports\ProductImport;
use Excel;
use Illuminate\Http\Request;

class ProductImportController extends Controller
{
    public function import(Request $request): void
    {
        Excel::import(new ProductImport, $request->file('file'));
    }
}
