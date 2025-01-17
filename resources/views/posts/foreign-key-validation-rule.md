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
    title: 'Foreign key validation rule',
    description: "A foreign key validation rule for the Laravel Validator that helps wrap up the 'exists' rule with a bit of syntactic sugar. Probably not fantastic to work with in the traditional fashion, but using the rule builder package...it looks gooooood!",
    date: new DateTimeImmutable('@1493522820', new DateTimeZone('Australia/Melbourne')),
    image: $url->asset('foreign-key-validation-rule.png'),
);

?>

In this post I'm going to show you how you can validate foreign keys in your request form objects by calling:

```php
Rule::foreignKey(Subscription::class)
```

I'm digging it. It's just a simple extension on the `exists` rule, but I think it is killer.

## Find or fail?

When I started with Laravel, I always found it quick and easy to utilise the Eloquent query builder's `findOrFail` method to ensure that any foreign keys I was submitted from a user were valid. Most of the time these foreign keys are coming from a HTML select drop-down in some web form. Of course we want to fail to make sure we don't attempt to insert, into the database, a foreign key that doesn't exist (from some tricksy little hobbits'zs that decide they want to wreak havoc in our apps), but was this the best approach?

Then I got thinking, really the `findOrFail` method is just for resources in the URL. That being said - I really don't care what type of error I send someone being malicious.

Say we have a user in the database with an id of `2`. That user has a foreign key of `role_id`.

If this user is being updated by calling `PATCH: /users/2`, but the request contains a `role_id` value that does not exists, it doesn't really make sense that the response would be a 404 error because that user does exist. They should, of course, receive a validation error. But `findOrFail` did serve the general purpose for me in the early days.

## Validation forms

Keeping all your validation in a form object makes things much easier to maintain. If I'm using a form object and still utilising `findOrFail` in the controller for my `role_id` foreign key, obviously I haven't validated my data. I should *trust* that those keys exist, and can just lean on the `find` method instead.

### Exists rule

Luckily, Laravel offers a handy validation rule `exists` to make this easy. As an initial introduction, say we are creating a password reset controller. The request coming in will contain an email address, and we need to check that the email address exists in the database for some user. We might do something like:

```php
$rules = [
    'email' => 'required|email|exists:users,email',
];
```

This will hit the `users` table and search through the `email` column for the value in the request for `email`. If it does not find that email I can present the user with an error letting them know the email address does not exist.

### Exists with foreign key, the *stringy* way

Translating this to a foreign key rule is pretty straightforward. We just need to specify the table and column.

My current project has a `User` and `Subscription` class. The user has a `subscription_id` foreign key field. Let's see how we could validate a submitted `subscription_id` value using the exists rule:

```php
$rules = [
    'subscription_id' => 'exists:subscriptions,id',
];
```

Here we are saying, ensure that the request input `subscription_id` exists in the `subscriptions` table by looking for it in the `id` column.

Perfect! Exactly what we were after. But wait - *there's more!*

Wouldn't it be annoying if we change our table name or primary key column name later on? Then we would have to update these strings manually. But we could always just access those values from an Eloquent instance instead:

```php
$subscription = new Subscription;

$rules = [
    'subscription_id' => 'exists:'.$subscription->getTable().','.$subscription->getKeyName(),
];
```

Hmmm. Although it works, I can't handle this. It's just to messy for me.

### Exists with foreign key, the fluent way

Let's utilise Laravel's built in fluent validation rules. These are nice helpers around some of the more complex validation rules to make it easier to do more complex computation within the rules.

```php
use Illuminate\Validation\Rule;

$subscription = new App\Subscription;

$rules = [
    'subscription_id' => [
        Rule::exists($subscription->getTable(), $subscription->getKeyName()),
    ]
];
```

Okay, now we are getting somewhere, but I think we can do better!

## Extending the validator

Let's solidify our concept of foreign key validation to make this really clear by [extending the Laravel validator](https://laravel.com/docs/5.4/validation#custom-validation-rules), you should do this in a service provider.

We're going to allow you to pass in an Eloquent instance or a class string. It's going to be awesome...

```php
namespace App\Providers;

use Illuminate\Validation\Rules\Exists;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Validator;

class AppServiceProvider extends ServiceProvider
{
    public function boot()
    {
        Validator::extend('foreign_key', function ($attribute, $value, $parameters, $validator) {
            $instance = is_string($parameters[0]) ? new $parameters[0] : $parameters[0];

            return new Exists($instance->getTable(), $instance->getKeyName());
        });
    }
}
```

## Using the foreign key validation rule

We can now access our new rule in our form validation. To kick things off, let's do it the *stringy* way.

```php
$rules = [
    'subscription_id' => 'foreign_key:'.App\Subscription::class,
];
```

So that will work fine, but again - we can do better! There are a couple of ways we can do this. The first is to extend the `Illuminate\Validation\Rule` class and add the method there, or add a macro to it, as it utilises the `Macroable` trait, but I'm going to use this as a chance to flaunt the [Laravel Validation Rule package](https://github.com/timacdonald/rule-builder) I made.

Once you have that installed, just extend the package like so:

```php
TiMacDonald\Validation\Rule::extendWithRules('foreign_key');
```

You should do this just after you extend the validator in your service provider.

Now we have added all the magic sauce, lets see it in action. When we want to validate that a foreign key exists, we can simply use:

```php
use App\Subscription;
use TiMacDonald\Validation\Rule;

$rules = [
    'subscription_id' => Rule::foreignKey(Subscription::class)->get(),
];
```

or assuming we already had an instance in the `$subscription` variable:

```php
use TiMacDonald\Validation\Rule;

$rules = [
    'subscription_id' => Rule::foreignKey($subscription)->get(),
];
```

## Update

Laravel 5.5 added [custom validation rules](https://laravel.com/docs/5.5/validation#custom-validation-rules), which means the framework now gives you a way to do this really cleanly.
