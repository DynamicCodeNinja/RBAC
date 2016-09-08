<?php

namespace DCN\RBAC\Middleware;

use Closure;
use Illuminate\Contracts\Auth\Guard;
use DCN\RBAC\Exceptions\PermissionDeniedException;

class VerifyPermission
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
     * @param int|string $permission
     * @return mixed
     * @throws \DCN\RBAC\Exceptions\PermissionDeniedException
     */
    public function handle($request, Closure $next, $permission)
    {
        if ($this->auth->check() && $this->auth->user()->may($permission)) {
            return $next($request);
        }

        throw new PermissionDeniedException($permission);
    }
}
