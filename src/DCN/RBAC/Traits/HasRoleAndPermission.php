<?php

namespace DCN\RBAC\Traits;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;

trait HasRoleAndPermission
{
    /**
     * Property for caching roles.
     *
     * @var \Illuminate\Database\Eloquent\Collection|null
     */
    protected $roles;

    /**
     * Property for caching permissions.
     *
     * @var \Illuminate\Database\Eloquent\Collection|null
     */
    protected $permissions;

    /**
     * User belongs to many roles.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function roles()
    {
        return $this->belongsToMany(config('rbac.models.role'))->withTimestamps()->withPivot('granted');
    }

    /**
     * Get only Granted Roles
     */
    public function grantedRoles() {
        return $this->roles()->wherePivot('granted', true);
    }

    /**
     * Get only Denied Roles
     */
    public function deniedRoles() {
        return $this->roles()->wherePivot('granted', false);
    }

    /**
     * User belongs to many permissions.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function userPermissions()
    {
        return $this->belongsToMany(config('rbac.models.permission'))->withTimestamps()->withPivot('granted');
    }

    /**
     * Get only Granted Permissions
     */
    public function grantedPermissions() {
        return $this->userPermissions()->wherePivot('granted', true);
    }

    /**
     * Get only Denied Permissions
     */
    public function deniedPermissions() {
        return $this->userPermissions()->wherePivot('granted', false);
    }

    /**
     * Get all roles as collection.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getRoles()
    {
        if(!$this->roles){
            $this->roles = $this->grantedRoles()->get();

            $deniedRoles = $this->deniedRoles()->get();
            foreach($deniedRoles as $role)
                $deniedRoles = $deniedRoles->merge($role->descendants());

            foreach($this->roles as $role)
                if(!$deniedRoles->contains($role))
                    $this->roles = $this->roles->merge($role->descendants());

            $this->roles = $this->roles->filter(function($role) use ($deniedRoles){
                return !$deniedRoles->contains($role);
            });
        }
        return  $this->roles;
    }

    /**
     * Get all permissions from roles.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function rolePermissions()
    {

        $permissions = new Collection();
        foreach ($this->getRoles() as $role)
            $permissions = $permissions->merge($role->permissions);
        return $permissions;
    }

    /**
     * Get all permissions as collection.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getPermissions()
    {
        if(!$this->permissions){
            $rolePermissions = $this->rolePermissions();
            $userPermissions = $this->grantedPermissions()->get();

            $permissions = $rolePermissions->merge($userPermissions);
            $deniedPermissions =$this->deniedPermissions()->get();

            $this->permissions = $permissions->filter(function($permission) use ($deniedPermissions)
            {
                return !$deniedPermissions->contains($permission);
            });
        }
        return $this->permissions;
    }
    /**
     * Check if the user has a role or roles.
     *
     * @param int|string|array $role
     * @param bool $all
     * @return bool
     */
    public function is($role, $all = false)
    {
        if ($this->isPretendEnabled()) {
            return $this->pretend('is');
        }

        return $this->{$this->getMethodName('is', $all)}($this->getArrayFrom($role));
    }

