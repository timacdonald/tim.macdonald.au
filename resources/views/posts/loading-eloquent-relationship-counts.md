<?php

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
    title: 'Loading Eloquent relationship counts',
    description: 'There are several ways to load relationship counts on eloquent models. I\'m going to explore the options and introduce you to a new one.',
    date: DateTimeImmutable::createFromFormat('Y-m-d H:i:s', '2018-11-01 12:00:00', new DateTimeZone('Australia/Melbourne')),
    image: $url->asset('loading-counts.png'),
);

?>

It is often useful to show the number of related models a given instance has, but not actually need any specific information about the related models, just how many exist. In this scenario you do not want to load all of the related models into memory to count them, we want our database to do the heavy lifting for us. Laravel offers a number of ways to retrieve relationship counts. 2 have been around for a while, but there is a new kid on the block:

- the query builder
- the relationship
- an eloquent collection 👀

We are going to take a quick look at all of them, and when you would use one over the other. If you are pretty comfortable with the first two options you can [skip to the eloquent collection](#eloquent-collection) content.

## Query builder

We use the query builder to retrieve our eloquent records and hydrate our models. Every time we hit the database we are impacting the performance of our app, so the fewer hits we make, the better. Luckily for us the query builder is able to retrieve relationship counts while it is loading out models from the database, and does not have to hit the database a second time to do it.

To add relationship counts to the hydrated models we use the `withCount($relationship)` method. The number of relationships will be stored on the model as an attribute with the format `"{$relationship}_count"`.

```php
$sandwiches = Sandwich::orderByFreshness()->withCount('fillings')->get();

$sandwiches->first()->fillings_count; // number of related fillings
```

The `withCount()` method also allows you to get multiple values at once by passing in an array instead of a string.

```php
$sandwiches = Sandwich::orderByFreshness()->withCount(['meats', 'salads'])->get();

$sandwiches->first()->salads_count; // number of related salads
```

Taking things up a notch, we are also able to load counts on related models when we are eager loading them.

```php
$sandwiches = Sandwich::with(['purchase' => function ($query) {
    $query->withCount('drinks');
})->get();

$sandwiches->first()->purchase->drinks_count; // number of drinks related to the sandwich's purchase.
```

If you find that you are repeating the `withCount($relationship)` calls frequently, as you always need a particular relationship count available on a model, you might consider adding it the the model's `$withCount` attribute. This will mean that whenever you pull the model out of the database, it will have the count added without you having to explicitly call it on the query builder.

```php
class Sandwich extends Eloquent
{
    protected $withCount = [
        'fillings',
    ];
}

//...

$sandwich = Sandwich::orderByFreshness()->get();

$sandwiches->first()->fillings_count; // number of related fillings
```

### Summary

As a general rule I would _always_ reach for the the query builder first before using any of the other methods we are about to look at. It is less likely you will hit an n+1 problem if you are using the query builder to get your relationship counts loaded on the model.

## Relationships

Relationship methods allow us to call the `count()` method on them to get the relationship count on the fly.

```php
class Sandwich extends Eloquent
{
    public function fillings()
    {
        return $this->hasMany(Filling::class);
    }
}

// ...

$sandwich->fillings()->count(); // number of related fillings
```

Something to keep in mind here is that every time you call the `count()` method the app will hit the database.

```php
$sandwich->fillings()->count();
$sandwich->fillings()->count();
$sandwich->fillings()->count();
// total database hits: 3
```

**Quick aside:** Something I've done to reduce the number database calls, and also to keep things standardised across different code paths, is add an accessor for the relationship count so that you can access it the same way you would if it was eager loaded, but also to cache the value so subsequent calls do not result in database hits. Keep in mind that this does not eliminate the potential n+1 problem you might encounter when working with a collection of models.

```php
class Sandwich extends Eloquent
{
    public function fillings()
    {
        return $this->hasMany(Filling::class);
    }

    protected function getFillingsCountAttribute($value)
    {
        return $value ?? $this->fillings_count = $this->fillings()->count();
    }
}

// ...

$sandwich->fillings_count;
$sandwich->fillings_count;
// total database hits: 1
```

**Back on track:** As I mentioned, the query builder should be your goto option for loading relationship counts, so here are a couple of ways you could do this while working with a single model. We'll look at a  `show` route for an example...

```php
public function show($id)
{
    $sandwich = Sandwich::withCount('fillings')->findOrfail($id); // explicitly call withCount()

    // ...
}
```

Or if you are using route model binding you could rely on the model's `$withCount` attribute as shown previously. This would mean that the model instance injected into the controller will already have the count attribute loaded.

```php
public function show(Sandwich $sandwich)
{
    $sandwich->fillings_count; // already available

    // ...
}
```

### Summary

Only use the relationship if you working with a single instance of the model, not a collection of them, and see if you can lean on the query builder first. Can be handy for a quick "on the fly" relation count where a single extra query is not the end of the world and gets the job done.

## Eloquent collection

Laravel `5.7.10` has just [added the ability to load relationship counts via the eloquent collection](https://github.com/laravel/framework/pull/25997). With all the existing ways of loading relationship counts, it might not be instantly clear why this is needed. One scenario you might find yourself in is working with polymorphic relationships. By the very nature of polymorphic relationships, the relationships available on one polymorphic relation may not be available on another...wait...what did I just say 🤔

Why don't we look at this problem with a bit more code.

Our Sandwich SaaS wants to track everything that is happening with an event log. Every time a model is created, updated, etc. we log the activity so that we can see a history of what has been happening in the system.

Here is our `Event` class.

```php
class Event extends Eloquent
{
    public function eventable()
    {
        return $this->morphTo();
    }
}
```

We also have our `Sandwich` class and `Drink` class. Notice the `$sandwich->fillings()` and `$drink->ingredients()` relationships.

```php
class Sandwich extends Eloquent
{
    public function events()
    {
        return $this->morphMany(Event::class, 'eventable');
    }

    public function fillings()
    {
        return $this->hasMany(Filling::class);
    }
}

class Drink extends Eloquent
{
    public function events()
    {
        return $this->morphMany(Event::class, 'eventable');
    }

    public function ingredients()
    {
        return $this->hasMany(Ingredient::class);
    }
}
```

We want to create a paginated list of Events for our SaaS administrators to be able to view. We have been asked to show a sandwich's `fillings_count` and a drink's `ingredients_count` on the page whenever we are displaying them.

On a first attempt we might try and do something like this...

```php
$events = Event::latest()->with(['eventable' => function ($query) {
    $query->withCount(['fillings', 'ingredients']);
}])->paginate();
```

But this will not work as we would hope 😩

As an eventable may be both a `Sandwich` or a `Drink` we will run into an error as there is no `$sandwich->ingredients()` relationship! This is a situation where loading relationship counts using the eloquent collection is the best tool for the job.

Step one is to retrieve the events and the related "eventable" models.

```php
$events = Event::latest()->with('eventable')->paginate();
```

Now we need to get all the eventable models split into their own collection groups, based on their class.

```php
$events = Event::latest()->with('eventable')->paginate();

$groups = $events->map(function ($event) {
    return $event->eventable;
})->groupBy(function ($eventable) {
    return get_class($eventable);
});
```

What we now have is a collection of collections. The parent collection is an associative array where the keys represent the class. Here is a rough way to imagine it...

```php
[
    'App\Models\Sandwich' => [
        $sandwich_1,
        $sandwich_2,
    ],
    'App\Models\Drink' => [
        $drink_1,
        $drink_2,
    ],
]
```

As you can see we are now able to confidently call methods on each sub collection as we know that the collection contains only one class, and therefore all have the same relationships available.

```php
$events = Event::latest()->with('eventable')->paginate();

$groups = $events->map(function ($event) {
    return $event->eventable;
})->groupBy(function ($eventable) {
    return get_class($eventable);
});

$groups[Sandwich::class]->loadCount('fillings');
$groups[Drink::class]->loadCount('ingredients');
```

Now all of our sandwiches and drinks have their respective relationship counts loaded, which was something we could not do with the query builder or the relationship on the model. It should be noted that we are introducing one extra database hit with each class group here, but that is much better than hitting the database on each model, especially if you are working with a large number of them.

As you would probably expect, you can also load multiple relationship counts similar to how we did it previously using the query builder.

```php
$groups[Sandwich::class]->loadCount(['meats', 'salads']);
```

### Summary

There are probably very few instances when you require the ability to load a relationship count on a collection, but working with polymorphic relationships are definitely one of them. If you think of any others I would love to hear about them.

Hopefully this has helped you understand the ways you can load elqouent relationship counts with Laravel. Remember to always reach for the query builder until it no longer works for you.
