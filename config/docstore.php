<?php

use LaurentMeuwly\Docstore\Models\Document;
use LaurentMeuwly\Docstore\Models\Folder;
use LaurentMeuwly\Docstore\Services\AllowAllVisibilityResolver;

return [
    'storage_disk' => 'local',

    'base_path' => 'docstore',

    'visibility' => [
        'resolver' => AllowAllVisibilityResolver::class,
    ],

    'models' => [
        'document' => Document::class,
        'folder' => Folder::class,
    ],
];
