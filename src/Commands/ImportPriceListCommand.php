<?php

namespace PlanParts\CrossMatcher\Commands;

use Illuminate\Console\Command;
use PlanParts\CrossMatcher\Services\PriceListImporter;

class ImportPriceListCommand extends Command
{
    protected $signature = 'plan-parts:import
                            {file : Path to the CSV file}
                            {--name= : Price list name (defaults to filename)}
                            {--supplier= : Supplier / distributor name}';

    protected $description = 'Import a supplier price list CSV into the cross-matcher database';

    public function handle(PriceListImporter $importer): int
    {
        $file     = $this->argument('file');
        $name     = $this->option('name') ?: pathinfo($file, PATHINFO_FILENAME);
        $supplier = $this->option('supplier') ?? '';

        if (!file_exists($file)) {
            $this->error("File not found: {$file}");
            return self::FAILURE;
        }

        $this->info("Importing price list from: {$file}");

        try {
            $priceList = $importer->import($file, $name, $supplier);
            $this->info("Done! Imported {$priceList->items_count} items into \"{$priceList->name}\" (ID: {$priceList->id}).");
        } catch (\Throwable $e) {
            $this->error("Import failed: {$e->getMessage()}");
            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
