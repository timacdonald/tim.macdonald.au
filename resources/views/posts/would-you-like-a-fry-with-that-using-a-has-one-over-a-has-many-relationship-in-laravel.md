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
    title: "Would you like fry with that? Using a HasOne over a HasMany relationship in Laravel",
    description: 'When you are working with a one-to-many relationship, it is sometimes the case that a particular instance on the "many" side of the relationship is flagged as unique and important to your system in some way.',
    date: new DateTimeImmutable('@1615691562', new DateTimeZone('Australia/Melbourne')),
    image: $url->asset('has-one-has-many.png'),
);

?>

When you are working with a one-to-many relationship, it is sometimes the case that a particular instance on the "many" side of the relationship is flagged as unique and important to your system in some way. It can be really handy to be able to access that unique instance in a first class way from your models. This post is going to cover how you can do that without introducing any new concepts into your application.

The idea of this unique relation over a `HasMany` relationship can be visualised as shown below in the example of a user having many payment methods (e.g., multiple credit cards), however only one payment method can ever be the "default" payment method at any given time.

![Visual representation showing one user on the left and many payment methods on the right - which only one connection between a user and a payment method highlighted]({{ url('images/posts/default-payment-method.png') }})

For our example, we are have a "state" column on the payment method table, which can hold a few different values such as `"available"`, `"default"`, `"expired"`, and `"disabled"`.

```php
Schema::create('payment_methods', function (Blueprint $table): void {
    // ...

    $table->foreignId('user_id')->constrained();
    $table->string('state');
}
```

and here is what our supporting models look like...

```php
class User extends Model
{
    public function paymentMethods(): HasMany
    {
        return $this->hasMany(PaymentMethod::class);
    }
}

class PaymentMethod extends Model
{
    public function scopeWhereDefault(Builder $builder): void
    {
        $builder->where('state', '=', 'default');
    }
}
```

## The inline way

Our first attempt to access the "default" payment method for a user might look something like the following...

```php
$defaultPaymentMethod = $user->paymentMethods()
    ->whereDefault()
    ->first();
```

Although this approach works perfectly fine in isolation, it doesn't handle a lot of things that we are going to want for our app as it grows bigger. We have the following issues with regard to accessing the "default" relation:

- No relation caching (unless we load all the payment methods into memory)
- No way to eager load (without redefining the constraint)
- Not reusable

## The custom method way

To solve the re-usability issue, we could try wrapping this up into a custom method on the model...

```php
class User extends Model
{
    public function paymentMethods(): HasMany
    {
        return $this->hasMany(PaymentMethod::class);
    }

    public function defaultPaymentMethod(): ?PaymentMethod
    {
        return $user->paymentMethods()
            ->whereDefault()
            ->first();
    }
}

$user->defaultPaymentMethod();
```

Although this has solved the re-usability issue, some of the previous issues still linger and we have introduced some new issues:

- *No query exposed for the relationship.*
- *Unconventional method access to the relationship.*
- No relation caching (unless we load all the payment methods into memory)
- No way to eager load (without redefining the constraint)
- <s>Not reusable</s>

## The accessor and query method way

In an attempt to address some more of these existing issues we might look at introducing a dedicated method to wrap up the query and an accessor to retrieve the `first()` item of the returned collection.

```php
class User extends Model
{
    public function paymentMethods(): HasMany
    {
        return $this->hasMany(PaymentMethod::class);
    }

    public function defaultPaymentMethods(): HasMany
    {
        return $user->paymentMethods()
            ->whereDefault();
    }

    public function getDefaultPaymentMethodAttribute(): ?PaymentMethod
    {
        return $user->defaultPaymentMethods->first();
    }
}

$user->defaultPaymentMethod;
```

Again, we have dealt with some of the issues, but introduced some others:

- *Implicit exposure of a `$user->defaultPaymentMethods` relationship attribute*
- *Singular and plural naming of `defaultPaymentMethods` and `defaultPaymentMethod`*
- *Is kind of just confusing*
- <s>No query exposed for the relationship.</s>
- <s>Unconventional method access to the relationship.</s>
- <s>No relation caching (unless we load all the payment methods into memory)</s>
- <s>No way to eager load (without redefining the constraint)</s>
- <s>Not reusable</s>

