<?php

return [
    'storage_disk' => 'local',

    'base_path' => 'docstore',

    'visibility' => [
        'resolver' => \LaurentMeuwly\Docstore\Services\AllowAllVisibilityResolver::class,
    ],

    'models' => [
        'document'  => \LaurentMeuwly\Docstore\Models\Document::class,
        'folder'    => \LaurentMeuwly\Docstore\Models\Folder::class,
    ],
];
