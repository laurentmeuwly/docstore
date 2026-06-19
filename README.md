# DocStore

![Laravel](https://img.shields.io/badge/Laravel-10|11|12-red)
![PHP](https://img.shields.io/badge/PHP-8.2+-blue)
![License](https://img.shields.io/badge/license-MIT-green)
[![Tests](https://github.com/laurentmeuwly/docstore/actions/workflows/tests.yml/badge.svg)](https://github.com/laurentmeuwly/docstore/actions/workflows/tests.yml)

Docstore is a small, extensible Laravel package for managing folders and documents in an application. It provides Eloquent models, migrations, a download route, and a configurable visibility resolver, while leaving the business rules to the host application.

The package is intentionally generic. It does not contain project-specific rules and can be reused in Laravel applications that need a lightweight document store.

## Features

- Hierarchical folders with parent/children relationships
- Document metadata: title, filename, MIME type, disk, path, size and JSON metadata
- Storage through the Laravel filesystem
- Secure downloads through a configurable visibility resolver
- Configurable model classes for application-specific extensions
- Laravel package auto-discovery
- Laravel 10, 11 and 12 support
- PHP 8.2+

## Installation

```bash
composer require laurentmeuwly/docstore
```

Publish the configuration file:

```bash
php artisan docstore:install
```

Publish the configuration file and migrations:

```bash
php artisan docstore:install --migrations
```

Then run your migrations:

```bash
php artisan migrate
```

## Configuration

After installation, the package configuration is available in `config/docstore.php`.

```php
return [
    'storage_disk' => 'local',

    'base_path' => 'docstore',

    'visibility' => [
        'resolver' => \LaurentMeuwly\Docstore\Services\AllowAllVisibilityResolver::class,
    ],

    'models' => [
        'document' => \LaurentMeuwly\Docstore\Models\Document::class,
        'folder' => \LaurentMeuwly\Docstore\Models\Folder::class,
    ],
];
```

## Data model

### Folder

The default `Folder` model contains:

- `id`
- `parent_id`
- `name`
- `position`
- timestamps

Relations:

- `parent()`
- `children()`
- `documents()`

Accessors:

- `full_path`

### Document

The default `Document` model contains:

- `id`
- `folder_id`
- `title`
- `filename`
- `mime_type`
- `disk`
- `path`
- `meta`
- `size`
- timestamps

Relations:

- `folder()`

Accessors and helpers:

- `url()`
- `formatted_size`
- `formatted_date`

## Extending models

You may replace the default models with your own application models.

```php
namespace App\Models;

class Document extends \LaurentMeuwly\Docstore\Models\Document
{
    // Add application-specific casts, relations, scopes or policies.
}
```

Then update `config/docstore.php`:

```php
'models' => [
    'document' => \App\Models\Document::class,
    'folder' => \App\Models\Folder::class,
],
```

The package will use the configured classes for its relationships and download logic.

## Visibility resolver

Downloads are protected by a visibility resolver. The default resolver allows access to every authenticated user that can reach the route.

To implement custom access rules, create a class implementing `DocumentVisibilityResolver`:

```php
namespace App\Services;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use LaurentMeuwly\Docstore\Contracts\DocumentVisibilityResolver;

class DocumentVisibilityResolver implements DocumentVisibilityResolver
{
    public function canAccess(Model $document, ?Authenticatable $user): bool
    {
        return $user !== null && $user->can('view', $document);
    }
}
```

Then configure it:

```php
'visibility' => [
    'resolver' => \App\Services\DocumentVisibilityResolver::class,
],
```

## Downloads

Docstore registers the following route:

```text
GET /docstore/{id}/download
```

The route is named:

```text
docstore.download
```

You can generate the download URL from a document model:

```php
$url = $document->url();
```

By default, the package expects the `path` column to contain the full relative path of the file on the configured filesystem disk.

Example:

```php
$file = $request->file('file');

$disk = config('docstore.storage_disk');
$path = $file->store(config('docstore.base_path'), $disk);

$documentClass = config('docstore.models.document');

$document = $documentClass::create([
    'folder_id' => $folder?->id,
    'title' => pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME),
    'filename' => $file->getClientOriginalName(),
    'mime_type' => $file->getMimeType(),
    'disk' => $disk,
    'path' => $path,
    'size' => $file->getSize(),
]);
```

## Artisan command

```bash
php artisan docstore:install
```

Options:

```bash
php artisan docstore:install --migrations
php artisan docstore:install --force
```

Published assets:

- `docstore-config`
- `docstore-migrations`

## Development

Install dependencies:

```bash
composer install
```

Run tests:

```bash
composer test
```

Run static analysis:

```bash
composer analyse
```

Check formatting:

```bash
composer lint
```

Fix formatting:

```bash
composer format
```

## Versioning

This package follows semantic versioning.

Laravel 12 support is introduced in the `1.1.x` series.

## License

Docstore is open-source software licensed under the MIT license.
