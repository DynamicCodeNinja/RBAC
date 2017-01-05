## [No Longer Maintained, Pull Requests Accepted!]
# RBAC For Laravel 5.3
Powerful package for handling roles and permissions in Laravel 5.3

Based on the [Bican/Roles](https://github.com/romanbican/roles/) Package.

### So whats Different?

The difference is how [Inheritance](#inheritance) work. With Bican/Roles, permissions are inherited based on your highest `role level`.

Instead this package uses a `parent_id` column to enable roles to be inherited from each other. 

This enables us to only pull permissions of roles that our users inherits, or that are directly assigned to the user.


- [Installation](#installation)
    - [Composer](#composer)
    - [Service Provider](#service-provider)
    - [Config File And Migrations](#config-file-and-migrations)
    - [HasRoleAndPermission Trait And Contract](#hasroleandpermission-trait-and-contract)
- [Usage](#usage)
    - Roles
        - [Creating Roles](#creating-roles)
        - [Attaching And Detaching Roles](#attaching-and-detaching-roles)
        - [Deny Roles](#deny-roles)
        - [Checking For Roles](#checking-for-roles)
    - Permissions
        - [Creating Permissions](#creating-permissions)
        - [Attaching And Detaching Permissions](#attaching-and-detaching-permissions)
        - [Deny Permissions](#deny-permissions)
        - [Checking For Permissions](#checking-for-permissions)
    - [Inheritance](#inheritance)
    - [Entity Check](#entity-check)
    - [Blade Extensions](#blade-extensions)
    - [Middleware](#middleware)
- [Config File](#config-file)
- [More Information](#more-information)
- [License](#license)

## Installation

This package is very easy to set up. There are only couple of steps.

### Composer

Pull this package in through Composer (file `composer.json`).

```js
{
    "require": {
        "php": ">=5.5.9",
        "laravel/framework": "5.1.*",
        "dcn/rbac": "~1.1.0"
    }
}
```

Run this command inside your terminal.

    composer update

### Service Provider

Add the package to your application service providers in `config/app.php` file.

```php
'providers' => [
    
    /*
     * Laravel Framework Service Providers...
     */
    Illuminate\Foundation\Providers\ArtisanServiceProvider::class,
    Illuminate\Auth\AuthServiceProvider::class,
    ...
    
    /**
     * Third Party Service Providers...
     */
    DCN\RBAC\RBACServiceProvider::class,

],
```

### Config File And Migrations

Publish the package config file and migrations to your application. Run these commands inside your terminal.

    php artisan vendor:publish --provider="DCN\RBAC\RBACServiceProvider" --tag=config
    php artisan vendor:publish --provider="DCN\RBAC\RBACServiceProvider" --tag=migrations

And also run migrations.

    php artisan migrate

> There must be created migration file for users table, which is in Laravel out of the box.

### HasRoleAndPermission Trait And Contract

Include `HasRoleAndPermission` trait and also implement `HasRoleAndPermission` contract inside your `User` model.

```php
use DCN\RBAC\Traits\HasRoleAndPermission;
use DCN\RBAC\Contracts\HasRoleAndPermission as HasRoleAndPermissionContract;

class User extends Model implements AuthenticatableContract, CanResetPasswordContract, HasRoleAndPermissionContract
{
    use Authenticatable, CanResetPassword, HasRoleAndPermission;
```

And that's it!

## Usage

### Creating Roles

```php
use DCN\RBAC\Models\Role;

$adminRole = Role::create([
    'name' => 'Admin',
    'slug' => 'admin',
    'description' => '', // optional
    'parent_id' => NULL, // optional, set to NULL by default
]);

$moderatorRole = Role::create([
    'name' => 'Forum Moderator',
    'slug' => 'forum.moderator',
]);
```

> Because of `Slugable` trait, if you make a mistake and for example leave a space in slug parameter, it'll be replaced with a dot automatically, because of `str_slug` function.

### Attaching And Detaching Roles

It's really simple. You fetch a user from database and call `attachRole` method. There is `BelongsToMany` relationship between `User` and `Role` model.

```php
use App\User;

$user = User::find($id);

$user->attachRole($adminRole); //you can pass whole object, or just an id
```

```php
$user->detachRole($adminRole); // in case you want to detach role
$user->detachAllRoles(); // in case you want to detach all roles
```

### Deny Roles

To deny a user a role and all of its children roles, see the following example.

We recommend that you plan your roles accordingly if you plan on using this feature. As you could easily lock out users without realizing it.

```php
use App\User;

$role = Role::find($roleId);

$user = User::find($userId);
$user->attachRole($role, FALSE); // Deny this role, and all of its decedents to the user regardless of what has been assigned.
```

### Checking For Roles

You can now check if the user has required role.

```php
if ($user->roleIs('admin')) { // you can pass an id or slug
    //
}
```

You can also do this:

```php
if ($user->isAdmin()) {
    //
}
```

And of course, there is a way to check for multiple roles:

```php
if ($user->roleIs('admin|moderator')) { // or $user->roleIs('admin, moderator') and also $user->roleIs(['admin', 'moderator'])
    // if user has at least one role
}

if ($user->roleIs('admin|moderator', true)) { // or $user->roleIs('admin, moderator', true) and also $user->roleIs(['admin', 'moderator'], true)
    // if user has all roles
}
```

As well as Wild Cards:

```php
if ($user->roleIs('admin|moderator.*')) { // or $user->roleIs('admin, moderator.*') and also $user->roleIs(['admin', 'moderator.*'])
    //User has admin role, or a moderator role
}

```

### Creating Permissions

It's very simple thanks to `Permission` model.

```php
use DCN\RBAC\Models\Permission;

$createUsersPermission = Permission::create([
    'name' => 'Create users',
    'slug' => 'create.users',
    'description' => '', // optional
]);

$deleteUsersPermission = Permission::create([
    'name' => 'Delete users',
    'slug' => 'delete.users',
]);
```

### Attaching And Detaching Permissions

You can attach permissions to a role or directly to a specific user (and of course detach them as well).

```php
use App\User;
use DCN\RBAC\Models\Role;

$role = Role::find($roleId);
$role->attachPermission($createUsersPermission); // permission attached to a role

$user = User::find($userId);
$user->attachPermission($deleteUsersPermission); // permission attached to a user
```

```php
$role->detachPermission($createUsersPermission); // in case you want to detach permission
$role->detachAllPermissions(); // in case you want to detach all permissions

$user->detachPermission($deleteUsersPermission);
$user->detachAllPermissions();
```

### Deny Permissions

You can deny a user a permission, or you can deny an entire role a permission.

To do this, when attaching a permission simply pass a second parameter of false. 
This will deny that user that permission regardless of what they are assigned.
Denied permissions take precedent over inherited and granted permissions. 

```php
use App\User;
use DCN\RBAC\Models\Role;

$role = Role::find($roleId);
$role->attachPermission($createUsersPermission, FALSE); // Deny this permission to all users who have or inherit this role.

$user = User::find($userId);
$user->attachPermission($deleteUsersPermission, FALSE); // Deny this permission to this user regardless of what roles they are in.
```

### Checking For Permissions

```php
if ($user->may('create.users') { // you can pass an id or slug
    //
}

if ($user->canDeleteUsers()) {
    //
}
```

You can check for multiple permissions the same way as roles.

### Inheritance

> If you don't want the inheritance feature in you application, simply ignore the `parent_id` parameter when you're creating roles.

Roles that are assigned a parent_id of another role are automatically inherited when a user is assigned or inherits the parent role.

Here is an example:

You have 5 administrative groups. Admins, Store Admins, Store Inventory Managers, Blog Admins, and Blog Writers.

Role                       | Parent       |
-----------                | -----------  |
Admins                     |              |
Store Admins               | Admins       |
Store Inventory Managers   | Store Admins |
Blog Admins                | Admins       |
Blog Writers               | Blog Admins  |

The `Admins Role` is the parent of both `Store Admins Role` as well as `Blog Admins Role`.

While the `Store Admins Role` is the parent to `Store Inventory Managers Role`.

And the `Blog Admins Role` is the parent to `Blog Writers`.

This enables the `Admins Role` to inherit both `Store Inventory Managers Role` and `Blog Writers Role`.

But the `Store Admins Role` only inherits the `Store Inventory Managers Role`,

And the `Blog Admins Role` only inherits the `Blog Writers Role`.


Another Example:

id  | slug        | parent_id   |
--- | ----------- | ----------- |
1   | admin       | NULL        |
2   | admin.user  | 1           |
3   | admin.blog  | 1           |
4   | blog.writer | 3           |
5   | development | NULL        |

Here, 
`admin` inherits `admin.user`, `admin.blog`, and `blog.writer`.

While `admin.user` doesn't inherit anything, and `admin.blog` inherits `blog.writer`.

Nothing inherits `development` and, `development` doesn't inherit anything.


### Entity Check

Let's say you have an article and you want to edit it. This article belongs to a user (there is a column `user_id` in articles table).

```php
use App\Article;
use DCN\RBAC\Models\Permission;

$editArticlesPermission = Permission::create([
    'name' => 'Edit articles',
    'slug' => 'edit.articles',
    'model' => 'App\Article',
]);

$user->attachPermission($editArticlesPermission);

$article = Article::find(1);

if ($user->allowed('edit.articles', $article)) { // $user->allowedEditArticles($article)
    //
}
```

This condition checks if the current user is the owner of article. If not, it will be looking inside user permissions for a row we created before.

```php
if ($user->allowed('edit.articles', $article, false)) { // now owner check is disabled
    //
}
```

### Blade Extensions

There are three Blade extensions. Basically, it is replacement for classic if statements.

```php
@role('admin') // @if(Auth::check() && Auth::user()->roleIs('admin'))
    // user is admin
@endrole

@permission('edit.articles') // @if(Auth::check() && Auth::user()->may('edit.articles'))
    // user can edit articles
@endpermission

@allowed('edit', $article) // @if(Auth::check() && Auth::user()->allowed('edit', $article))
    // show edit button
@endallowed

@role('admin|moderator', 'all') // @if(Auth::check() && Auth::user()->roleIs('admin|moderator', 'all'))
    // user is admin and also moderator
@else
    // something else
@endrole
```

### Middleware

This package comes with `VerifyRole` and `VerifyPermission` middleware. You must add them inside your `app/Http/Kernel.php` file.

```php
/**
 * The application's route middleware.
 *
 * @var array
 */
protected $routeMiddleware = [
    'auth' => \App\Http\Middleware\Authenticate::class,
    'auth.basic' => \Illuminate\Auth\Middleware\AuthenticateWithBasicAuth::class,
    'guest' => \App\Http\Middleware\RedirectIfAuthenticated::class,
    'role' => \DCN\RBAC\Middleware\VerifyRole::class,
    'permission' => \DCN\RBAC\Middleware\VerifyPermission::class,
];
```

Now you can easily protect your routes.

```php
$router->get('/example', [
    'as' => 'example',
    'middleware' => 'role:admin',
    'uses' => 'ExampleController@index',
]);

$router->post('/example', [
    'as' => 'example',
    'middleware' => 'permission:edit.articles',
    'uses' => 'ExampleController@index',
]);
```

It throws `\DCN\RBAC\Exception\RoleDeniedException` or `\DCN\RBAC\Exception\PermissionDeniedException` exceptions if it goes wrong.

You can catch these exceptions inside `app/Exceptions/Handler.php` file and do whatever you want.

```php
/**
 * Render an exception into an HTTP response.
 *
 * @param  \Illuminate\Http\Request  $request
 * @param  \Exception  $e
 * @return \Illuminate\Http\Response
 */
public function render($request, Exception $e)
{
    if ($e instanceof \DCN\RBAC\Exceptions\RoleDeniedException) {
        // you can for example flash message, redirect...
        return redirect()->back();
    }

    return parent::render($request, $e);
}
```

## Config File

You can change connection for models, slug separator, models path and there is also a handy pretend feature. Have a look at config file for more information.

## More Information

This project is based on [Bican/Roles](https://github.com/romanbican/roles/).

## License

This package is free software distributed under the terms of the MIT license.

I don't care what you do with it.

## Contribute

I honestly don't know what I'm doing. If you see something that could be fixed. Make a pull request on the develop branch!.
