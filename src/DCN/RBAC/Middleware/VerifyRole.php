<?php

namespace DCN\RBAC\Middleware;

use Closure;
use Illuminate\Contracts\Auth\Guard;
use DCN\RBAC\Exceptions\RoleDeniedException;

class VerifyRole
{
    /**
     * @var \Illuminate\Contracts\Auth\Guard
     */
    protected $auth;

    /**
     * Create a new filter instance.
     *
     * @param \Illuminate\Contracts\Auth\Guard $auth
     */
    public function __construct(Guard $auth)
    {
        $this->auth = $auth;
    }

    /**
     * Handle an incoming request.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Closure $next
     * @param int|string $role
     * @return mixed
     * @throws \DCN\RBAC\Exceptions\RoleDeniedException
     */
    public function handle($request, Closure $next, ...$roles)
    {
        $all = filter_var(array_values(array_slice($roles, -1))[0], FILTER_VALIDATE_BOOLEAN);
        if ($this->auth->check() && $this->auth->user()->roleIs($roles, $all)) {
            return $next($request);
        }

        throw new RoleDeniedException(implode(',', $roles));
    }
}
