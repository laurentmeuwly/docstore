<?php

use Illuminate\Support\Facades\Route;
use LaurentMeuwly\Docstore\Http\Controllers\DocumentDownloadController;

Route::middleware(config('docstore.routes.middleware', ['web', 'auth']))
    ->prefix(config('docstore.routes.prefix', 'docstore'))
    ->get('{id}/download', DocumentDownloadController::class)
    ->name('docstore.download');
