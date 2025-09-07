<?php

namespace App\Imports;

use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductImage;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;

class ProductsImport implements ToCollection
{
    public function collection(Collection $rows)
    {
        // Skip header row if present
        foreach ($rows->skip(1) as $row) {
            $storeId = $row[0]; // store_id
            $name = $row[1];
            $description = $row[2];
            $price = $row[3];
            $sku = $row[4] ?? null;
            $barcode = $row[5] ?? null;
            $isOnline = $row[6] ?? true;
            $stockQty = $row[7] ?? 0;
            $categoryIds = explode(',', $row[8] ?? ''); // comma-separated category IDs
            $images = explode(',', $row[9] ?? ''); // comma-separated image URLs

            $product = Product::create([
                'store_id' => $storeId,
                'name' => $name,
                'slug' => \Str::slug($name),
                'description' => $description,
                'price' => $price,
                'sku' => $sku,
                'barcode' => $barcode,
                'is_online' => $isOnline,
                'stock_qty' => $stockQty,
            ]);

            // Add categories
            foreach ($categoryIds as $catId) {
                ProductCategory::create([
                    'product_id' => $product->id,
                    'category_id' => $catId
                ]);
            }

            // Add images
            foreach ($images as $index => $imgPath) {
                ProductImage::create([
                    'product_id' => $product->id,
                    'path' => $imgPath,
                    'position' => $index,
                    'is_cover' => $index === 0
                ]);
            }
        }
    }
}
