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
    title: 'Dedicated query builders for Eloquent models',
    description: "As my current project grew, so did my models. In a hunt for thinner models, I realised it was possible to extract model scopes to a dedicated query builder class. I'll show you how to do this, and a few things to keep in mind if you implement this refactor.",
    date: new DateTimeImmutable('@1556327460', new DateTimeZone('Australia/Melbourne')),
    image: $url->asset('dedicate-eloquent-model-query-builders.png'),
);

?>

This blog post is an in-depth walk though of a [tweet I sent out a few weeks ago](https://twitter.com/timacdonald87/status/1109964446766497793). You can probably get everything you need from the tweet and the comments on it, but I've written this to make it Google-able if ever needed, and to cover some caveats you should be aware of.

Out of the box Laravel comes with a long list of conveniences and scaffolding that help developers build out web applications quickly and efficiently. One of those conveniences is the co-location of eloquent attributes, relationships, and scopes on the model class. As your application grows, more than likely your models will also grow as you add more attribute mutators, new relationships, additional query scopes, and other functionality. The model is the perfect place for these things initially, but at a point I personally find I want the ability to thin out my models.

The goal here is to thin models by moving query scope methods from the model to a dedicated query builder class, on a per model basis. If your models get larger than you would prefer, I feel that this is the natural place for scopes to be relocated to. Because scopes are an indirect way of extending the eloquent builder on a per class basis, extending the actual query builder on a per class basis just *feels right* to me.

A couple of ancillary benefits also arise from introducing this pattern, including:

- The ability to click through to method definitions for query scopes (because we are removing the `"scope"` prefix)
- Static analysis is able to understand your scopes (because we are removing the magic behind them)

It also does not change the public facing API for how you access / interact with your eloquent queries! But enough prologue, let's look some code.

## The team model

We are going to use the following model as a starting point to show how to refactor out query scope methods to a dedicated query builder. In reality I would not apply the refactor to this actual model until it was *substantially* bigger, but we can all use our [imagination](https://media.giphy.com/media/QIiqoufLNmWo8/giphy.gif) to pretend we have a bunch more stuff on this model.

```php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Team extends Model
{
    public const MAX_MEMBERS = 4;

    protected $casts = [
        'ranking' => 'integer',
        'is_public' => 'boolean',
        'is_active' => 'boolean',
    ];

    /**
     * Scopes...
     */

    public function scopeWherePublic($builder, $boolean = true)
    {
        $builder->where('is_public', '=', $boolean);
    }

    public function scopeWherePrivate($builder)
    {
        $this->scopeWherePublic($builder, false);
    }

    public function scopeWhereRankedHigherThan($builder, $team)
    {
        $builder->where('ranking', '>', $team->ranking);
    }

    public function scopeWhereFull($builder)
    {
        $builder->has('users', '=', static::MAX_MEMBERS);
    }

    /**
     * Relationships...
     */

    public function users()
    {
        return $this->hasMany(User::class);
    }

    // a bunch more stuff...
}

```

In this example model we have four query scope methods that we are looking to refactor off the model to a dedicated query builder:

- `Team::wherePublic()`
- `Team::wherePrivate()`
- `Team::whereRankedHigherThan($team)`
- `Team::whereFull()`

## The query builder

The base eloquent model has a `newEloquentBuilder` method that returns the same query builder class for each type of model in your application.

```php
/**
 * Create a new Eloquent query builder for the model.
 *
 * @param  \Illuminate\Database\Query\Builder  $query
 * @return \Illuminate\Database\Eloquent\Builder|static
 */
public function newEloquentBuilder($query)
{
    return new Builder($query);
}
```

As this method is located on the model, we are able to override it in our application models and return our dedicated query builder. To get started we will create an empty class that extends the base eloquent builder.

```php
namespace App\Builders;

use Illuminate\Database\Eloquent\Builder;

class TeamBuilder extends Builder
{
    //
}
```

Now we have created the dedicated query builder, we will override the `newEloquentBuilder` method on our `Team` model. Note that you will want to create a builder class for each model you are applying this refactor to.

```diff
namespace App\Models;

+use App\Builders\TeamBuilder;
use Illuminate\Database\Eloquent\Model;

class Team extends Model
{
    public const MAX_MEMBERS = 4;

    protected $casts = [
        'ranking' => 'integer',
        'is_public' => 'boolean',
        'is_active' => 'boolean',
    ];

+    public function newEloquentBuilder($query)
+    {
+        return new TeamBuilder($query);
+    }

    /**
     * Scopes...
     */

    public function scopeWherePublic($builder, $boolean = true)
    {
        $builder->where('is_public', '=', $boolean);
    }

    public function scopeWherePrivate($builder)
    {
        $this->scopeWherePublic($builder, false);
    }

    public function scopeWhereRankedHigherThan($builder, $team)
    {
        $builder->where('ranking', '>', $team->ranking);
    }

    public function scopeWhereFull($builder)
    {
        $builder->has('users', '=', static::MAX_MEMBERS);
    }

    /**
     * Relationships...
     */

    public function users()
    {
        return $this->hasMany(User::class);
    }

    // a bunch more stuff...
}
```

## Progressive refactor

The great thing about this refactor is that it isn't all or nothing. We are now using our `TeamBuilder` class whenever we interact with query scopes for the `Team` model, however all the scopes still located on the model will continue to work as expected. You could leave existing scopes on the model and only add new scopes to the dedicated query builder, if your heart so desires, although I personally wouldn't recommend it as it might cause confusion when trying to locate the scope methods because they are split across two classes.

I'm going to break this refactor down into the four smallest steps possible to make sure we don't miss anything along the way.

## Step 1: relocate the scope

I'm going to walk through moving the `scopeWhereRankedHigherThan` method from the model to the new builder. First thing is to remove it from the model...

```diff
namespace App\Models;

use App\Builders\TeamBuilder;
use Illuminate\Database\Eloquent\Model;

class Team extends Model
{
    public const MAX_MEMBERS = 4;

    protected $casts = [
        'ranking' => 'integer',
        'is_public' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function newEloquentBuilder($query)
    {
        return new TeamBuilder($query);
    }

    /**
     * Scopes...
     */

    public function scopeWherePublic($builder, $boolean = true)
    {
        $builder->where('is_public', '=', $boolean);
    }

    public function scopeWherePrivate($builder)
    {
        $this->scopeWherePublic($builder, false);
    }

-    public function scopeWhereRankedHigherThan($builder, $team)
-    {
-        $builder->where('ranking', '>', $team->ranking);
-    }

    public function scopeWhereFull($builder)
    {
        $builder->has('users', '=', static::MAX_MEMBERS);
    }

    /**
     * Relationships...
     */

    public function users()
    {
        return $this->hasMany(User::class);
    }

    // a bunch more stuff...
}
```

and place it as is on the new builder class...

```diff
namespace App\Builders;

use Illuminate\Database\Eloquent\Builder;

class TeamBuilder extends Builder
{
-    //
+    public function scopeWhereRankedHigherThan($builder, $team)
+    {
+        $builder->where('ranking', '>', $team->ranking);
+    }
}
```

## Step 2: remove the $builder parameter

When the scope is on the model, Laravel under the hood passes the query builder instance into the scope - but now we are working directly with the query builder, so this won't happen anymore. But do not fret - we just remove the first `$builder` parameter and replace any reference to it in the method body with `$this`.

```diff
namespace App\Builders;

use Illuminate\Database\Eloquent\Builder;

class TeamBuilder extends Builder
{
-    public function scopeWhereRankedHigherThan($builder, $team)
+    public function scopeWhereRankedHigherThan($team)
    {
-        $builder->where('ranking', '>', $team->ranking);
+        $this->where('ranking', '>', $team->ranking);
    }
}
```

## Step 3: remove the "scope" prefix and fix the casing.

In order to tell eloquent that the methed on the model is a query scope and not just another method on the model, we start the method name with `"scope"`. We can now remove this prefix, but we also need to make sure the method matches the casing used when we call query scope, so we also need to lowercase the first letter in the method name after removing the prefix.

```diff
namespace App\Builders;

use Illuminate\Database\Eloquent\Builder;

class TeamBuilder extends Builder
{
-    public function scopeWhereRankedHigherThan($team)
+    public function whereRankedHigherThan($team)
    {
        $this->where('ranking', '>', $team->ranking);
    }
}
```

## Step 4: return $this

The last piece of magic Laravel does for us under the hood is ensure that if we do not return a value from the scope, it will automatically return the query builder instance for us. But now we need to make sure we do that ourselves.

```diff
namespace App\Builders;

use Illuminate\Database\Eloquent\Builder;

class TeamBuilder extends Builder
{
    public function whereRankedHigherThan($team)
    {
        $this->where('ranking', '>', $team->ranking);
+
+        return $this;
    }
}
```

and that is all the steps involved in this refactor. Applying the refactor the the rest of the query scopes we end up with the following builder class...

```php
namespace App\Builders;

use App\Models\Team;
use Illuminate\Database\Eloquent\Builder;

class TeamBuilder extends Builder
{
    public function wherePublic($boolean = true)
    {
        $this->where('is_public', '=', $boolean);

        return $this;
    }

    public function wherePrivate()
    {
        $this->wherePublic(false);

        return $this;
    }

    public function whereRankedHigherThan($team)
    {
        $this->where('ranking', '>', $team->ranking);

        return $this;
    }

    public function whereFull()
    {
        $this->has('users', '=', Team::MAX_MEMBERS);

        return $this;
    }

    public function whereRankedHigherThan($team)
    {
        $this->where('ranking', '>', $team->ranking);

        return $this;
    }
}
```

...and our model is looking much cleaner now as we have relocated all of the query scopes.

```php
namespace App\Models;

use App\Builders\TeamBuilder;
use Illuminate\Database\Eloquent\Model;

class Team extends Model
{
    public const MAX_MEMBERS = 4;

    protected $casts = [
        'ranking' => 'integer',
        'is_public' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function newEloquentBuilder($query)
    {
        return new TeamBuilder($query);
    }

    /**
     * Relationships...
     */

    public function users()
    {
        return $this->hasMany(User::class);
    }

    // a bunch more stuff...
}
```

## Caveat: multi-where scopes

If you are chaining multiple `where`s in a single scope then you need to group them manually when using this new approach. [Eloquent under the hood detects](https://github.com/laravel/framework/blob/bc71036081f936b9bd6e856a3e97da27aeb35712/src/Illuminate/Database/Eloquent/Builder.php#L996-L1006) how many `where`s you applied in a single scope and automatically groups them if you add more than one. Here is an example of how you would manually group multiple `where`s.

```php
// (before) on the model...

public function scopeActiveOrPublic($builder)
{
    $builder->where('is_active', '=', true)->orWhere('is_public', '=', true);
}

// (after) on the dedicated builder...

public function activeOrPublic()
{
    $this->where(function ($query) {
        $query->where('is_active', '=', true)->orWhere('is_public', '=', true);
    });
}
```

You can see this in action in [this Twitter thread](https://twitter.com/erikgaal/status/1112617973993390081) where [Erik](https://twitter.com/erikgaal) pointed this out to me. Thanks Erik!

I personally write very small scopes that generally only have one `where` method in them. I then combine scopes at a higher level (i.e. when calling them) to get the desired groupings. This gives me more fine-grained control and reuse of my scopes.

## Oh! So I can like bind this to the container then!?!

Well, like, you could...but you shouldn't <abbr title="In my opinion">IMO</abbr>. Although this approach removes some of the work done under the hood - it doesn't remove all of it. When the builder is created, eloquent still does some trickery to set the model and do a few other things to the builder before returning it.

Also, binding it to the container sounds like you are going to want to mock it. Mocking the database sounds like a bad time. Just continue to access the query builder like you always have - directly off the model itself. i.e.

```php
Team::whereActive()->etc();

// or

Team::query()->whereActive()->etc();
```

I'd recommend wrapping your eloquent scope calls in a repository if you really need to mock something for a specific test.

Another thing is that you are going to have to bind each builder to the container individually. Does not sound nice...but I'll concede: I am not your Dad and you can do what you want. If for whatever reason you *do* want to bind it to the container, do it like this in a service provider...

```php
public function register()
{
    $this->app->bind(\App\Builders\TeamBuilder::class, function () {
        return \App\Models\Team::query();
    });
}
```


## Sharing scopes

Another topic that came up in the Twitter thread was sharing scopes with traits. If you want to share a handful of scopes with a particular set of models, then traits are still a great way to go about that. You can use the trait on the model as you have been doing, or you could use the dedicated query builder approach and use the trait on the query builders (ensuring that the way you are defining the scope methods matches where you put them).

If, however, you are looking to share scopes across *all* your models, you can also utilise inheritance. I've usually got a handful of general helper scopes that I use across all of my models. To do this I create a base query builder (like the one shown below) and all my model specific query builders inherit from this. Yes, it is another layer of inheritance, but I do the same thing with my models to add a couple of helpers. It works quite well.

```php
namespace App\Builders;

use Illuminate\Database\Eloquent\Builder as BaseBuilder;

class Builder extends BaseBuilder
{
    public function whereNot($model)
    {
        $this->whereKeyNot($model->getKey());

        return $this;
    }

    // other shared scopes...
}
```

## Static analysis

If you are into static analysis, you will be happy to know this approach to query scopes works perfectly well without any helper packages or plugins. PHPStan (with as many strict rules as I could find) and Psalm are completely happy! You need to specify a couple of return typehints on your models, and then ensure you start your queries in a specific way, but after that you are ready to roll. For a start, you'll need to specify a return type on the static `query` method on your model.

```php
namespace App\Models;

use App\Builders\TeamBuilder;
use Illuminate\Database\Query\Builder;
use Illuminate\Database\Eloquent\Model;

class Team extends Model
{
    public static function query() : TeamBuilder
    {
        return parent::query();
    }

    public function newEloquentBuilder($query)
    {
        return new TeamBuilder($query);
    }

        // ...
}
```

Now when you want to start a query from the model, you kick things off by calling the static `query()` method, which Eloquent usually does under the hood for you.

```php
$teams = Team::query()
    ->wherePublic()
    ->whereNot($request->user()->team)
    ->whereRankedHigherThan($request->user()->team)
    ->paginate();
```
You will also need to go through and add parameter typehints and return typehints to your query builder methods, but the `Model::query()` return typehint is the one that will help make the static analysis dream a reality.

## The wrap

Hopefully you've found this approach interesting. I don't think you should reach for this technique out of the box, but I find it a great way to whip some growing models into shape by splitting out the query scopes to dedicated classes.

You should always consider if this technique is appropriate for you application, your team, and your programming style before implementing the refactor.

If you have any questions or thoughts on any of these ideas I would love to chat - [reach out on Twitter](https://twitter.com/timacdonald87) anytime.
