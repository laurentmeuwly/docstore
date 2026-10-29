<?php

use LaurentMeuwly\Docstore\Models\Document;
use LaurentMeuwly\Docstore\Models\Folder;
use LaurentMeuwly\Docstore\Services\AllowAllVisibilityResolver;

return [

    /*
    |--------------------------------------------------------------------------
    | Storage
    |--------------------------------------------------------------------------
    |
    | Default filesystem disk used when a document has no `disk` value, and
    | the base directory your application may use when storing new files.
    |
    */

    'storage_disk' => env('DOCSTORE_DISK', 'local'),

    'base_path' => 'docstore',

    /*
    |--------------------------------------------------------------------------
    | Visibility resolver
    |--------------------------------------------------------------------------
    |
    | Class implementing LaurentMeuwly\Docstore\Contracts\DocumentVisibilityResolver.
    | The default resolver grants access to ANY authenticated user: replace it
    | with your own rules before going to production.
    |
    */

    'visibility' => [
        'resolver' => AllowAllVisibilityResolver::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Models
    |--------------------------------------------------------------------------
    |
    | Override these with subclasses of the package models to add your own
    | casts, relations or scopes.
    |
    */

    'models' => [
        'document' => Document::class,
        'folder' => Folder::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Download route
    |--------------------------------------------------------------------------
    |
    | Set `enabled` to false to register your own route instead (the route
    | name `docstore.download` is used by Document::url()).
    |
    */

    'routes' => [
        'enabled' => true,
        'prefix' => 'docstore',
        'middleware' => ['web', 'auth'],
    ],

];
