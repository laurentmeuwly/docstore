<?php

namespace LaurentMeuwly\Docstore\Services;

use Illuminate\Contracts\Auth\Authenticatable;
use LaurentMeuwly\Docstore\Contracts\DocumentVisibilityResolver;
use LaurentMeuwly\Docstore\Models\Document;

class AllowAllVisibilityResolver implements DocumentVisibilityResolver
{
    public function canAccess(Document $document, ?Authenticatable $user): bool
    {
        return true;
    }
}
