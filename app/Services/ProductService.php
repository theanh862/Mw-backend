<?php

namespace App\Services;

use App\Repositories\ProductRepositoryInterface;

class ProductService
{
    public function __construct(
        protected ProductRepositoryInterface $productRepository,
        protected TranslationService $translationService,
    ) {}

    public function getAllProducts(array $filters = [])
    {
        return $this->productRepository->getAll($filters);
    }

    public function getProductById(int $id)
    {
        return $this->productRepository->findById($id);
    }

    public function createProduct(array $data)
    {
        // Xử lý specs nếu gửi dạng riêng lẻ
        if (isset($data['screen']) || isset($data['cpu'])) {
            $data['specs'] = [
                'screen' => $data['screen'] ?? 'N/A',
                'cpu' => $data['cpu'] ?? 'N/A',
                'ram' => $data['ram'] ?? 'N/A',
                'storage' => $data['storage'] ?? 'N/A',
                'battery' => $data['battery'] ?? 'N/A',
            ];
            unset($data['screen'], $data['cpu'], $data['ram'], $data['storage'], $data['battery']);
        }

        // Xử lý specs_en nếu gửi dạng riêng lẻ
        if (isset($data['screen_en']) || isset($data['cpu_en'])) {
            $data['specs_en'] = [
                'screen' => $data['screen_en'] ?? 'N/A',
                'cpu' => $data['cpu_en'] ?? 'N/A',
                'ram' => $data['ram_en'] ?? 'N/A',
                'storage' => $data['storage_en'] ?? 'N/A',
                'battery' => $data['battery_en'] ?? 'N/A',
            ];
            unset($data['screen_en'], $data['cpu_en'], $data['ram_en'], $data['storage_en'], $data['battery_en']);
        }

        $skusData = $data['skus'] ?? [];
        unset($data['skus']);

        if (empty($data['name_en']) && !empty($data['name'])) {
            $data['name_en'] = $this->translationService->translate($data['name']) ?? $data['name'];
        }
        if (empty($data['description_en']) && !empty($data['description'])) {
            $data['description_en'] = $this->translationService->translate($data['description']) ?? $data['description'];
        }
        if (!empty($data['specs']) && empty($data['specs_en'])) {
            $data['specs_en'] = $this->translateSpecsValues($data['specs']);
        }

        $product = $this->productRepository->create($data);

        if (!empty($skusData)) {
            foreach ($skusData as $skuData) {
                $nameEn = $this->translationService->translate($skuData['name']) ?? $skuData['name'];
                $product->skus()->create([
                    'sku' => $skuData['sku'],
                    'name' => $skuData['name'],
                    'name_en' => $nameEn,
                    'price' => $skuData['price'],
                    'original_price' => $skuData['original_price'] ?? null,
                    'stock' => $skuData['stock'] ?? 0,
                    'image' => $skuData['image'] ?? null,
                ]);
            }
        }

        return $product->fresh(['category', 'skus']);
    }

    public function updateProduct(int $id, array $data)
    {
        // Xử lý specs nếu gửi dạng riêng lẻ
        if (isset($data['screen']) || isset($data['cpu'])) {
            $data['specs'] = [
                'screen' => $data['screen'] ?? 'N/A',
                'cpu' => $data['cpu'] ?? 'N/A',
                'ram' => $data['ram'] ?? 'N/A',
                'storage' => $data['storage'] ?? 'N/A',
                'battery' => $data['battery'] ?? 'N/A',
            ];
            unset($data['screen'], $data['cpu'], $data['ram'], $data['storage'], $data['battery']);
        }

        // Xử lý specs_en nếu gửi dạng riêng lẻ
        if (isset($data['screen_en']) || isset($data['cpu_en'])) {
            $data['specs_en'] = [
                'screen' => $data['screen_en'] ?? 'N/A',
                'cpu' => $data['cpu_en'] ?? 'N/A',
                'ram' => $data['ram_en'] ?? 'N/A',
                'storage' => $data['storage_en'] ?? 'N/A',
                'battery' => $data['battery_en'] ?? 'N/A',
            ];
            unset($data['screen_en'], $data['cpu_en'], $data['ram_en'], $data['storage_en'], $data['battery_en']);
        }

        $skusData = $data['skus'] ?? null;
        unset($data['skus']);

        if (empty($data['name_en']) && !empty($data['name'])) {
            $data['name_en'] = $this->translationService->translate($data['name']) ?? $data['name'];
        }
        if (empty($data['description_en']) && !empty($data['description'])) {
            $data['description_en'] = $this->translationService->translate($data['description']) ?? $data['description'];
        }
        if (!empty($data['specs']) && empty($data['specs_en'])) {
            $data['specs_en'] = $this->translateSpecsValues($data['specs']);
        }

        $product = $this->productRepository->update($id, $data);

        if ($skusData !== null) {
            $existingSkuIds = $product->skus()->pluck('id')->toArray();
            $newSkuIds = [];

            foreach ($skusData as $skuData) {
                $nameEn = $this->translationService->translate($skuData['name']) ?? $skuData['name'];
                if (!empty($skuData['id'])) {
                    $product->skus()->where('id', $skuData['id'])->update([
                        'sku' => $skuData['sku'],
                        'name' => $skuData['name'],
                        'name_en' => $nameEn,
                        'price' => $skuData['price'],
                        'original_price' => $skuData['original_price'] ?? null,
                        'stock' => $skuData['stock'] ?? 0,
                        'image' => $skuData['image'] ?? null,
                    ]);
                    $newSkuIds[] = $skuData['id'];
                } else {
                    $newSku = $product->skus()->create([
                        'sku' => $skuData['sku'],
                        'name' => $skuData['name'],
                        'name_en' => $nameEn,
                        'price' => $skuData['price'],
                        'original_price' => $skuData['original_price'] ?? null,
                        'stock' => $skuData['stock'] ?? 0,
                        'image' => $skuData['image'] ?? null,
                    ]);
                    $newSkuIds[] = $newSku->id;
                }
            }

            $toDeleteIds = array_diff($existingSkuIds, $newSkuIds);
            if (!empty($toDeleteIds)) {
                $product->skus()->whereIn('id', $toDeleteIds)->delete();
            }
        }

        return $product->fresh(['category', 'skus']);
    }

    public function deleteProduct(int $id)
    {
        return $this->productRepository->delete($id);
    }

    public function getProductCount()
    {
        return $this->productRepository->count();
    }

    private function translateSpecsValues(array $specs): array
    {
        $specsEn = [];
        foreach ($specs as $key => $value) {
            if (!empty($value) && $value !== 'N/A') {
                $translated = $this->translationService->translate((string) $value);
                $specsEn[$key] = $translated ?? $value;
            } else {
                $specsEn[$key] = $value;
            }
        }
        return $specsEn;
    }
}
