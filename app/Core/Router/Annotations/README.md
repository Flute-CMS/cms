# Attribute-Based Routing in Flute CMS

This feature allows you to define routes directly on controller methods using PHP 8 attributes, making your code more concise and maintainable.

## Basic Usage

```php
use Flute\Core\Router\Annotations\Get;
use Flute\Core\Router\Annotations\Post;
use Flute\Core\Router\Annotations\Middleware;

class UserController
{
    #[Get('/users', name: 'users.index')]
    public function index()
    {
        // Handle GET request to /users
    }
    
    #[Post('/users', name: 'users.store')]
    #[Middleware('csrf')]
    public function store()
    {
        // Handle POST request to /users
    }
}
```

## Available Route Attributes

- `#[Route('/path', methods: ['GET', 'POST'], name: 'route.name')]` - Generic route attribute
- `#[Get('/path', name: 'route.name')]` - Shorthand for GET routes
- `#[Post('/path', name: 'route.name')]` - Shorthand for POST routes
- `#[Put('/path', name: 'route.name')]` - Shorthand for PUT routes
- `#[Delete('/path', name: 'route.name')]` - Shorthand for DELETE routes

## Route Parameters and Constraints

You can define route parameters and add constraints to them:

```php
#[Get('/users/{id}', name: 'users.show', where: ['id' => '[0-9]+'])]
public function show($id)
{
    // Show user with ID
}
```

## Default Values for Parameters

You can provide default values for optional parameters:

```php
#[Get('/users/{status?}', name: 'users.index', defaults: ['status' => 'active'])]
public function index($status)
{
    // List users with the given status (defaults to 'active')
}
```

## Middleware

You can apply middleware to controllers or specific methods:

```php
#[Middleware('auth')]
class AdminController
{
    #[Get('/admin/settings')]
    #[Middleware(['permission:manage-settings', 'csrf'])]
    public function settings()
    {
        // Handle settings page
    }
}
```

## Registering Attribute Routes

To register routes from controllers with attributes, you need to use the `router()` helper:

```php
router()->registerRoutesFromDirectories([
    __DIR__ . '/Controllers'
], 'Flute\\Modules\\Home\\Controllers');
```

Or register routes from a specific controller class:

```php
router()->registerRoutesFromClass('Flute\\Modules\\Home\\Controllers\\UserController');
```