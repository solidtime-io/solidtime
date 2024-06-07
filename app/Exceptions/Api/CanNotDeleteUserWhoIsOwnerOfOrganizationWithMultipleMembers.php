<?php

declare(strict_types=1);

namespace App\Exceptions\Api;

class CanNotDeleteUserWhoIsOwnerOfOrganizationWithMultipleMembers extends ApiException
{
    public const string KEY = 'can_not_delete_user_who_is_owner_of_organization_with_multiple_members';
}
