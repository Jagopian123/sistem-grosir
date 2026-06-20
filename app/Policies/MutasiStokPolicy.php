<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\MutasiStok;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class MutasiStokPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:MutasiStok');
    }

    public function view(AuthUser $authUser, MutasiStok $mutasiStok): bool
    {
        return $authUser->can('View:MutasiStok');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:MutasiStok');
    }

    public function update(AuthUser $authUser, MutasiStok $mutasiStok): bool
    {
        return $authUser->can('Update:MutasiStok');
    }

    public function delete(AuthUser $authUser, MutasiStok $mutasiStok): bool
    {
        return $authUser->can('Delete:MutasiStok');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:MutasiStok');
    }

    public function restore(AuthUser $authUser, MutasiStok $mutasiStok): bool
    {
        return $authUser->can('Restore:MutasiStok');
    }

    public function forceDelete(AuthUser $authUser, MutasiStok $mutasiStok): bool
    {
        return $authUser->can('ForceDelete:MutasiStok');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:MutasiStok');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:MutasiStok');
    }

    public function replicate(AuthUser $authUser, MutasiStok $mutasiStok): bool
    {
        return $authUser->can('Replicate:MutasiStok');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:MutasiStok');
    }
}
