<?php

namespace QuickExcelImport\ExcelImport;

use Illuminate\Database\Eloquent\Model;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

abstract class BaseImport
{
    /**
     * The Eloquent model to import data into
     *
     * @var Model
     */
    protected $model;

    /**
     * Path to the import file
     *
     * @var string
     */
    protected $filePath;

    /**
     * Whether to overwrite existing records
     *
     * @var bool
     */
    protected $overwrite;

    /**
     * The column to use as a condition for overwriting
     *
     * @var string|null
     */
    protected $condition;

    /**
     * Array to store import data
     *
     * @var array
     */
    protected $data;

    /**
     * Array to store import errors
     *
     * @var array
     */
    protected $errors = [];

    /**
     * Create a new import instance
     *
     * @param string $filePath Path to the Excel file
     * @param Model $model The model to import data into
     * @param bool $overwrite Whether to overwrite existing records
     * @param string|null $condition The column to use as condition for overwriting
     */
    public function __construct($filePath, Model $model, $overwrite = false, $condition = null)
    {
        $this->filePath = $filePath;
        $this->model = $model;
        $this->overwrite = $overwrite;
        $this->condition = $condition;
    }

    /**
     * Extract data from Excel file
     *
     * @param string $filePath
     * @return array
     * @throws \Exception
     */
    public function getData($filePath)
    {
        if (!file_exists($filePath)) {
            throw new \Exception('File not found: ' . $filePath);
        }
        
        $spreadsheet = IOFactory::load($filePath);
        $sheet = $spreadsheet->getActiveSheet();
        $highestRow = $sheet->getHighestRow();
        $highestColumn = $sheet->getHighestColumn();

        $headers = $sheet->rangeToArray('A1:' . $highestColumn . '1', null, true, true)[0];
        $data = $sheet->rangeToArray('A2:' . $highestColumn . $highestRow, null, true, true);

        $result = array_map(function($row) use ($headers) {
            return array_combine($headers, $row);
        }, $data);
        
        return $result;
    }
    
    /**
     * Import data into model
     *
     * @param array $data
     * @return void
     */
    public function import($data)
    {
        foreach ($data as $row) {
            if (empty(array_filter($row))) {
                continue;
            }
            
            // Transform the data using the handle method
            $transformedRow = $this->handle($row);
            $this->processRow($transformedRow);
        }
    }

    /**
     * Process a single row of data
     *
     * @param array $row
     * @return Model|null
     */
    protected function processRow($row)
    {
        try {
            if ($this->overwrite && $this->condition && !empty($row[$this->condition])) {
                $existingRecord = $this->model->where($this->condition, $row[$this->condition])->first();
                if ($existingRecord) {
                    $existingRecord->update($row);
                    return $existingRecord; 
                }
            }
            
            $obj = $this->model->create($row);
            return $obj;
        } catch (\Throwable $th) {
            $this->errors[] = $row;
            return null;
        }
    }

    /**
     * Run the import process
     *
     * @return array
     */
    public function run()
    {
        $data = $this->getData($this->filePath);
        $this->import($data);
        return $this->response();
    }

    /**
     * Get import errors
     *
     * @return array
     */
    public function getErrors()
    {
        return $this->errors;
    }

    /**
     * Get the import response
     *
     * @return array
     */
    public function response()
    {
        if ($this->errors) {
            return ['status' => false, 'errors' => $this->exportErrors()];
        }
        return ['status' => true];
    }

    /**
     * Export errors to an Excel file
     *
     * @return string
     * @throws \Exception
     */
    public function exportErrors()
    {
        if (empty($this->errors)) {
            throw new \Exception('No errors to export.');
        }
    
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->fromArray(array_keys($this->errors[0]), null, 'A1');
        
        $rowIndex = 2;
        foreach ($this->errors as $error) {
            $columnIndex = 0; 
            foreach ($error as $data) {
                $columnLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($columnIndex + 1);
                $sheet->setCellValue($columnLetter . $rowIndex, $data);
                $columnIndex++;
            }
            $rowIndex++;
        }
    
        $writer = new Xlsx($spreadsheet);
        $errorDir = config('excel-import.error_directory', 'errors');
        $filePath = $errorDir . '/errors_' . time() . '.xlsx';
        
        // Create directory if it doesn't exist
        if (!is_dir(dirname($filePath))) {
            mkdir(dirname($filePath), 0755, true);
        }
        
        if (!is_writable(dirname($filePath))) {
            throw new \Exception('Cannot write to the specified error directory: ' . dirname($filePath));
        }
        
        $writer->save($filePath);
    
        return $filePath;
    }

    /**
     * Transform the raw data before importing
     * This method should be implemented by child classes
     *
     * @param array $row
     * @return array
     */
    abstract public function handle($row);
}