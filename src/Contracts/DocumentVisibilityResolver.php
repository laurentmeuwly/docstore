<?php

namespace LaurentMeuwly\Docstore\Contracts;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;

interface DocumentVisibilityResolver
{
    public function canAccess(Model $document, ?Authenticatable $user): bool;
}
