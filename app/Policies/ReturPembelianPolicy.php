<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\ReturPembelian;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class ReturPembelianPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:ReturPembelian');
    }

    public function view(AuthUser $authUser, ReturPembelian $returPembelian): bool
    {
        return $authUser->can('View:ReturPembelian');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:ReturPembelian');
    }

    public function update(AuthUser $authUser, ReturPembelian $returPembelian): bool
    {
        return $authUser->can('Update:ReturPembelian');
    }

    public function delete(AuthUser $authUser, ReturPembelian $returPembelian): bool
    {
        return $authUser->can('Delete:ReturPembelian');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:ReturPembelian');
    }

    public function restore(AuthUser $authUser, ReturPembelian $returPembelian): bool
    {
        return $authUser->can('Restore:ReturPembelian');
    }

    public function forceDelete(AuthUser $authUser, ReturPembelian $returPembelian): bool
    {
        return $authUser->can('ForceDelete:ReturPembelian');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:ReturPembelian');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:ReturPembelian');
    }

    public function replicate(AuthUser $authUser, ReturPembelian $returPembelian): bool
    {
        return $authUser->can('Replicate:ReturPembelian');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:ReturPembelian');
    }
}