    /**
     * Check if the user has at least one role.
     *
     * @param array $roles
     * @return bool
     */
    protected function isOne(array $roles)
    {
        foreach ($roles as $role) {
            if ($this->hasRole($role)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if the user has all roles.
     *
     * @param array $roles
     * @return bool
     */
    protected function isAll(array $roles)
    {
        foreach ($roles as $role) {
            if (!$this->hasRole($role)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Check if the user has role.
     *
     * @param int|string $role
     * @return bool
     */
    protected function hasRole($role)
    {
        return $this->getRoles()->contains(function ($key, $value) use ($role) {
            return $role == $value->id || Str::is($role, $value->slug);
        });
    }
    
    /**
     * Check if the user has a permission or permissions.
     *
     * @param int|string|array $permission
     * @param bool $all
     * @return bool
     */
    public function can($permission, $all = false)
    {
        if ($this->isPretendEnabled()) {
            return $this->pretend('can');
        }

        return $this->{$this->getMethodName('can', $all)}($this->getArrayFrom($permission));
    }

    /**
     * Check if the user has at least one permission.
     *
     * @param array $permissions
     * @return bool
     */
    protected function canOne(array $permissions)
    {
        foreach ($permissions as $permission) {
            if ($this->hasPermission($permission)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if the user has all permissions.
     *
     * @param array $permissions
     * @return bool
     */
    protected function canAll(array $permissions)
    {
        foreach ($permissions as $permission) {
            if (!$this->hasPermission($permission)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Check if the user has a permission.
     *
     * @param int|string $permission
     * @return bool
     */
    protected function hasPermission($permission)
    {
        return $this->getPermissions()->contains(function ($key, $value) use ($permission) {
            return $permission == $value->id || Str::is($permission, $value->slug);
        });
    }

    /**
     * Check if the user is allowed to manipulate with entity.
     *
     * @param string $providedPermission
     * @param \Illuminate\Database\Eloquent\Model $entity
     * @param bool $owner
     * @param string $ownerColumn
     * @return bool
     */
    public function allowed($providedPermission, Model $entity, $owner = true, $ownerColumn = 'user_id')
    {
        if ($this->isPretendEnabled()) {
            return $this->pretend('allowed');
        }

        if ($owner === true && $entity->{$ownerColumn} == $this->id) {
            return true;
        }

        return $this->isAllowed($providedPermission, $entity);
    }

    /**
     * Check if the user is allowed to manipulate with provided entity.
     *
     * @param string $providedPermission
     * @param \Illuminate\Database\Eloquent\Model $entity
     * @return bool
     */
    protected function isAllowed($providedPermission, Model $entity)
    {
        foreach ($this->getPermissions() as $permission) {
            if ($permission->model != '' && get_class($entity) == $permission->model
                && ($permission->id == $providedPermission || $permission->slug === $providedPermission)
            ) {
                return true;
            }
        }

        return false;
    }

    /**
     * Attach role to a user.
     *
     * @param int|\DCN\RBAC\Models\Role $role
     * @param bool $granted
     * @return bool|null
     */
    public function attachRole($role, $granted = TRUE)
    {
        if($granted)
            return (!$this->grantedRoles()->get()->contains($role)) ? $this->roles()->attach($role, array('granted' => TRUE)) : true;
        else
            return (!$this->deniedRoles()->get()->contains($role)) ? $this->roles()->attach($role, array('granted' => FALSE)) : true;
    }

    /**
     * Detach role from a user.
     *
     * @param int|\DCN\RBAC\Models\Role $role
     * @return int
     */
    public function detachRole($role)
    {
        return $this->roles()->detach($role);
    }

    /**
     * Detach all roles from a user.
     *
     * @return int
     */
    public function detachAllRoles()
    {
        return $this->roles()->detach();
    }

    /**
     * Attach permission to a user.
     *
     * @param int|\DCN\RBAC\Models\Permission $permission
     * @param bool $granted
     * @return bool|null
     */
    public function attachPermission($permission, $granted = true)
    {
        if($granted)
            return (!$this->grantedPermissions()->get()->contains($permission)) ? $this->userPermissions()->attach($permission, array('granted' => TRUE)) : true;
        else
            return (!$this->deniedPermissions()->get()->contains($permission)) ? $this->userPermissions()->attach($permission, array('granted' => FALSE)) : true;

    }

    /**
     * Detach permission from a user.
     *
     * @param int|\DCN\RBAC\Models\Permission $permission
     * @return int
     */
    public function detachPermission($permission)
    {
        return $this->userPermissions()->detach($permission);
    }

    /**
     * Detach all permissions from a user.
     *
     * @return int
     */
    public function detachAllPermissions()
    {
        return $this->userPermissions()->detach();
    }

    /**
     * Check if pretend option is enabled.
     *
     * @return bool
     */
    private function isPretendEnabled()
    {
        return (bool) config('roles.pretend.enabled');
    }

    /**
     * Allows to pretend or simulate package behavior.
     *
     * @param string $option
     * @return bool
     */
    private function pretend($option)
    {
        return (bool) config('roles.pretend.options.' . $option);
    }

    /**
     * Get method name.
     *
     * @param string $methodName
     * @param bool $all
     * @return string
     */
    private function getMethodName($methodName, $all)
    {
        return ((bool) $all) ? $methodName . 'All' : $methodName . 'One';
    }

    /**
     * Get an array from argument.
     *
     * @param int|string|array $argument
     * @return array
     */
    private function getArrayFrom($argument)
    {
        return (!is_array($argument)) ? preg_split('/ ?[,|] ?/', $argument) : $argument;
    }

    /**
     * Handle dynamic method calls.
     *
     * @param string $method
     * @param array $parameters
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        if (starts_with($method, 'is')) {
            return $this->is(snake_case(substr($method, 2), config('roles.separator')));
        } elseif (starts_with($method, 'can')) {
            return $this->can(snake_case(substr($method, 3), config('roles.separator')));
        } elseif (starts_with($method, 'allowed')) {
            return $this->allowed(snake_case(substr($method, 7), config('roles.separator')), $parameters[0], (isset($parameters[1])) ? $parameters[1] : true, (isset($parameters[2])) ? $parameters[2] : 'user_id');
        }

        return parent::__call($method, $parameters);
    }
}
