# Excel Import Package for Laravel

A flexible Laravel package for importing Excel files into Eloquent models with support for Filament.

## Installation

```bash
composer require quickhelper/quickexcelimport
```

## Publish Configuration

```bash
php artisan vendor:publish --tag=excel-import-config
```

## Basic Usage

### Create an Importer

Create a class that extends the `BaseImport` class:

```php
<?php

namespace App\Imports;

use App\Models\Product;
use QuickExcelImport\ExcelImport\BaseImport;

class ProductImport extends BaseImport
{
    protected $condition = 'sku'; // Column to use for finding existing records

    public function __construct($filePath, $overwrite = false)
    {
        parent::__construct($filePath, new Product(), $overwrite, $this->condition);
    }

    public function handle($row)
    {
        // Transform the data before importing
        return [
            'name' => $row['name'],
            'sku' => $row['sku'],
            'price' => $row['price'],
            'description' => $row['description'],
            // Add more fields as needed
        ];
    }
}
```

### Using with Filament

Add the import action to your Filament resource:

```php
<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProductResource\Pages;
use App\Imports\ProductImport;
use Filament\Resources\Resource;
use QuickExcelImport\ExcelImport\BaseFilamentAction;

class ProductResource extends Resource
{
    // Your resource configuration...

    public static function getActions(): array
    {
        $data = [
            'importer' => ProductImport::class,
            'label' => 'Import Products',
            'can' => auth()->user()->can('import products'),
            'form' => [
                // Add additional form fields if needed
            ],
        ];

        return [
            BaseFilamentAction::action($data),
        ];
    }
}
```

## Features

- Import Excel files into Eloquent models
- Handle validation and error reporting
- Support for overwriting existing records
- Integration with Filament for UI
- Customizable error handling and reporting
- Export errors to Excel for easy debugging

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.

Best regards,  
[Yossef Ashraf](https://github.com/yossef-ashraf)