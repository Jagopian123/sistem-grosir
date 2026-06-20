<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\ReturPenjualan;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class ReturPenjualanPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:ReturPenjualan');
    }

    public function view(AuthUser $authUser, ReturPenjualan $returPenjualan): bool
    {
        return $authUser->can('View:ReturPenjualan');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:ReturPenjualan');
    }

    public function update(AuthUser $authUser, ReturPenjualan $returPenjualan): bool
    {
        return $authUser->can('Update:ReturPenjualan');
    }

    public function delete(AuthUser $authUser, ReturPenjualan $returPenjualan): bool
    {
        return $authUser->can('Delete:ReturPenjualan');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:ReturPenjualan');
    }

    public function restore(AuthUser $authUser, ReturPenjualan $returPenjualan): bool
    {
        return $authUser->can('Restore:ReturPenjualan');
    }

    public function forceDelete(AuthUser $authUser, ReturPenjualan $returPenjualan): bool
    {
        return $authUser->can('ForceDelete:ReturPenjualan');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:ReturPenjualan');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:ReturPenjualan');
    }

    public function replicate(AuthUser $authUser, ReturPenjualan $returPenjualan): bool
    {
        return $authUser->can('Replicate:ReturPenjualan');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:ReturPenjualan');
    }
}
