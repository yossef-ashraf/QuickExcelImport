<?php

namespace QuickExcelImport\ExcelImport;

use Illuminate\Support\ServiceProvider;

class ExcelImportServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any package services.
     *
     * @return void
     */
    public function boot()
    {
        // Publish configuration
        $this->publishes([
            __DIR__ . '/../config/excel-import.php' => config_path('excel-import.php'),
        ], 'excel-import-config');
        
        // Merge config
        $this->mergeConfigFrom(
            __DIR__ . '/../config/excel-import.php', 'excel-import'
        );
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        // Register services if needed
    }
}