<?php

use TiMacDonald\Website\Format;
use TiMacDonald\Website\Page;

/**
 * Props.
 *
 * @var string $projectBase
 * @var \TiMacDonald\Website\Request $request
 * @var \TiMacDonald\Website\Url $url
 * @var (callable(string): void) $e
 * @var \TiMacDonald\Website\Markdown $markdown
 * @var \TiMacDonald\Website\Collection $collection
 */

// ...

$page = Page::fromPost(
    file: __FILE__,
    title: 'Fake expectations',
    description: 'Fake assertions or Pest PHP expectations: ¿Por Qué No Los Dos?',
    date: new DateTimeImmutable('now', new DateTimeZone('Australia/Melbourne')),
    image: $url->asset('magic-values.png'),
    formats: [Format::Article],
    hidden: true,
);

?>

I enjoy creating fake objects for testing. These fake objects are similar to Laravel's testing fakes in that they have named assertions attached to them, e.g.,

```php
$client = new FakeClient;

// ...

$client->assertSent(new Request(/* ... */));
```

When it comes to making assertions against other types of data, I'm deeply in love with Pest PHP's expectation API:

```php
expect($frameworks)->toHaveCount(5);
expect($frameworks[0])->toBe('Laravel');
```

Because I write a lot of feature tests, I often find my tests end up making assertions and expectations together, e.g.,

```php
$client = new FakeClient;
$user = User::factory()->create();

// ...

$client->assertSent(new Request(/* ... */));
expect($user)->toHaveProperty(/* ... */);
```

This inconsistency was bothering me. I decided see if I could unify my testing API. Thanks to Pest's custom expectations, I was able to keep my fake objects and named assertions while also creating more symmetrical test code:

```php
$client = new FakeClient;
$user = User::factory()->create();

// ...

expect($client)->toHaveSent(new Request(/* ... */));
expect($user)->toHaveProperty(/* ... */);
```

[Custom expectations](https://pestphp.com/docs/custom-expectations) are not a new thing. I've created many of them throughout the years. The thing that was new to me was accessing _and modifying_ the `$this->value` property of the custom expectation.

Here is the skeleton of the custom expectation:

```php
expect()->extend('toHaveSent', function (Request $request) {
    //
});
```

The value passed to the expectation, e.g., `expect($client)`, is made available within the custom expectation. Pest makes this happen by re-binding the value of `$this` within the callback:

```php
expect()->extend('toHaveSent', function (Request $request) {
    echo $this->value::class; // Test\FakeClient
});
```

In the case of `toHaveSent`, I do not want to make assertions against the entire `FakeClient` object; I want to check against one of the client's properties. I'm trying to test that the client's `public array $requestsSent` property contains the given request.

To achieve this, I'm able to modify the expectation's value:

```php
expect()->extend('toHaveSent', function (Request $request) {
    $this->value = $this->value->requestsSent;

    echo is_array($this->value); // true
    echo $this->value[0]; // Request
});
```

Then, I can return the expectation I would like applied to the modified value:

```php
expect()->extend('toHaveSent', function (Request $request) {
    $this->value = $this->value->requestsSent;

    return $this->toContainEqual($request);
});
```

This leaves me with a clean and unified testing API for both general data and fake objects:

```php
expect($client)->toHaveSent(new Request(/* ... */));
expect($user)->toHaveProperty(/* ... */);
```

## Epilogue

Although I could have reached for Pest's higher order testing, I am not satisfied with this as a clean replacement for the fake object assertions:

```php
expect($client->requstsSent)->toContainEqual(new Request(/* ... */));
expect($user)->toHaveProperty(/* ... */);

```
