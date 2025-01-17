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
    title: 'Query scopes, meet action scopes',
    description: "Action scopes are...well...just query scopes really, but instead of filtering they take an action. It's just a random name I've given them to differentiate them from regular filtering based query scopes in my projects.",
    date: new DateTimeImmutable('@1570834800', new DateTimeZone('Australia/Melbourne')),
    image: $url->asset('action-scopes.png'),
);

?>

I just read [Freek's blog post on the `void` return type](https://freek.dev/1481-the-value-of-the-void-typehint-in-php) (which you should check out), and it reminded me of this pattern I dig and thought I'd write up a quick post about it...and I named it...for better or worse.

## Query scopes

If you've worked with Eloquent, you've probably come across query scopes. Nothing special here...

```php
class Voucher extends Model
{
    // ...

    public function scopeWhereExpired(Builder $builder): void
    {
        $builder->where('expires_at', '<', Carbon::now());
    }
}
```

Applying this query scope will filter out any vouchers that have expired. We can use it as follows...

```php
Voucher::query()
    ->whereExpired()
    ->etc();
```

..but you didn't come here for query scopes, let's talk about...

## Action scopes

These are different from query scopes as calling them will **not** return a query builder, i.e. you cannot continue to chain additional methods onto the query builder. Again, if you've used Eloquent for long enough, you have no doubt used these already, you just haven't given them this name. `count()` is an example of what I would call an action scope.

```php
$count = Voucher::query()
    ->whereExpired()
    ->count();
```

`$count` is now an `integer`. You'll also notice that you couldn't chain more methods onto the query builder, right?!

```php
Voucher::query()
    ->whereExpired()
    ->count()
    ->thisWillCauseAnError(); // 🚨🚨🚨
```

That is because `count()` does not return the builder, but a result, in this case the number of matching records. `exists()` is another example of this, where it will return a `boolean` indicating if any matching records exist, `get()` and `paginate()` are also the same. Sure they return an object you can chain methods onto, but the object isn't the query, i.e. you are no longer adding filters to the **query**.

### Doin' it yourself

So how do we create our own "action scopes" that return a result? Coming back to our original `Voucher` class...

```php
class Voucher extends Model
{
    // ...

    public function scopeWhereExpired(Builder $builder): void
    {
        $builder->where('expires_at', '<', Carbon::now());
    }
}
```

As [Freek points out in his post](https://freek.dev/1481-the-value-of-the-void-typehint-in-php), returning the `$builder` or `void` makes no difference when calling the scope.

But what if we don't return `void` or the `$builder`, and instead return something...else 👻

### Returning something else!

We know what happens when we return nothing, and we know what happens when we return the builder, so let's see what happens when we return something else. We're gonna return an `integer` from our action scope method...

```php
class Voucher extends Model
{
    // ...

    public function scopeExtendUntil(Builder $builder, Carbon $date): int
    {
        return $builder->update(['expires_at' => $date]);
    }
}
```

The `update()` method on the query builder (not the Model) will return the number of updated rows. Our action scope is now returning how many vouchers have had their expiration date extended.

```php
$count = Voucher::query()
    ->whereExpired()
    ->extendUntil(Carbon::now()->addYear());

// $count is now an integer containing the number of extended vouchers.

return "{$count} vouchers have been extended for a year";
```

...and there you have it - that is an action scope...which is just query scopes that returns something other than `void` or the `$builder`. They indicate that it is the end of the line for the query builder, and that the query has been used - an action has been taken.

## Under the hood

But how does a model scope handle all this? We'll take a quick squiz under the hood.

When Laravel calls a query scope on a model, it is something simlar to the following code snippet. I've made modifications to try and make it more succinct, but if you'd like to see the full story, checkout the `__call` method on the `Illuminate\Database\Eloquent\Builder` class and follow the codepaths.

```php
$method = 'scope'.ucfirst($method);

$result = $model->$method($builder, ...$parameters) ?? $builder;

return $result;
```

As you can see, when the scope is called on the model, if you return the builder, it will end up in the `$result`. If you don't return anything, because of the null coalesce operator `??`, `$builder` will end up in `$result`. But if you return any non-null value, it will end up in the `$result` and be returned to where you called the scope.

## But why would you use an action scope?

I find it to be more expressive. Just like we do with methods on models ([or on collections](/giving-collections-a-voice/)), a nicely named action scope can look really nice.

```php
// for an individual model...

if ($voucher->hasExpired()) {
    $voucher->extendUntil(Carbon::now()->addYear());
}

// with an action scope...

Voucher::query()
    ->whereExpired()
    ->extendUntil(Carbon::now()->addYear());
```

Nothing like a nice unified API across all of eloquent 😍 😍 😍

It also promotes encasulation, which I'm always a fan of when possible. If you are applying change to many rows of the database, doing it from a query is always going to be more performant than say looping over a collection and updating each individually.

It can also serve as a great place to fire events or do any other work required.

```php
class Voucher extends Model
{
    // ...

    public function scopeExtendUntil(Builder $builder, Carbon $date): int
    {
        return tap($builder->update(['expires_at' => $date]), function ($count) use ($date) {
            Log::info("{$count} vouchers have been extended until {$date->diffForHumans()}");
        });
    }
}
```

Anyway...action scopes are just query scopes...but they return a result ✌️

## Related

- [The value of the void typehint in PHP](https://freek.dev/1481-the-value-of-the-void-typehint-in-php)
- [Giving collections a voice](/giving-collections-a-voice/)
- [Dedicated query builders for Eloquent models](/dedicated-eloquent-model-query-builders/)
