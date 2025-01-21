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
    title: "Rethinking Laravel's middleware argument API",
    description: "String concatenation is the current way we can pass arguments to Laravel middleware - but what if there was another way",
    date: new DateTimeImmutable('@1586995200', new DateTimeZone('Australia/Melbourne')),
    image: $url->asset('rethinking-middleware.png'),
);

?>

I have been using a helper trait to make working with middleware arguments feel nicer. I'm going to walk through the current way we can pass arguments to middlewares, and then we'll dive into what I've come up with and how it changes the way you work with middleware arguments.

But before we do that, I wanna take a step back in time and look at validation rules in Laravel, before we had the fluent rule builder.

Take the following example...

```php
return [
    'email' => [
        'required',
        'email',
        'unique:users',
    ],
];
```

This is simple enough, easily understood, and works well. But once we start to add some more complexity to the validation rules, it can start to break down.

```php
return [
    'email' => [
        'required',
        'email',
        'unique:users,email_address,NULL,id,account_id,1',
    ],
];
```

The `unique` validation rule is starting to look at little confusing. What do the values in this list represent? I know coming back to this in a couple of weeks, there is going to be a bit of overhead in understanding what this validation is doing.

Another pain point was providing a list of values to rules such as `in`.

```php
return [
    'country' => 'in:'.Country::AUSTRALIA.','.Country::NEW_ZEALAND.','.Country::INDONESIA,
];


// or add a little implode...

return [
    'country' => 'in:'.implode([
        Country::AUSTRALIA,
        Country::NEW_ZEALAND,
        Country::INDONESIA,
    ], ','),
];
```

We've just covered two issues. The first is not knowing what the values represent, and the second is if you want to reference constants, having to do the string concatenation was never enjoyable.

