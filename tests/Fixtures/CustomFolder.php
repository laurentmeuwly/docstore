<?php

namespace LaurentMeuwly\Docstore\Tests\Fixtures;

use LaurentMeuwly\Docstore\Models\Folder;

/**
 * Mirrors how consuming applications extend Folder, configured via
 * `docstore.models.folder`. Used to assert that relations defined on
 * Folder resolve to this subclass rather than the base Folder class.
 */
class CustomFolder extends Folder {}
