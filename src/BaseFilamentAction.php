<?php

namespace QuickExcelImport\ExcelImport;

use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Storage;

class BaseFilamentAction
{
    /**
     * Create a Filament action for Excel import
     *
     * @param array $data Configuration data
     * @return \Filament\Actions\Action
     */
    public static function action(array $data)
    {
        $importerClass = $data['importer'];
        $label = $data['label'] ?? 'Import';
        $can = $data['can'] ?? true;
        $form = $data['form'] ?? [];
        $labelId = str_replace(' ', '-', strtolower($label)); 

        // Merge custom form fields with default file upload
        $formFields = [
            FileUpload::make('file')
                ->label('Excel File')
                ->acceptedFileTypes([
                    'application/vnd.ms-excel',
                    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                ])
                ->required(),
        ];

        // Add overwrite toggle if not explicitly provided in form
        if (!isset($form['overwrite'])) {
            $formFields[] = Toggle::make('overwrite')
                ->label('Overwrite existing records')
                ->default(false);
        }

        // Merge with any custom form fields
        $formFields = array_merge($formFields, $form);

        return Action::make($labelId)
            ->label($label)
            ->visible($can)
            ->form($formFields)
            ->action(function (array $data) use ($importerClass) {
                self::actionUpload($data, $importerClass);
            });
    }

    /**
     * Handle the file upload and import process
     *
     * @param array $data Form data
     * @param string $importerClass Importer class name
     * @return void
     */
    public static function actionUpload(array $data, string $importerClass)
    {
        try {
            $file = Storage::disk(config('excel-import.storage_disk', 'public'))->path($data['file']);
            $importer = new $importerClass($file, $data['overwrite'] ?? false);
            $result = $importer->run();
            
            if ($result['status'] === true) {
                Notification::make()
                    ->success()
                    ->title('Import Successful')
                    ->send();
            } else {
                $errorFilePath = $result['errors'];
                $fileName = basename($errorFilePath);
                $errorUrl = config('excel-import.error_url_prefix', '') . $fileName;

                Notification::make()
                    ->danger()
                    ->title('Import Failed')
                    ->body('Some records could not be imported. Download the error report for details.')
                    ->actions([
                        \Filament\Notifications\Actions\Action::make('download')
                            ->label('Download Errors')
                            ->url($errorUrl)
                            ->openUrlInNewTab()
                    ])
                    ->send();
            }
            
            // Clean up uploaded file
            Storage::disk(config('excel-import.storage_disk', 'public'))->delete($data['file']);
        } catch (\Exception $e) {
            Notification::make()
                ->danger()
                ->title('Import Failed')
                ->body($e->getMessage())
                ->send();
        }
    }
}