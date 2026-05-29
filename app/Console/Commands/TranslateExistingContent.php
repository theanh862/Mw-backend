<?php

namespace App\Console\Commands;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductSku;
use App\Services\TranslationService;
use Illuminate\Console\Command;

class TranslateExistingContent extends Command
{
    protected $signature = 'translate:existing';
    protected $description = 'Auto-translate existing products and categories to English';

    public function handle(TranslationService $translator): void
    {
        // Translate categories
        $categories = Category::whereNull('name_en')->orWhere('name_en', '')->get();
        $this->info("Translating {$categories->count()} categories...");

        foreach ($categories as $cat) {
            $translated = $translator->translate($cat->name);
            if ($translated) {
                $cat->update(['name_en' => $translated]);
                $this->line("  ✓ {$cat->name} → {$translated}");
            }
            usleep(300000);
        }

        // Translate products
        $products = Product::where(function ($q) {
            $q->whereNull('name_en')->orWhere('name_en', '')
              ->orWhereNull('specs_en');
        })->get();

        $this->info("Translating {$products->count()} products...");

        foreach ($products as $product) {
            $updates = [];

            if (empty($product->name_en)) {
                $translated = $translator->translate($product->name);
                $updates['name_en'] = $translated ?? $product->name;
                $this->line("  ✓ name: {$product->name} → " . ($updates['name_en']));
                usleep(300000);
            }

            if (empty($product->description_en) && !empty($product->description)) {
                $translated = $translator->translate($product->description);
                if ($translated) {
                    $updates['description_en'] = $translated;
                }
                usleep(300000);
            }

            if (empty($product->specs_en) && !empty($product->specs)) {
                $specsEn = [];
                foreach ($product->specs as $key => $value) {
                    if (!empty($value) && $value !== 'N/A') {
                        $translated = $translator->translate((string) $value);
                        $specsEn[$key] = $translated ?? $value;
                        $this->line("    spec [{$key}]: {$value} → " . ($specsEn[$key]));
                        usleep(300000);
                    } else {
                        $specsEn[$key] = $value;
                    }
                }
                $updates['specs_en'] = $specsEn;
            }

            if (!empty($updates)) {
                $product->update($updates);
            }
        }

        // Translate SKUs
        $skus = ProductSku::whereNull('name_en')->orWhere('name_en', '')->get();
        $this->info("Translating {$skus->count()} SKUs...");

        foreach ($skus as $sku) {
            $translated = $translator->translate($sku->name);
            $nameEn = $translated ?? $sku->name;
            $sku->update(['name_en' => $nameEn]);
            $this->line("  ✓ sku: {$sku->name} → {$nameEn}");
            usleep(300000);
        }

        $this->info('Done!');
    }
}
