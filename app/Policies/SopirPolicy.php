<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Sopir;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class SopirPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:Sopir');
    }

    public function view(AuthUser $authUser, Sopir $sopir): bool
    {
        return $authUser->can('View:Sopir');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:Sopir');
    }

    public function update(AuthUser $authUser, Sopir $sopir): bool
    {
        return $authUser->can('Update:Sopir');
    }

    public function delete(AuthUser $authUser, Sopir $sopir): bool
    {
        return $authUser->can('Delete:Sopir');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:Sopir');
    }

    public function restore(AuthUser $authUser, Sopir $sopir): bool
    {
        return $authUser->can('Restore:Sopir');
    }

    public function forceDelete(AuthUser $authUser, Sopir $sopir): bool
    {
        return $authUser->can('ForceDelete:Sopir');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:Sopir');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:Sopir');
    }

    public function replicate(AuthUser $authUser, Sopir $sopir): bool
    {
        return $authUser->can('Replicate:Sopir');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:Sopir');
    }
}