## The relation that always returns a collection way

Alternatively we could settle on exposing the `defaultPaymentMethods` relation that always returns a collection, but that is not great in my opinion as you are *always* going to be repeating the following...

```php
$user->defaultPaymentMethods->first();
```

## Why it's all a hack

This all just feels like hacky workaround, which I'd rather not do. I want something first class that represents this relation, but isn't confusing.

If we take a step back and look at all our solutions, we will see that the cause of all our problems is the `HasMany` relation, and the fact that we are using it when we only want one instance returned. The `HasMany` relation always returns a collection, so at some point we need to intercept it and tell it to return the `first()` result.

## Diving the relations

The bit that is making this happen is the `HasMany::getResults` method.

```php
public function getResults()
{
    return ! is_null($this->getParentKey())
        ? $this->query->get()
        : $this->related->newCollection();
}
```

This method is *always* going to return a collection - however looking at the `HasOne::getResults` method we can see that it is calling `first()` on the query *for us* (I've removed some of the code that we don't care about for the purpose of this post).

```php
public function getResults()
{
    // ...

    return $this->query->first() ?: $this->getDefaultFor($this->parent);
}
```

This got me to thinking...could I not use a `HasOne` to model this relationship? The user does, after all, "have one default payment method". Drum roll, please 🥁

## The HasOne way 🎉

If we attempt to model this `HasOne` over the `HasMany` relation, we adjust our model to the following...

```php
class User extends Model
{
    public function paymentMethods(): HasMany
    {
        return $this->hasMany(PaymentMethod::class);
    }

    public function defaultPaymentMethod(): ?HasOne
    {
        return $this->hasOne(PaymentMethod::class)
            ->whereDefault();
    }
}

$user->defaultPaymentMethod;
```

The intentions here are clear as we are sticking to Laravel's conventions and anyone who knows those conventions will also know what to expect from the above relations, for example accessing the relation attribute will cache the instance. But lets take a look at our full problem list and see how we are going...

- <s>Implicit exposure of a `$user->defaultPaymentMethods` relationship attribute</s>
- <s>Singular and plural naming of `defaultPaymentMethods` and `defaultPaymentMethod`</s>
- <s>Is kinda just confusing</s>
- <s>No query exposed for the relationship.</s>
- <s>Unconventional method access to the relationship.</s>
- <s>No relation caching (unless we load all the payment methods into memory)</s>
- <s>No way to eager load (without redefining the constraint)</s>
- <s>Not reusable</s>

Hey, would you look at that! We have addressed all the issues with the previous approaches and the solution is minimal. What's more is we haven't had to introduce any workarounds or new concepts - we are just using the existing `HasOne` relationship Laravel provides out of the box.

We get eager loading...

```php
$users = User::query()
   ->with(['defaultPaymentMethod'])
   ->etc();
```

We get relationship caching...

```php
$user->defaultPaymentMethod; // hits the DB the first time
$user->defaultPaymentMethod; // doesn't hit the DB
```

and everything else you'd expect from a normal relationship.

## Do not rely on ordering

The example we have used focused on the premise that there is some kind of unique flag per user that indicates which payment method we want to retrieve. Having some kind of unique flag per relation is *essential* to this approach. If you try to rely on ordering you are going to hit some issues. Instead of me outlining them here, I highly recommend you go and read [Dynamic relationships in Laravel using subqueries](https://reinink.ca/articles/dynamic-relationships-in-laravel-using-subqueries) by Jonathan Reinink instead, which is excellent and explains how to handle this kind of relation that relies on ordering (it's pretty darn magical to be honest).

The idea here is that user genuinly only _has one_ default payment method, which is represented in the database via state. It doesn't rely on ordering and can be described with filtering via where statements. A limtus test to use to for this approach is as follows...

If the following...

```php
$user->paymentMethods()->whereDefault()->count()
```

could return anything other than 1 or 0, it is not suitable for this approach.
