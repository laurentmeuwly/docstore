<?php

use LaurentMeuwly\Docstore\Http\Controllers\DocumentDownloadController;

Route::middleware(['web', 'auth'])
    ->get('/docstore/{id}/download', DocumentDownloadController::class)
    ->name('docstore.download');
