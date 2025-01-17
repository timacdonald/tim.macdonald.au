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
    title: "Using Laravel's Policies and Route Model Binding without Eloquent",
    description: "I always thought Laravel's Policies and Route Model Binding were only able to be used with Eloquent models. Turns out I was wrong",
    date: new DateTimeImmutable('@1602800688', new DateTimeZone('Australia/Melbourne')),
    image: $url->asset('popo.png'),
);

?>

I made an assumption. I, for no reason at all, always assumed Laravel's Policy authorization and Route Model Binding functionality were only meant to be used with Eloquent models. Of course they work with Eloquent, but turns they work with any class. I wanna take a look at how and *why* you would even want to do this. Come along for the journey if that sounds interesting!

# Gates vs Policies

Before we get into this idea, I just want to cover when I would reach for a Gate or a Policy. I like to see Gates being used for authorization of things that don't exist in the application's domain and also for one off actions (usually when your RESTful API starts to look like an RPC call). Policies on the other hand are used when you need authorization around a "thing" in your domain.

Also to note is that a Gate may be the right answer in one domain and a Policy would be the right answer for a similar thing in another. Let's see what I mean by that.

Say we are working in an application that handles the financial information of a business. For whatever reason we have setup an endpoint you can hit and it triggers your application to deploy. For most applications that is going to be an RPC style call that might look something like this...

```
POST /api/v1/deploy-app
```

This endpoint is the perfect candidate for a Gate because servers being deployed are not within the financial domain.

```php
Gate::define('deploy-app', function (User $user): Response {
    //
});
```

However, in an application like Laravel Forge or Digital Ocean, deployed servers *are* within the problem domain, so an endpoint for a similar task would look more resourceful...

```
POST /api/v1/servers/3618/deployment
```

and within the application you can imagine that hitting this endpoint is going to create a new "Deployment" record for the "Server".

For a Forge-like application the authorization would live behind a Policy instead of a Gate, because we are now working with a "thing" that exists in the domain, i.e. a "Deployment".

```php
class DeploymentPolicy
{
    public function create(User $user, Server $server): Response
    {
        if ($user->team->doesNotOwn($server)) {
            return Response::deny('Your team does not have access to this server');
        }

        return Response::allow();
    }
}
```

