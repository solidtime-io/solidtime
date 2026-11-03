<?php

declare(strict_types=1);

namespace App\Exceptions;

use Exception;

/**
 * Thrown by listeners of the MemberAdding event to prevent a member from being added to an organization.
 */
class MemberCanNotBeAddedException extends Exception {}
