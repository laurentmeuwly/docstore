# Docstore

[![Tests](https://github.com/laurentmeuwly/docstore/actions/workflows/tests.yml/badge.svg)](https://github.com/laurentmeuwly/docstore/actions/workflows/tests.yml)
[![Latest Release](https://img.shields.io/github/v/release/laurentmeuwly/docstore?sort=semver)](https://github.com/laurentmeuwly/docstore/releases)
![Laravel](https://img.shields.io/badge/Laravel-12%20|%2013-red)
![PHP](https://img.shields.io/badge/PHP-8.2%20|%208.3%20|%208.4-blue)
![License](https://img.shields.io/badge/license-MIT-green)

Docstore is a small, extensible Laravel package for storing **folders and documents**. It ships Eloquent models, a migration, an authenticated download route and a pluggable access-control hook — and leaves every business rule to your application.

It deliberately does **not** provide an upload UI, a file browser or permissions of its own: you keep full control over how files arrive and who may read them.

## Contents

- [Requirements](#requirements)
- [Installation](#installation)
- [Configuration](#configuration)
- [Storing a document](#storing-a-document)
- [Downloading](#downloading)
- [Access control](#access-control)
- [Extending the models](#extending-the-models)
- [Data model reference](#data-model-reference)
- [Upgrading](#upgrading)
- [Development](#development)

## Requirements

| Docstore | Laravel              | PHP            |
|----------|----------------------|----------------|
| 1.2.x    | 12, 13               | 8.2 – 8.4      |
| 1.1.x    | 10, 11, 12           | 8.2+           |
| 1.0.x    | 10, 11               | 8.2+           |

Laravel 13 itself requires PHP 8.3 or later; Composer picks the right combination for you.

Laravel 10 and 11 no longer receive security fixes and are not supported from 1.2.0 on. Applications still on those versions keep receiving Docstore 1.1.x.

## Installation

Docstore is not published on Packagist. Declare the GitHub repository in your application's `composer.json`:

```json
"repositories": [
    {
        "type": "vcs",
        "url": "https://github.com/laurentmeuwly/docstore"
    }
]
```

then require it:

```bash
composer require laurentmeuwly/docstore:^1.2
```

Composer reads the available versions from the repository's Git tags (`v1.2.0`, …).

The service provider is registered automatically (package auto-discovery).

Publish the configuration and the migration, then migrate:

```bash
php artisan docstore:install --migrations
php artisan migrate
```

`docstore:install` options:

| Option               | Effect                                  |
|----------------------|-----------------------------------------|
| `-m`, `--migrations` | Also publish the migration              |
| `-f`, `--force`      | Overwrite previously published files    |
| `-v`                 | Show the output of `vendor:publish`     |

You can also publish each asset yourself with `php artisan vendor:publish --tag=docstore-config` or `--tag=docstore-migrations`. Re-publishing the migration reuses the existing file instead of creating a duplicate.

> The migration creates two tables named **`folders`** and **`documents`**. Check that your application does not already use these names before migrating.

## Configuration

`config/docstore.php`:

```php
return [
    // Disk used when a document has no `disk` value
    'storage_disk' => env('DOCSTORE_DISK', 'local'),

    // Suggested directory for new files (used by your own upload code)
    'base_path' => 'docstore',

    'visibility' => [
        // Must implement LaurentMeuwly\Docstore\Contracts\DocumentVisibilityResolver
        'resolver' => \LaurentMeuwly\Docstore\Services\AllowAllVisibilityResolver::class,
    ],

    'models' => [
        'document' => \LaurentMeuwly\Docstore\Models\Document::class,
        'folder'   => \LaurentMeuwly\Docstore\Models\Folder::class,
    ],

    'routes' => [
        'enabled'    => true,
        'prefix'     => 'docstore',
        'middleware' => ['web', 'auth'],
    ],
];
```

## Storing a document

Docstore does not handle uploads; you store the file with the Laravel filesystem and create the matching record. The `path` column must contain the path of the file **relative to the root of its disk**.

```php
use Illuminate\Http\Request;
use LaurentMeuwly\Docstore\Models\Document;

public function store(Request $request)
{
    $request->validate([
        'file' => ['required', 'file', 'max:20480'],
        'folder_id' => ['nullable', 'exists:folders,id'],
    ]);

    $file = $request->file('file');
    $disk = config('docstore.storage_disk');

    $document = Document::model()::create([
        'folder_id' => $request->input('folder_id'),
        'title'     => pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME),
        'filename'  => $file->getClientOriginalName(),
        'mime_type' => $file->getMimeType(),
        'disk'      => $disk,
        'path'      => $file->store(config('docstore.base_path'), $disk),
        'size'      => $file->getSize(),
    ]);

    return redirect($document->url());
}
```

`Document::model()` and `Folder::model()` return the classes configured in `docstore.models`, so your code keeps working if you swap in your own models.

## Downloading

Docstore registers one route:

| Method | URI                        | Name                |
|--------|----------------------------|---------------------|
| GET    | `/docstore/{id}/download`  | `docstore.download` |

```php
$document->url();                                         // attachment
route('docstore.download', ['id' => $document->id, 'inline' => 1]); // inline, PDF only
```

The controller:

1. loads the document through the configured model (404 if unknown);
2. asks the visibility resolver (403 if denied);
3. checks that the file exists on its disk (404 otherwise);
4. streams it with its stored MIME type, a properly encoded `Content-Disposition` header and `X-Content-Type-Options: nosniff`.

`?inline=1` is honoured **only for `application/pdf`**: other types are always sent as attachments, so a stored HTML or SVG file can never be rendered in the browser from your domain.

### Customising the route

Change `routes.prefix` or `routes.middleware` (for example to add `verified` or a custom guard), or set `routes.enabled` to `false` and register your own route. Keep the name `docstore.download` if you want `Document::url()` to keep working:

```php
Route::middleware(['web', 'auth:admin'])
    ->get('/admin/files/{id}', \LaurentMeuwly\Docstore\Http\Controllers\DocumentDownloadController::class)
    ->name('docstore.download');
```

## Access control

> **Important:** the default `AllowAllVisibilityResolver` lets **any authenticated user download any document**, simply by guessing its id. Replace it before going to production.

Implement the contract:

```php
namespace App\Docstore;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use LaurentMeuwly\Docstore\Contracts\DocumentVisibilityResolver;

class PolicyVisibilityResolver implements DocumentVisibilityResolver
{
    public function canAccess(Model $document, ?Authenticatable $user): bool
    {
        return $user !== null && $user->can('view', $document);
    }
}
```

and register it:

```php
'visibility' => [
    'resolver' => \App\Docstore\PolicyVisibilityResolver::class,
],
```

The resolver is resolved once per request through the container, so constructor dependency injection is available. A misconfigured class that does not implement the contract throws an `InvalidArgumentException`.

## Extending the models

```php
namespace App\Models;

use LaurentMeuwly\Docstore\Models\Document as BaseDocument;

class Document extends BaseDocument
{
    public function author()
    {
        return $this->belongsTo(User::class);
    }

    public function scopePdf($query)
    {
        return $query->where('mime_type', 'application/pdf');
    }
}
```

```php
'models' => [
    'document' => \App\Models\Document::class,
    'folder'   => \App\Models\Folder::class,
],
```

All package relations (`Document::folder()`, `Folder::parent()`, `Folder::children()`, `Folder::documents()`) and the download controller use the configured classes. The base models declare their table and foreign keys explicitly, so a subclass may have any class name (e.g. `ProjectFolder`) without extra configuration.

## Data model reference

### `Folder`

| Column      | Type                | Notes                            |
|-------------|---------------------|----------------------------------|
| `id`        | bigint              |                                  |
| `parent_id` | bigint, nullable    | `null` for root folders; set to `null` if the parent is deleted |
| `name`      | string, indexed     |                                  |
| `position`  | integer, default 0  | Sort order among siblings        |
| timestamps  |                     |                                  |

- Relations: `parent()`, `children()` (ordered by `position`, then `id`), `documents()`
- Scope: `Folder::root()` — folders without a parent
- Accessor: `full_path` — e.g. `"Clients / ACME / Invoices"`. Each ancestor is lazy-loaded: when listing many folders, prefer computing paths in a single pass.

### `Document`

| Column      | Type                         | Notes                                        |
|-------------|------------------------------|----------------------------------------------|
| `id`        | bigint                       |                                              |
| `folder_id` | bigint, nullable             | Set to `null` if the folder is deleted       |
| `title`     | string                       | Display title                                |
| `filename`  | string                       | Name proposed to the browser on download     |
| `mime_type` | string, default `application/pdf` | Sent as `Content-Type`                  |
| `disk`      | string, nullable             | Falls back to `docstore.storage_disk`        |
| `path`      | string, indexed              | Path relative to the disk root               |
| `meta`      | json, nullable               | Cast to array                                |
| `size`      | unsigned bigint, default 0   | Bytes                                        |
| timestamps  |                              |                                              |

- Relation: `folder()`
- Helpers: `url()`, `formatted_size` (`"1.5 MB"`), `formatted_date` (`"29.10.2026 14:30"`, based on `updated_at`)

Deleting a `Document` record does **not** delete the stored file; do it in your application (or in a model observer) if needed.

## Upgrading

See [CHANGELOG.md](CHANGELOG.md) for details.

**1.1 → 1.2** — requires Laravel 12 or 13. No code change is needed. Run `composer update laurentmeuwly/docstore`. Re-publishing the configuration (`php artisan docstore:install --force`) is optional; it only adds the new `routes` section, whose defaults reproduce the previous behaviour.

## Development

```bash
composer install
composer test        # Pest
composer analyse     # PHPStan / Larastan
composer lint        # Pint (check only)
composer format      # Pint (fix)
```

The GitHub Actions workflow runs the test suite on every supported PHP × Laravel combination.

## License

Docstore is open-source software licensed under the [MIT license](LICENSE.md).
