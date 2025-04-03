<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Storage Disk
    |--------------------------------------------------------------------------
    |
    | The disk to use for storing uploaded Excel files and error reports.
    |
    */
    'storage_disk' => 'public',

    /*
    |--------------------------------------------------------------------------
    | Error Directory
    |--------------------------------------------------------------------------
    |
    | The directory within the storage disk to store error reports.
    |
    */
    'error_directory' => 'excel-import/errors',

    /*
    |--------------------------------------------------------------------------
    | Error URL Prefix
    |--------------------------------------------------------------------------
    |
    | The URL prefix to use when generating links to error reports.
    |
    */
    'error_url_prefix' => '/storage/excel-import/errors/',
];