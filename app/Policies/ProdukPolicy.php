<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Produk;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class ProdukPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:Produk');
    }

    public function view(AuthUser $authUser, Produk $produk): bool
    {
        return $authUser->can('View:Produk');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:Produk');
    }

    public function update(AuthUser $authUser, Produk $produk): bool
    {
        return $authUser->can('Update:Produk');
    }

    public function delete(AuthUser $authUser, Produk $produk): bool
    {
        return $authUser->can('Delete:Produk');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:Produk');
    }

    public function restore(AuthUser $authUser, Produk $produk): bool
    {
        return $authUser->can('Restore:Produk');
    }

    public function forceDelete(AuthUser $authUser, Produk $produk): bool
    {
        return $authUser->can('ForceDelete:Produk');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:Produk');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:Produk');
    }

    public function replicate(AuthUser $authUser, Produk $produk): bool
    {
        return $authUser->can('Replicate:Produk');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:Produk');
    }
}
