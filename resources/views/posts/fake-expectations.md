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
    description: 'A testing fake with named assertions or Pest PHP\'s expectation API: ¿Por Qué No Los Dos?',
    date: new DateTimeImmutable('@1743820860', new DateTimeZone('Australia/Melbourne')),
    image: $url->asset('fake-expectations.png'),
    formats: [Format::Article],
);

?>

I enjoy creating fake objects to assist with testing. These fake objects are similar to Laravel's testing fakes, such as `Mail::fake()`, in that they have named assertions attached to them. Throughout this post I'll use a custom HTTP client fake to illustrate the idea.

```php
$client = new ClientFake;

// ...

$client->assertSent(new Request(/* ... */));
```

When it comes to making assertions against other types of data, I'm deeply in love with Pest PHP's expectation API:

```php
expect($frameworks)->toHaveCount(5);
expect($frameworks[0])->toBe('Laravel');
```

Because I write a lot of _feature_ tests, I am often using the `assert...` and `expect` APIs alongside each other:

```php
$client = new ClientFake;
$user = User::factory()->create();

// ...

expect($user)->toHaveProperty(/* ... */);
$client->assertSent(new Request(/* ... */));
```

I started to feel that having both of these APIs alongside each other was creating unneeded mental overhead when scanning the test due to a lack of visual symmetry. I yearned for a unified testing API.

Thanks to Pest's [_custom expectations_](https://pestphp.com/docs/custom-expectations) feature, I was able to add named expectations for my fake objects which created visual symmetry:

```php
$client = new ClientFake;
$user = User::factory()->create();

// ...

expect($user)->toHaveProperty(/* ... */);
expect($client)->toHaveSent(new Request(/* ... */));
```

Custom expectations are not a new thing. I've created many of them throughout the years. The thing that was new to me was accessing _and modifying_ the `$this->value` property within the custom expectation.

Here is the skeleton of the custom expectation:

```php
expect()->extend('toHaveSent', function (Request $request) {
    //
});
```

The value passed to the `expect` function is made available within the custom expectation. Pest makes this happen by re-binding the value of `$this` within the callback:

```php
// expect($client)

expect()->extend('toHaveSent', function (Request $request) {
    echo $this->value::class; // Test\ClientFake
});
```

In the case of my `toHaveSent` expectation, I do not want to make assertions against the entire `ClientFake` object; I want to check against one of the client's properties. More specifically, I'm trying to test that the client's `requestsSent` property contains the given request.

To achieve this, I'm able to modify the expectation's value on the fly within the callback:

```php
expect()->extend('toHaveSent', function (Request $request) {
    $this->value = $this->value->requestsSent;

    echo is_array($this->value); // true
    echo $this->value[0]::class; // Test\Request
});
```

Then, I can return the expectation I would like applied to the modified value:

```php
expect()->extend('toHaveSent', function (Request $request) {
    $this->value = $this->value->requestsSent;

    return $this->toContainEqual($request);
});
```

This leaves a clean and unified testing API for both general data and fake objects:

```php
expect($user)->toHaveProperty(/* ... */);
expect($client)->toHaveSent(new Request(/* ... */));
```

If I were to have multiple test fakes that could use the `toHaveSent` expectation, e.g., a custom mail fake:

```php
$mail = new MailFake;
$client = new ClientFake;

// ...

expect($mail)->toHaveSent(new Email(/* ... */));
expect($client)->toHaveSent(new Request(/* ... */));
```

I can augment the callback to handle multiple types:

```php
expect()->extend('toHaveSent', function (Request|Email $needle) {
    $this->value = match ($this->value::class) {
        ClientFake::class => $this->value->requestsSent,
        MailFake::class => $this->value->mailSent,
        default => throw new RuntimeException('Unexpected class encounterd ['.$this->value::class.'].'),
    };

    return $this->toContainEqual($needle);
});
```

Leaving us with the final result:

```php
$mail = new MailFake;
$client = new ClientFake;
$user = User::factory()->create();

// ...

expect($user)->toHaveProperty(/* ... */);
expect($mail)->toHaveSent(new Email(/* ... */));
expect($client)->toHaveSent(new Request(/* ... */));
```


## Epilogue

Although I could have reached for raw expectations or Pest's higher order testing, I was not satisfied with aesthetics trade off when compared to the original `$client->assertSent(...)` API.

```php
expect($client->requestsSent)->toContainEqual(new Request(/* ... */));
expect($client)->requestsSent->toContainEqual(new Request(/* ... */));

// vs

$client->assertSent(new Request(/* ... */));
```
