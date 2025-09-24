<?php

namespace App\Imports;

use App\Models\Product;
use App\Models\ProductImage;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\IOFactory;

class ProductsImport
{
    /**
     * Import products from an Excel/CSV file.
     *
     * @param  string $filePath  Absolute path to the uploaded file
     * @return int    Number of products created
     * @throws \PhpOffice\PhpSpreadsheet\Reader\Exception
     */
    public function import(string $filePath): int
    {
        $reader = IOFactory::createReaderForFile($filePath);
        $spreadsheet = $reader->load($filePath);
        $sheet = $spreadsheet->getActiveSheet();

        // Get rows as arrays keyed by column letters (A, B, C…)
        $rows = $sheet->toArray(null, true, true, true);
        $count = 0;

        foreach ($rows as $index => $row) {
            // Skip header row
            if ($index === 1) {
                continue;
            }

            $storeId     = $row['A'];
            $name        = $row['B'];
            $description = $row['C'];
            $price       = $row['D'];
            $sku         = $row['E'] ?? null;
            $barcode     = $row['F'] ?? null;
            $isOnline    = $row['G'] ?? true;
            $stockQty    = $row['H'] ?? 0;
            $categoryRaw = trim($row['I'] ?? '');
            $images      = array_filter(explode(',', $row['J'] ?? ''));

            // Must have a single category ID
            $categoryIds = array_filter(explode(',', $categoryRaw));
            if (empty($categoryIds)) {
                // skip if no category supplied
                continue;
            }
            $categoryId = $categoryIds[0]; // only the first one

            if (empty($storeId) || empty($name) || empty($price)) {
                continue; // minimal validation
            }

            $product = Product::create([
                'store_id'    => $storeId,
                'category_id' => $categoryId,
                'name'        => $name,
                'slug'        => Str::slug($name),
                'description' => $description,
                'price'       => $price,
                'sku'         => $sku,
                'barcode'     => $barcode,
                'is_online'   => filter_var($isOnline, FILTER_VALIDATE_BOOLEAN),
                'stock_qty'   => (int) $stockQty,
            ]);

            // Add images if any
            foreach ($images as $position => $path) {
                ProductImage::create([
                    'product_id' => $product->id,
                    'path'       => $path,
                    'position'   => $position,
                    'is_cover'   => $position === 0,
                ]);
            }

            $count++;
        }

        return $count;
    }
}
