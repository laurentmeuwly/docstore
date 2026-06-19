<?php

namespace LaurentMeuwly\Docstore\Services;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use LaurentMeuwly\Docstore\Contracts\DocumentVisibilityResolver;

class AllowAllVisibilityResolver implements DocumentVisibilityResolver
{
    public function canAccess(Model $document, ?Authenticatable $user): bool
    {
        return true;
    }
}
