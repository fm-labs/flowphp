# flowphp

PHP micro framework

[![Build Status](https://travis-ci.org/fm-labs/flowphp.svg?branch=master)](https://travis-ci.org/fm-labs/flowphp)

## Requirements

- php 7.0+

## Installation

```console
$ cd /my/project/dir
$ composer require fm-labs/flowphp dev-master
```

## Core Concepts

- `Global` - Manages singletons and global config. Provides static convenience helpers 
- `Application` - Wires configuration, routes and middleware
- `Router` - Wires routes with handlers
- `Controller` - Processes server requests
- `Template` - Renders parameterized templates php-style
- `View` - Template + Layout template
- `Manager` - Manages named object instances of configured adapters
  - Database
  - Cache
  - Log
- `RouteHandler` - Any callable that MAY produce on of the following results:
  - `ResponseInterface`
  - `RouteHandler`: Will be invoked recursively with updated route context
  - `Closure`: Treated as a RouteHandler
  - `String`: Will be applied as response body
  - `Null`: Fallback handling. Not implemented yet
  - `Other`: Throws exception

## Components

### HTTP Stack

- `Messages` (`Request` / `Response`) - Complaint to [PSR-7](https://www.php-fig.org/psr/psr-7),
- `Handler` - Complaint to [PSR-15](https://www.php-fig.org/psr/psr-15)
- `Factory` - Complaint to [PSR-17](https://www.php-fig.org/psr/psr-17)
- `Server` - for processing `HTTP Messages` using `HTTP Handlers` and `HTTP Factories`
- `MiddlewareQueue` - for processing multiple request handlers

### Application

The application is the main object we are working when building a project based on FlowPHP.
All the wiring of the configuration, components and routes happens here.

Basically the application acts as a Service Container and Server Request Handler.
The standard application has 3 main components: Configuration, Router and Middleware.


### Middleware

Built-in middlewares:
- `ErrorMiddleware` - Error handling
- `RoutingMiddleware` - Route matching and handling
- `RequestMapperMiddleware` - Build response based on request 
- `CorsMiddleware` - CORS handling

### Routing

Simple Router
- String templates
- Named args
- Optional args
- Pass args - Arguments that will be passed to the request handler as function arguments
- Custom regex for arg matching

### Templating

Simple Template Engine


```php

$template = (new Template())
    ->set('myvar', 'myval')
    ->setTemplate('mytemplate'); // resolves to ./templates/mytemplate.phtml

$html = $template->render();

```

## Examples



## Run tests
```console
$ composer run-script test
// or
$ composer run-script test-verbose
// or
$ ./vendor/bin/phpunit --bootstrap tests/bootstrap.php tests/
```

## TODO

- App: Controller
- App: Plugins
- Routing: Nested routers
- Template: Helpers
- Http/Server: Cookie support
- Http/Server: Uploaded files support


## Changelog