You should [check out the docs](https://laravel.com/docs/8.x/authorization) which go into more detail about all this.

Now we've looked at what a Policy and a Gate are, I want to talk about the problem I came across in our application.

# 3rd party resources

In our app we proxy some HTTP requests to a 3rd party service. This service, for the sake of the blog post, allows us to retrieve information about different keyboards.

Within our Laravel application, all we are doing is checking that the user is allowed the make the request. Once authorized we shoot off a HTTP call to the 3rd party service and return the response payload to the front-end.

We are exposing a resourceful URL structure `/api/v1/keyboards/{keyboard}` for the front-end to interact with. We have also setup a resourceful controller, but we don't have an internal representation of the keyboard. Instead we just pass around the keyboard identifier in a `$keyboardId` variable as a `string`. Our controller looks something like this....

```php
class KeyboardController extends Controller
{
    public function show(string $keyboardId): array
    {
        $this->authorize('viewKeyboard', $keyboardId);

        $payload = KeyboardClient::get($keyboardId);

        return response()->json($payload);
    }
}
```

## Authorizing via a Gate

Because we don't have an internal representation of a keyboard, we utilised a `Gate` to handle the authorization.

```php
Gate::define('viewKeyboard', function (User $user, string $keyboardId): Response {
    //
});
```

This works perfectly fine, but the more I looked at it the more it started to smell because:

1. Passing around `$thingId` variables always smells like "[Primitive obsession](https://refactoring.guru/smells/primitive-obsession)" to me. I always opt for passing around an actual object instead of just the ID.
2. The endpoint we expose to the front-end is resourceful, so we should be working with an actual resource.
3. All our other resourceful controllers hit Policies because they interact with Eloquent objects.
4. Getting the ID injected into the controller method as a `string` instead of an Eloquent model via Route Mode Binding just isn't symmetrical with our other controllers.

So I set out on a mission to try and resolve some of these things, and turns out there is a really nice solution to each of them!

## Policies for POPOs

Although we now have auto-registering Policies in Laravel, I want to assume that doesn't exist to help demonstrate how this all comes together. When you want to tie an Eloquent model to a Policy you register it in your `AuthServiceProvider` like so...

```php
class AuthServiceProvider extends ServiceProvider
{
    protected $policies = [
        User::class => UserPolicy::class,
        Post::class => PostPolicy::class,
    ];

    // ...
}
```

This is why we can't use a Policy for our Keyboard controller; we don't have an internal representation of a keyboard! What would we use as the key in this array?

So, like, what...if...we...just created an internal representation 💡 We can just create a Plain Old PHP Object (POPO) to house the keyboard ID.

```php
class Keyboard
{
    public string $id;

    public function __construct(string $id)
    {
        $this->id = $id;
    }
}
```

Now we *do* have a way to link a class to a policy 🎉  We can go ahead and create a policy for a Keyboard now...

```php
$ php artisan make:policy KeyboardPolicy
```

and wire it all up in our `AuthServiceProvider` ...

```php
class AuthServiceProvider extends ServiceProvider
{
    protected $policies = [
        User::class => UserPolicy::class,
        Post::class => PostPolicy::class,
        Keyboard::class => KeyboardPolicy::class,
    ];

    // ...
}
```

I was super happy to see that this actually worked. I wasn't 100% sure it would, being that the `Keyboard` class does not extend Eloquent.

We also need to delete our Gate definition and add it to our `KeyboardPolicy`

```php
class KeyboardPolicy
{
    public function view(User $user, Keyboard $keyboard): Response
    {
        //
    }
}
```

You can see we are accepting a `Keyboard` instance instead of just a `string` in the Policy now!

Lastly for using a Policy instead of a Gate, we need to make sure we pass a `Keyboard` into the authorization call in the controller.

```php
class KeyboardController extends Controller
{
    public function show(string $keyboardId): array
    {
        // $this->authorize('viewKeyboard', $keyboardId);
        $this->authorize('view', new Keyboard($keyboardId));

        // ...
    }
}
```

Now we are getting very close to having a controller that matches our other resourceful controllers...but there is still one thing missing: we are still injecting a `string` based ID to the controller and we `new` up the `Keyboard` inline.

# Route Model Binding for POPOs

Route model binding is right up there as one of my favourite Laravel features. It isn't even a huge game changing feature, but it is a small feature I use so much that I would miss it more than I could ever express if it were to disappear from the framework.

But is route model binding only for Eloquent models? I'm now modelling a keyboard in our application, maybe I could use Route Model Binding for my POPO? It isn't "Route Eloquent Model Binding" sooooooo I'm gonna make a call and say I sure can use Route Model Binding with our new `Keyboard` POPO.

As we previously saw, our route is already resourceful...

```php
Route::get('/api/v1/keyboards/{keyboard}', [KeyboardController::class, 'show']);
```

We can't use [Implicit Route Model Binding](https://laravel.com/docs/8.x/routing#implicit-binding), because Laravel knows nothing of our little `Keyboard` class and how to construct it, so instead we will lean on some [Explicit Route Model Binding](https://laravel.com/docs/8.x/routing#explicit-binding).

```php
class RouteServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Route::bind('keyboard', function (string $value): Keyboard {
            return new Keyboard($value);
        });

        // ...
    }
}
```

This route model binding will look for a route parameter called `{keyboard}` and replace it with our POPO representation instead. For our application it is fine to accept any value passed through, but you might want to do some extra validation on the value beforehand. You could, for example, throw a 404 if the `$value` is not a UUID.

Route Model Binding was the last piece of the puzzle to make our controller feel much more resourceful. We can now use Route Model Binding to get our `Keyboard` object and pass it directly to our authorization call.

```php
class KeyboardController extends Controller
{
    public function show(Keyboard $keyboard): array
    {
        $this->authorize('view', $keyboard);

        // ...
    }
}
```

## The wrap

We now have a minimal representation of a `Keyboard` in our app, and because of that, we get the power of Route Model Binding and we can use a Policy instead of a Gate, which feels more expected to me and if you're going to do Object Orientated Programming, you might as well be working with objects, right?

## But wait, there's more!

Turns out I was telling fibs when I said we couldn't use Implicit Route Model Binding. As Martin Bean [pointed out on Twitter](https://twitter.com/martinbean/status/1316921371318943747) we _can_ use it if we make our `Keyboard` class implement [the `UrlRoutable` interface](https://github.com/laravel/framework/blob/43bea00fd27c76c01fd009e46725a54885f4d2a5/src/Illuminate/Contracts/Routing/UrlRoutable.php)!

Implementing this interface on the class means we can remove the `Route::bind(...)` call in the `RouteServiceProvider`. So you have the flexibility of discovering the binding via service provider or an implemented interface - you choose what is best for your project.

Here is what it might look like to implement the `UrlRoutable` interface on the `Keyboard` class.

```php
use Exception;
use Illuminate\Contracts\Routing\UrlRoutable;

class Keyboard implements UrlRoutable
{
    public string $id;

    public function getRouteKey(): string
    {
        return $this->id;
    }

    public function getRouteKeyName(): string
    {
        return 'keyboard';
    }

    public function resolveRouteBinding($value, $field = null): self
    {
        return tap(new self, fn (self $instance) => $instance->id = $value);
    }

    public function resolveChildRouteBinding($childType, $value, $field): void
    {
        throw new Exception("Errrm, I haven't looked into how this is used, so I'm just gonna bail for now");
    }
}
```

A couple of interesting things about implementing this interface:

1. We have had to remove our constructor because the interface methods are not `static` so the class must be instantiated in order to call them.
2. Because of reason 1, it is now possible to get the object into a bad state by calling `new Keyboard` and not passing in an ID. Unfortunately we also cannot make the constructor private as the container has to be able to create an instance to call the methods defined in the contract.
3. We can now use the class to also create URLs with the `route` helper, e.g. `route('keyboards.show', $keyboard)`

If I was going to continue with this interface, I'm going to want implement a static constructor to make working with it easier, otherwise we are always going to have to be playing this game...
```php
$keyboard = new Keyboard;

$keyboard->id = 'abc';
```

I'd much rather be able to create the object and have it in an always known state, so I will be adding a `make` method...

```php
use Exception;
use Illuminate\Contracts\Routing\UrlRoutable;

class Keyboard implements UrlRoutable
{
    public string $id;

    public static function make(string $id): self
    {
        return tap(new self, fn (self $instance) => $instance->id = $value);
    }

    public function getRouteKey(): string
    {
        return $this->id;
    }

    public function getRouteKeyName(): string
    {
        return 'keyboard';
    }

    public function resolveRouteBinding($value, $field = null): self
    {
        return self::make($value);
    }

    public function resolveChildRouteBinding($childType, $value, $field): void
    {
        throw new Exception(self::class.' may not be implicitly resolved from route bindings.');
    }
}
```

This allows us to easily instantiate the object into a good state...

```php
$keyboard = Keyboard::make('abc');
```

Thanks for the hot tip Martin!
