# Docstore
Un mini-package Laravel pour gérer des documents et dossiers (upload, stockage, organisation, accès, téléchargement), extensible selon les besoins de votre application.

---

## Fonctionnalités

- Gestion de **dossiers hiérarchiques** (parents/enfants)  
- Gestion de **documents** (titre, fichier, métadonnées, taille, type MIME…)  
- Stockage via le **Laravel Filesystem** (local, S3, etc.)  
- Téléchargement sécurisé via un **resolver de visibilité**  
- Architecture entièrement **extensible** :  
  - surcharge des modèles  
  - surcharge de la logique métier  
  - surcharge de la logique d’accès (`DocumentVisibilityResolver`)  
- Intégration simple dans tout projet Laravel 10 ou 11  
- Compatible PHP **>= 8.2**

---

## Installation

```
composer require laurentmeuwly/docstore
```

Puis publier la configuration :

```
php artisan docstore:install
```

Si vous souhaitez publier les migrations :

```
php artisan docstore:install --migrations
```

## Configuration

Le fichier publié config/docstore.php contient :

```
return [
    'storage_disk' => 'local',    // disque utilisé pour stocker les fichiers
    'base_path' => 'docstore',    // dossier racine dans le disque

    'visibility' => [
        'resolver' => null,       // classe de résolution d'accès (null => accès autorisé)
    ],

    'models' => [
        'document' => \LaurentMeuwly\Docstore\Models\Document::class,
        'folder'   => \LaurentMeuwly\Docstore\Models\Folder::class,
    ],
];
```

## Modèles Eloquent

Le package fournit deux modèles :

Folder

id

parent_id

name

order

relations : parent, children, documents

Document

id

folder_id

title

filename

disk

path

mime_type

size

meta (JSON)

relations : folder

méthodes utiles :

url() → URL de téléchargement

formatted_size

formatted_date

Surcharger les modèles dans votre application

Si vous souhaitez ajouter vos propres relations, règles métier ou traductions, créez vos modèles dans app/Models.

Exemple : app/Models/Document.php

namespace App\Models;

class Document extends \LaurentMeuwly\Docstore\Models\Document
{
    // Ajoutez vos propres propriétés, casts, scopes, relations, etc.
}


Puis dans config/docstore.php :

'models' => [
    'document' => \App\Models\Document::class,
    'folder'   => \App\Models\Folder::class,
],


Toutes les relations du package (Folder→Document, etc.) utiliseront automatiquement vos modèles.

## Gestion des accès (Visibility Resolver)

Par défaut, tout le monde peut télécharger tous les documents.

Pour restreindre l'accès, implémentez le contrat :

use LaurentMeuwly\Docstore\Contracts\DocumentVisibilityResolver;
use LaurentMeuwly\Docstore\Models\Document;
use Illuminate\Contracts\Auth\Authenticatable;

class ProcoradVisibilityResolver implements DocumentVisibilityResolver
{
    public function canAccess(Document $document, ?Authenticatable $user): bool
    {
        // Votre logique métier :
        // labo, rôle, année, permissions, etc.
        return $user?->isAdmin() ?? false;
    }
}


Puis configurez-le :

'visibility' => [
    'resolver' => \App\Services\ProcoradVisibilityResolver::class,
],


À chaque téléchargement, le contrôleur appelle :

$resolver->canAccess($document, auth()->user());

### Téléchargement de fichier

Le package expose une route :

GET /docstore/{document}/download


Elle retourne un téléchargement seulement si le resolver valide l'accès.

Pour obtenir l’URL d’un document :

$url = $document->url();

### Ajout de documents (exemple)

Voici un exemple d’upload simple :

$file = $request->file('file');

$path = $file->store(config('docstore.base_path'), config('docstore.storage_disk'));

$documentClass = config('docstore.models.document');

$document = $documentClass::create([
    'folder_id' => $folder->id,
    'title'     => $file->getClientOriginalName(),
    'filename'  => $file->getClientOriginalName(),
    'mime_type' => $file->getMimeType(),
    'size'      => $file->getSize(),
    'disk'      => config('docstore.storage_disk'),
    'path'      => $path,
]);

## Tests

Le package utilise Pest et PHPUnit.

Pour lancer les tests :

composer test

Les tests utilisent Orchestra Testbench pour charger Laravel en mode package.

## Commandes artisan

php artisan docstore:install

Publie :

configuration (docstore-config)

migrations (--migrations)

## Architecture interne
src/
  Contracts/
    DocumentVisibilityResolver.php
  Models/
    Document.php
    Folder.php
  Services/
    AllowAllVisibilityResolver.php
  Http/
    Controllers/
      DocumentDownloadController.php
  Console/
    InstallCommand.php
  DocstoreServiceProvider.php
config/
  docstore.php
database/
  migrations/
routes/
  web.php

## Contribution

Les contributions sont les bienvenues !

Forkez le dépôt

Créez une branche (feature/ma-fonctionnalite)

Ajoutez vos tests

Exécutez Pint, PHPStan et PHPUnit

Ouvrez une Pull Request

## Licence

Ce package est distribué sous licence MIT.

## Support

Pour toute question, vous pouvez ouvrir une issue GitHub ou contacter l’auteur du package.
