# Simple Parts

Surprisingly simple components!

These components are intentionally small and simple.

We advise you to actually read the code of a component you want to use because
they are short and sometimes fit in one page.

The name is inspired by Crockford's book *javascript the good parts*.

## Why?

Mostly as a personal learning exercise. The initial motivation was to create
a framework like Slim only slimmer.

Other reasons:

* We dislike depending on an endless amount of third-party packages
* We dislike the endless amount of upgrades for third-party packages
* We dislike monitoring third-party packages for vulnerabilities
* We dislike learning existing frameworks
* We dislike general-purpose frameworks because they are so large and complex
* We are less exposed to a [supply chain attack](https://blog.sonarsource.com/php-supply-chain-attack-on-composer)  
* Smaller and simpler components have less defects and vulnerabilities
* Creating components as if they were third-party packages makes for better components

Overview:

* `Cache`
* `Clock`,`SystemClock`,`FrozenClock`
* `Config`
* `Console`
* `Container`
* `ErrorHandler`
* `File`,`TextFile`,`MemoryFile`
* `HttpClient`,`Request`,`Response`
* `Json`
* `Logger`,`Handler`,`CliHandler`,`FileHandler`
* `Migrator`
* `Renderer`
* `Router`  
* `Session`
* `Shell`
* `Test`

## Cache

The cache is a persistent key-value store.

Cached items have ttls (time-to-live) specified in seconds from now. By default they never expire.

The underlying storage is a file. The cached data is serialized as json.

The cache is written to file when it is garbage-collected.

```php
<?php

$cache = new Cache(new TextFile('./cache.json'));

$cache->set('foo'); // boolean true
$cache->set('foo', 'bar');
$cache->set('foo', 'bar', 60);
$cache->get('foo');

$cache->get('non_existing_key'); // NULL
$cache->get('non_existing_key', 'default'); // 'default'

$cache->delete('foo');
$cache->clear();
```

## Clock

The clock component is useful in testing.

Clock interface:
```php
<?php

interface Clock
{
    public function now(): \DateTimeImmutable;
}
```

System clock:
```php
<?php

$clock = new SystemClock();

$now = $clock->now();

print $now->format('Y-m-d H:i:s'); // 2021-05-09 22:56:25
```

Frozen clock:
```php
<?php

$clock = new FrozenClock(new \DateTimeImmutable('1980-12-24'));

print $clock->now()->format('Y-m-d H:i:s'); // 1980-12-24 00:00:00

$clock->advance(new \DateInterval('P1Y'));

print $clock->now()->format('Y-m-d H:i:s'); // 1981-12-24 00:00:00
```

## Config
Config is a small layer around a php array with key-value pairs.

Keys in the custom config MUST have default values in the default config.

Trying to grab a non-existing key results in an exception.

```php
<?php

$defaultConfig = [
    'env' => 'dev',
];

$customConfig = [
    'env' => 'prod',
];

$config = Config::fromArray($defaultConfig, $customConfig);

$env = $config->get('env'); // prod
```
## Console

The console allows for some convenient printing of text, newlines and tables.

Also useful is colored text and exit code.

All methods support printf-style formatting.

```php
$console = new Console();

$console->print("hello %s\n", 'world');
$console->println('hello world');

$console->greenln('hello world');

$console->yellowln('hello world');

$console->redln('hello world');

$console->table(['id', 'user'], [
    [1, 'root'],
    [2, 'support'],
]);

$console->exit(1);
```
```
$ php test.php ; echo $?
hello world
hello world
hello world
hello world
hello world
+---------+---------+
| id      | user    |
+---------+---------+
| 1       | root    |
| 2       | support |
+---------+---------+
1
```

## Container

Container is a standard dependency container.

The resolved dependencies are cached.

Only closures are allowed as values.

```php
<?php

$container = new Container();

$container['http_client'] = function($container) {
    return new HttpClient($container['http_client_config']);
};

$container['http_client_config'] = function() {
    return [
        'timeout' => 5,
    ];
};

$httpClient = $container['http_client'];
```

## ErrorHandler

The error handler registers itself as php's error handler, exception handler and shutdown function.

A logger MUST be provided.

It is heavily inspired by
[Monolog](https://github.com/Seldaek/monolog).

```php
<?php

$logger = new Logger('default', [new CliHandler()]);

$_ = ErrorHandler::create($logger);

print $foo;
```
```
[2021-05-10 02:18:56] default.INFO E_NOTICE: Undefined variable: foo at /home/u/simple-parts/test.php line 11 {
    "stacktrace": [
        "/home/u/simple-parts/test.php:11"
    ]
}
```

## TextFile

TextFile is a standard file abstraction.

```php
<?php

$file = new TextFile('./diary.txt');

$file->write('hello ');

$file->append('world');

if ($file->exists()) {
    print $file->read();
}
```

## Request

Request is an abstraction over an http request.

```php
<?php

$request = Request::fromGlobals();

print $request->method(); // request method
print $request->uri(); // request uri
print $request->get('non_existing'); // query param, defaults to NULL
print $request->get('id', '42'); // query param, defaults to '42'
print $request->post('user', 'anon'); // post param, defaults to 'anon'
```

## Response

Response is an abstraction over an http response.

```php
<?php

$response = new Response("Hello world\n", 200, ['Content-Type' => 'text/plain']);

$response->send();
```

It has a few utility methods:

```php
<?php

$response = new Response();

$response = $response
    ->withHeader('foo', 'bar')
    ->withCode(201)
    ->withJson(['message' => 'all is good'])
;

$response->send();
```

## HttpClient

This is a large undertaking.

Basic usage:
```php
<?php

$client = new HttpClient();

$response = $client->get('https://example.com');

print $response->body();
```

Responses that are not ok will throw exception:
```php
$client = new HttpClient();

try {
    $response = $client->get('https://example.com/non-existing');
} catch (SimpleException $e) {
    print "Not found\n";
}
```

Post request:
```php
<?php

$client = new HttpClient();

$response = $client->post('https://example.com/', [
    'body' => [
        'foo' => 'bar'
    ]
]);

print $response->body();
```

Generic usage:
```php
<?php

$client = new HttpClient();

$response = $client->request('GET', 'https://example.com/', [
    'useragent'         => 'firefox',
    'connect_timeout'   => 3,
    'timeout'           => 3,
]);

print $response->body();
```
## Json

Json is mostly a wrapper that throws exception if the data fails to encode/decode as json.

```php
<?php

try {
    $json = Json::encode(['message' => 'hello']);

    print $json . "\n";

    $array = Json::decode($json);

    print_r($array);
} catch (SimpleException $e) {
    print $e->getMessage();
}
```
```
$ php test.php
{
    "message": "hello"
}
Array
(
    [message] => hello
)
```
## Logger

The logger is heavily inspired by
[Monolog](https://github.com/Seldaek/monolog).

It accepts an array of handlers. It has three log levels: `INFO`, `WARNING` and `ERROR`.

All handlers will receive a log item of the form:
```
[
    'name'          => $this->name,
    'created_at'    => new DateTime(),
    'level'         => $level,
    'level_name'    => self::LEVEL_NAMES[$level],
    'message'       => $message,
    'context'       => $context,
]
```

Usage:
```php
<?php

$logger = new Logger('default', [new CliHandler()]);

$logger->info('hello', ['foo' => 'bar']);
$logger->warning('hello');
$logger->error('hello');
```
```
[2021-05-10 03:23:53] default.INFO hello {
    "foo": "bar"
}
[2021-05-10 03:23:53] default.WARNING hello []
[2021-05-10 03:23:53] default.ERROR hello []
```

## Migrator

The migrator is for database migrations.

Migrate migrations in `./migrations`:
```php
<?php

$pdo = new \PDO('sqlite:application.db');

$migrator = new Migrator($pdo, './migrations');

$result = $migrator->migrate();

if ($result === []) {
    exit;
}

print implode("\n", $result) . "\n";
```

## Renderer

Renderer is a template engine. `e` is a function that escapes for html context.

```php
<?php 

$renderer = new Renderer();

print $renderer->render('./welcome.php', ['user' => 'bob']);
```

welcome.php:
```php
<?php namespace dvikan\SimpleParts; ?>

Hello <?= e($user) ?>
```

## Router

The router maps http requests to handlers.

```php
<?php

$router = new Router();

$router->get('/', function() {
    return 'index';
});

$router->post('/delete', function() {
    return 'delete';
});

$router->map(['GET', 'POST'], '/update', function() {
    return 'update';
});

$router->post('/delete/(\d+)', function(array $vars) {
    $id = (int) $vars[0];
    return 'id: ' . $id;
});

$route = $router->dispatch('GET', '/');

if ($route[0] === Router::NOT_FOUND) {
    exit('404');
}

if ($route[0] === Router::METHOD_NOT_ALLOWED) {
    exit('Method not allowed');
}

$handler = $route[1];
$args = $route[2];

print $handler($args);
```

## Session

Session is an abstraction over php's `$_SESSION`.
```php
<?php

$session = new Session();

$session->set('user', 'alice');

print 'Hello, ' . $session->get('user', 'anon');
```

## Shell

Shell is an abstraction for running os commands.
```php
<?php

$shell = new Shell();

print $shell->execute('echo', ['hello', 'world']);
```

The arguments are escaped but you still need to make sure they are not parsed as command options.

You can sometimes use `--` otherwise validate the arguments manually.
```php
<?php

$shell = new Shell();

$userInput = 'https://github.com/Seldaek/monolog.git';

print $shell->execute('git clone --', [$userInput]);
```
## Test

Test is a tool for creating unit tests. Tests must inherit from `TestCase`.

Tests are assumed to be located in `./test`.

Example:
```php
<?php

use dvikan\SimpleParts\TestCase;

final class test extends TestCase
{
    function test_1()
    {
        $this->assert(1, 1);
        $this->assert(1, 2);
    }
}
```

Run tests:
```
$ php vendor/bin/test.php
Fail at /home/u/simple-parts/test.php line 10
Expected: 1
Actual: 2
```

## Todo

* git wrapper
* irc client
* socket wrapper
* web framework
* Url
* Uri
* Csv
* Collection
* ImapClient
* DataMapper
* ORM
* Dotenv
* EventDispatcher
* Validator
* Random
* Guid
* Flat file database
* vardumper
* throttling
* captcha
* i18n
* String
* html form, csrf
* browser ua lib
* ipv4 address to location lib
* autoloader
