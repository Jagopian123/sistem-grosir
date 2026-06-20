<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\BatchStok;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class BatchStokPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:BatchStok');
    }

    public function view(AuthUser $authUser, BatchStok $batchStok): bool
    {
        return $authUser->can('View:BatchStok');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:BatchStok');
    }

    public function update(AuthUser $authUser, BatchStok $batchStok): bool
    {
        return $authUser->can('Update:BatchStok');
    }

    public function delete(AuthUser $authUser, BatchStok $batchStok): bool
    {
        return $authUser->can('Delete:BatchStok');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:BatchStok');
    }

    public function restore(AuthUser $authUser, BatchStok $batchStok): bool
    {
        return $authUser->can('Restore:BatchStok');
    }

    public function forceDelete(AuthUser $authUser, BatchStok $batchStok): bool
    {
        return $authUser->can('ForceDelete:BatchStok');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:BatchStok');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:BatchStok');
    }

    public function replicate(AuthUser $authUser, BatchStok $batchStok): bool
    {
        return $authUser->can('Replicate:BatchStok');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:BatchStok');
    }
}