Anyway, others must have also felt this pain, because at some point the framework shipped with [fluent object](https://github.com/laravel/framework/blob/b92e27ded466e0994f3d5b3fa15c0337e62fa884/src/Illuminate/Validation/Rule.php) interfaces for some of the validation rules. This made the rules more PHP'ish to declare, and helped with identitying what the values represented by providing better named methods / interfaces.

```php
return [
    'email' => [
        'required',
        'email',
        Rule::unique('users')->ignore($user->id)->where(function ($query) {
            $query->where('account_id', 1);
        }),
    ],
];
```

The same was done for the `in` rule (and several others) to make lists of constants easier to implement.

```php
return [
    'country' => Rule::in([
        Country::AUSTRALIA,
        Country::NEW_ZEALAND,
        Country::INDONESIA,
    ]),
];
```

These made me happy.

These work so well because Laravel knows what these validation rules are, so it can create an API for them that _feels_ like Laravel, but is also reflective of what is happening.


Now fast forward to the present. I wanna talk about middleware, and more importantly, a pain I felt while passing arguments to a middleware. This is in no way a huge pain point, and to call it a "pain point" might even be to stronger language. But I found it lacking when compared to the API's Laravel is known for.

Lets first take a look at what we are even talking about, to refresh your memory.

In certain scenarios it can be handy to pass values from a route definition to a middleware class. It makes the middleware more reuseable, as it can adapt to differing requirements for different routes. In order to do this, define some parameters in the `handle()` method of the middleware class after the `$request` and `$next` parameters.

```php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureResearchOutputs
{
    public function handle(Request $request, Closure $next, string $publications = '1', string $citations = '3'): Response
    {
        //
    }
}

```

Then, going by the docs, we need to register the middleware in the HTTP kernel. We do this by adding the class to the `$routeMiddleware` property in the HTTP kernel. The key in the array is just a short string that identifies the middleware, which you use in your route definitions. You can use whatever makes sense for your middleware.

```diff
protected $routeMiddleware = [
    // ...
    'throttle' => \Illuminate\Routing\Middleware\ThrottleRequests::class,
+    'ensure_research_outputs' => \App\Http\Middleware\EnsureResearchOutputs::class,
];
```


The key, in this case, is `ensure_research_outputs`. When we want to reference this middleware in our route definitions, we only need to use this key, instead of the class name. We use it with the following convention...

```php
// passing no arguments...
Route::stuff()
    ->middleware([
        'ensure_research_outputs',
    ]);

// passing a single argument...
Route::stuff()
    ->middleware([
        'ensure_research_outputs:20',
    ]);

// passing multiple arguments...
Route::stuff()
    ->middleware([
        'ensure_research_outputs:20,100',
    ]);
```

It is also possible to create a middleware that accepts a list of values an array. You can achieve this by using a [variadic parameter](https://www.php.net/manual/en/functions.arguments.php#functions.variable-arg-list) like so...

```php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class Domains
{
    public function handle(Request $request, Closure $next, string ...$domains): Response
    {
        // $domains is an array of strings
    }
}
```

...and you can use a combination of both approaches (but only ever one variadic parameter - cause that's how they work). But no matter how you define the parameters in the `handle()` method, you'll always pass the values, in your routes file, as a comma seperated list of strings.

```php
// accepts a list of domains...
Route::stuff()
    ->middleware([
        'domains:gmail.com,hotmail.com',
    ]);

// accepts an ability and a list of models
Route::stuff()
    ->middleware([
        'can:create,App\Post,App\Video',
    ]);
```

So that is the background on what and how middleware parameters / arguments work in Laravel.

There are a couple of things that that I don't love about this:

1. It relies on a comma seperated list of values that is not clear what each value represents, just like the validation rules we looked at earlier. In `'ensure_research_outputs:20,100',` what does the `100` represent? I know that I've already forgotten, and I was the one that created this fictional middleware only a few paragraphs ago!
2. String concatenation as an API
3. Having to register every middleware in the HTTP kernel

We are gonna start to break this down and see where we end up (pssst. I think you'll dig it).

As it turns out, you don't actually *have* to register the middleware in the HTTP kernel, instead you can just reference the class.

```php
Route::stuff()
    ->middleware([
        EnsureResearchOutputs::class.':20,100',
    ]);
```

Woo. That addressed item 3, but has introduced more string concatenation. But we'll push on.

I usually find that middleware parameters are coming from constant values, so I'll swap out the magic strings with constants and we can see what it looks like.

```php
Route::stuff()
    ->middleware([
        EnsureResearchOutputs::class.':'.Premium::MINIMUM_PUBLICATIONS.','.Premium::MINIMUM_CITATIONS,
    ]);
```

Okay, so now the values at least have better naming and I know what they represent. But again, I've introduced even more concatenation.

Maybe we switch the string concatenation of the constant values with an implode! _(I'll just need a sec while I google the correct order of the implode method...brb...oh yea, that's right, it works in...any...order 😯)_.

```php
Route::stuff()
    ->middleware([
        EnsureResearchOutputs::class.':'.implode(',', [
            Premium::MINIMUM_PUBLICATIONS,
            Premium::MINIMUM_CITATIONS,
        ]),
    ]);
```

But I'm still not a fan of this at all. As a matter of fact, my eyes are starting to hurt just looking at it. What have I DONE!

So this is where I got to and thought: I've got to be able to abstract the implode, and this would be really nice, so what if...

```php
Route::stuff()
    ->middleware([
        EnsureResearchOutputs::with([
            'publications' => Premium::MINIMUM_PUBLICATIONS,
            'citations' => Premium::MINIMUM_CITATIONS,
        ]),
    ]);
```

Oh yea, that is the API I was searching for. A little more verbose sure, but now we have statically analysable, easily formattable middleware that doesn't require string concatenation! Plus we know what each value represents, by looking at the array keys. There is more to come...

To achieve this API, I've created a [helper trait](https://github.com/timacdonald/has-parameters) that you can use on any middleware that accepts custom parameters. With a sprinkle of reflection and a handful of collection pipelines, I feel like I've worked out a really nice API.

Anyway, enough of me patting myself on the back, let me show you what is now possible with a bit more detail.

With this trait, it is possible to omit parameters that have default values set. Working with the [`ThrottleRequests` middleware](https://github.com/laravel/framework/blob/b92e27ded466e0994f3d5b3fa15c0337e62fa884/src/Illuminate/Routing/Middleware/ThrottleRequests.php#L47), if you wanted to keep the default values, but set a prefix (which is the last parameter in the list!), you can do the following...

```php
// before...
Route::stuff()
    ->middleware([
        'throttle:60,1,admin',
    ]);

// after...
Route::stuff()
    ->middleware([
        ThrottleRequests::with([
            'prefix' => 'admin',
        ]),
    ]);
```

Although we are not specifying the other parameters, the middleware will still receive the default values.

Passing variadic parameters is also pretty neat as you can pass an array to them! Working with the [`Authorize` middleware](https://github.com/laravel/framework/blob/0b12ef19623c40e22eff91a4b48cb13b3b415b25/src/Illuminate/Auth/Middleware/Authorize.php#L41) that has an `$ability` parameter followed by a `$models` variadic parameter, the following API is possible.

```php
// before...
Route::stuff()
    ->middleware([
        'can:create,App\Post,App\Video',
    ]);

// after...
Route::stuff()
    ->middleware([
        Authorize::with([
            'ability' => Abilities::CREATE,
            'models' => [
                Post::class,
                Video::class,
            ],
        ]),
    ]);
```

The `with()` method also does a bit of validation on the array you pass through. It ensures that any keys you pass exist on the middleware, so you don't accidently misspell a key. It will also make sure that all required parameters have been provided. Of course PHP will yell at you about this, but only if you hit the middleware. The in-built validation will happen whenever the route file is booted (not just when you hit a specific route), so you should know if you've made a mistake well before you ship to production. Note: this still all boils downs to a string, so route caching removes any performance hit from the validation.

The `with()` method works great if you have mulitple parameters to fill, however sometimes you are just working with a single variadic parameter, and you want to send through a single list. In this case, the name of the parameter usually doesn't matter to much. To satify this scenario, I've created the `in()` method.

```php
Route::stuff()
    ->middleware([
        EnsureReferrer::in([
            'facebook.com',
            'twitter.com',
        ]),
    ]);
```

Now that was a pretty long blog post just to introduce a package, but I wanted to run you through the journey, as that is always more exciting than the destination. I think there is still places to go with this, but it would possibly require some buy in from the framework. I'd love to push this futher any allow the values to be serialized so that the middleware receives the original types, i.e. if you pass `true` you get that value, not `"1"`.

For now that is the end of the journey. I enjoyed pushing this idea to an extreme to see what came out the otherside. If you like the idea of this, take it for a spin. I'd love to hear your thoughts on [Twitter](https://twitter.com/timacdonald87).

## Links

- [GitHub repository](https://github.com/timacdonald/has-parameters)
