<?php

namespace LaurentMeuwly\Docstore\Tests\Fixtures;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use LaurentMeuwly\Docstore\Contracts\DocumentVisibilityResolver;

class DenyAllResolver implements DocumentVisibilityResolver
{
    public function canAccess(Model $document, ?Authenticatable $user): bool
    {
        return false;
    }
}
