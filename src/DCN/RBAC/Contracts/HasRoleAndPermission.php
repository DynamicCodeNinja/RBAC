<?php

namespace DCN\RBAC\Contracts;

use Illuminate\Database\Eloquent\Model;

interface HasRoleAndPermission
{
    /**
     * User belongs to many roles.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function roles();

    /**
     * Get all roles as collection.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getRoles();

    /**
     * Check if the user has a role or roles.
     *
     * @param int|string|array $role
     * @param bool $all
     * @return bool
     */
    public function roleIs($role, $all = false);

    /**
     * Attach role to a user.
     *
     * @param int|\DCN\RBAC\Models\Role $role
     * @param bool $granted
     * @return bool|null
     */
    public function attachRole($role, $granted = TRUE);

    /**
     * Detach role from a user.
     *
     * @param int|\DCN\RBAC\Models\Role $role
     * @return int
     */
    public function detachRole($role);

    /**
     * Detach all roles from a user.
     *
     * @return int
     */
    public function detachAllRoles();

    /**
     * Get all permissions from roles.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function rolePermissions();

    /**
     * User belongs to many permissions.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function userPermissions();

    /**
     * Get all permissions as collection.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getPermissions();

    /**
     * Check if the user has a permission or permissions.
     *
     * @param int|string|array $permission
     * @param bool $all
     * @return bool
     */
    public function may($permission, $all = false);

    /**
     * Check if the user is allowed to manipulate with entity.
     *
     * @param string $providedPermission
     * @param \Illuminate\Database\Eloquent\Model $entity
     * @param bool $owner
     * @param string $ownerColumn
     * @return bool
     */
    public function allowed($providedPermission, Model $entity, $owner = true, $ownerColumn = 'user_id');

    /**
     * Attach permission to a user.
     *
     * @param int|\DCN\RBAC\Models\Permission $permission
     * @param bool $granted
     * @return bool|null
     */
    public function attachPermission($permission, $granted = TRUE);

    /**
     * Detach permission from a user.
     *
     * @param int|\DCN\RBAC\Models\Permission $permission
     * @return int
     */
    public function detachPermission($permission);

    /**
     * Detach all permissions from a user.
     *
     * @return int
     */
    public function detachAllPermissions();
}
