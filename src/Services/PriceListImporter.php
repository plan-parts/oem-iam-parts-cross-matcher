<?php

namespace PlanParts\CrossMatcher\Services;

use Illuminate\Support\Facades\DB;
use PlanParts\CrossMatcher\Models\PriceList;
use PlanParts\CrossMatcher\Models\PriceListItem;

class PriceListImporter
{
    private const CHUNK_SIZE = 500;

    /**
     * Import a CSV price list file.
     *
     * Expected CSV columns (header row required):
     *   article, brand, price, currency, description, availability
     *
     * @param  string  $filePath  Absolute path to the CSV file
     * @param  string  $name      Human-readable name for this price list
     * @param  string  $supplier  Supplier / distributor name
     * @return PriceList
     */
    public function import(string $filePath, string $name, string $supplier = ''): PriceList
    {
        if (!file_exists($filePath)) {
            throw new \InvalidArgumentException("File not found: {$filePath}");
        }

        $handle = fopen($filePath, 'r');
        if ($handle === false) {
            throw new \RuntimeException("Cannot open file: {$filePath}");
        }

        // Read header row
        $header = fgetcsv($handle);
        if ($header === false) {
            fclose($handle);
            throw new \RuntimeException('CSV file is empty or unreadable.');
        }

        $header = array_map('strtolower', array_map('trim', $header));
        $this->validateHeader($header);

        $priceList = PriceList::create([
            'name'        => $name,
            'filename'    => basename($filePath),
            'supplier'    => $supplier,
            'currency'    => 'USD',
            'items_count' => 0,
            'imported_at' => now(),
        ]);

        $chunk      = [];
        $totalCount = 0;

        while (($row = fgetcsv($handle)) !== false) {
            $data = array_combine($header, $row);

            if (empty($data['article'])) {
                continue;
            }

            $article = trim($data['article']);
            $brand   = strtoupper(trim($data['brand'] ?? ''));

            $chunk[] = [
                'price_list_id'      => $priceList->id,
                'article'            => $article,
                'article_normalized' => strtoupper(preg_replace('/[\s\-_.]/', '', $article)),
                'brand'              => $brand,
                'brand_normalized'   => preg_replace('/[\s\-_.]/', '', $brand),
                'price'              => (float) str_replace(',', '.', $data['price'] ?? '0'),
                'currency'           => strtoupper(trim($data['currency'] ?? 'USD')),
                'description'        => trim($data['description'] ?? ''),
                'availability'       => trim($data['availability'] ?? ''),
                'created_at'         => now(),
                'updated_at'         => now(),
            ];

            $totalCount++;

            if (count($chunk) >= self::CHUNK_SIZE) {
                PriceListItem::insert($chunk);
                $chunk = [];
            }
        }

        fclose($handle);

        if (!empty($chunk)) {
            PriceListItem::insert($chunk);
        }

        $priceList->update(['items_count' => $totalCount]);

        return $priceList;
    }

    private function validateHeader(array $header): void
    {
        $required = ['article', 'brand', 'price'];

        foreach ($required as $col) {
            if (!in_array($col, $header, true)) {
                throw new \InvalidArgumentException(
                    "CSV is missing required column: \"{$col}\". " .
                    "Expected columns: article, brand, price, currency, description, availability"
                );
            }
        }
    }
}
