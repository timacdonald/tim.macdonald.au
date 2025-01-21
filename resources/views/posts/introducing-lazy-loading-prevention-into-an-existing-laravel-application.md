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
    title: 'Introducing lazy loading prevention into an existing application',
    description: "Laravel's new lazy loading prevention is fantastic, but depending on the size of your system might be hard to introduce. Here is an approach you might like to try out.",
    date: new DateTimeImmutable('@1631963212', new DateTimeZone('Australia/Melbourne')),
    image: $url->asset('n-1-detection.png'),
);

?>

Laravel recently introduced the ability to detect n+1 issues caused by lazily loading Eloquent relationships. This is a great feature and I wanted to roll it out on our project, however I wanted to do it in a manner that allowed us to incrementally fix existing issues while still having the app useable. This meant that simply killing execution because of an n+1 issue was not an approach I wanted to roll out. Luckily Laravel allows you to specify your own handling logic to decide how you would like to deal with lazy loading violations.

My first thought was to utilise the `report($exception)` function in Laravel to silently send the error to Sentry and continue on with the applications execution. However, because of the nature of the n+1 detection (it triggers for each database call), the size of our team, and the fact that our SPA might make several requests for each page - each of which might trigger n+1 detection, and the number of deployed environments we have (development, <abbr title="User Acceptance Testing">UAT</abbr>, <abbr title="System Integration Testing">SIT</abbr>) this approach would have potentially flooded our error tracker with exceptions eating up our quota. So I leaned on an existing Laravel feature to solve this problem for us (hint: I hope you've got your ticket!). But first...

## What is an n+1 query?

For those that are unfamiliar with what an n+1 query is, this can happen when you forget to eagerly load relationships. You know what, let's take a look at some queries to better understand the problem.

In this scenario we have a user that has 3 blog posts and each of those blog posts has 3 comments. We want to have access to all the blog posts and comments. To do this without hitting n+1 issues we would execute something similar to the following queries...

```sql
# knowing we are looking for posts for user 5...

select * from posts where `user_id` = 5; 

# result: [ ["id" => 1, "..."], ["id" => 2, "..."], ["id" => 3, "..."] ]

select * from comments where `post_id` in (1, 2, 3); 

# result: [ ["id" => 11, "..."], ["id" => 12, "..."], ["id" => 13, "..."] ]
```

The above example has 2 queries and does not suffer from the n+1 problem. The same thing with n+1 issues looks like the following...

```sql
# knowing we are looking for posts for user 5...

select * from posts where `user_id` = 5; 

# result: [ ["id" => 1, "..."], ["id" => 2, "..."], ["id" => 3, "..."] ]

select * from comments where `post_id` = 1; 

# result: [ ["id" => 11, "..."] ]

select * from comments where `post_id` = 2; 

# result: [ ["id" => 12, "..."] ]

select * from comments where `post_id` = 3; 

# result: [ ["id" => 13, "..."] ]
```

This is a common problem in many applications that utilise an <abbr title="Object Relational Mapper">ORM</abbr>. With this small example we can see that our query count has gone from 2 to 4. In a real-world application the increase in query count is likely much, much higher. 

In Laravel we have tooling to prevent this via [Eager Loading](https://laravel.com/docs/8.x/eloquent-relationships#eager-loading). Let's look at the Laravel code for the previous examples. 

The first uses Eager Loading and does not hit the n+1 problem...

```php
$request->user()->load('posts.comments');

// because we have "eager loaded" there are no more
// DB queries performed in this code snippet...

$request->user()->posts->each(function (Post $post): void {
    $post->comments; 
});
```

However, if we remove the Eager Loading we could easily introduce n+1 issues...

```php
// 1 database query to get the 3 posts...
$request->user()->posts->each(function (Post $post): void { 
    // 3 database queries to get each posts comments...
    $post->comments; 
});
```

<abbr title="Too long, didn't read">tl;dr;</abbr> eager loading for the win.

## Rolling out to our existing system

I've already outlined the concerns with rolling this out in our existing system. We needed a solution that would not completely interrupt our existing workflows so that our team can continue to deliver value, inform us that there is an issue via our error tracker, and not spam our error tracker in the process. The solution I reached for was a lottery.

A lottery gives someone or something a chance to win. There are odds in play and only `1 in X` wins. Let's look at how we can use a lottery to handle n+1 detection in our Laravel applications.

First thing we need to do is register our handling of the lazy loading violations. As [per the docs](https://laravel.com/docs/8.x/eloquent-relationships#preventing-lazy-loading), we do this in our service provider.

```php
class AppServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        // all our deployed environments, including our development, UAT, and 
        // SIT environments have APP_ENV set to "production" but we want these 
        // environments to report violations so we utilise another environment
        // variable to know which one is *actually* production.
        Model::preventLazyLoading(
           $this->app['config']->get('app.deployed_env') !== 'production'
        );

        // this is our custom handler that gets 
        // triggered when lazy loading is detected...
        Model::handleLazyLoadingViolationUsing(function (Model $model, string $relation): void {
            //
        });
    }
}
```

Next we need to setup our lottery. I wanted to ensure that I could configure the lottery on a per environment basis (we have many environments), which would also allow us to tweak the odds without having to push new code. This would be handy if we grossly over or underestimated the initial lottery odds.

To make this configurable, we can introduce an environment variable / configuration value.

```php
// file: config/logging.php

return [
    // ...

    /**
     * As we roll this out, we don't want to absolutely smash our
     * Sentry account in the case that there are many, many violations.
     * Instead we will only report based on a lottery.
     *
     * Our default lottery reports every 1 in 500 lazy loads.
     */
    'lazy_loading_reporting_lottery' => array_map(
        'intval',
        explode(',', env('LOG_LAZY_LOADING_DETECTION_LOTTERY', '1,500'))
    ),
];
```

This configuration option sets a sensible default of `1 in 500` for the lottery and also allows the odds to be configured via an environment variable as needed.

```bash
# file: .env

# Set the lottery odds to 1 in 250
LOG_LAZY_LOADING_DETECTION_LOTTERY=1,250
```

Now for the fun bit. We want to setup our lazy loading handler to report to Sentry every time it "wins" the lottery.

```php
Model::handleLazyLoadingViolationUsing(function (Model $model, string $relation): void {
    // retrieve our configured lottery odds...
    $lottery = $this->app['config']->get('logging.lazy_loading_reporting_lottery');

    // determine if this particular lazy loading violation "wins" the lottery...
    if (random_int(1, $lottery[1]) <= $lottery[0]) {

        // ding, ding! We have a winner. silently report to Sentry...
        report(new LazyLoadingViolationException($model, $relation));
    }
});
```

This custom handler meets our needs nicely in that it doesn't stop execution, so it doesn't interrupt developers when they are in the flow, it doesn't flood Sentry and use all of our quota, and it allows us to incrementally tighten the odds as we address n+1 issues throughout the application.

The `random_int($min, $max)` function is utilised as it gives an unbiased random number - after all you don't want to rig a lottery with bias!

## Throwing on local

I want our local environments to actually throw exceptions based on the lottery - as we don't have error tracking setup for our local development environments.

```php
Model::handleLazyLoadingViolationUsing(function (Model $model, string $relation): void {
    $lottery = $this->app['config']->get('logging.lazy_loading_reporting_lottery');

    if (random_int(1, $lottery[1]) <= $lottery[0]) {
        $exception = new LazyLoadingViolationException($model, $relation);

        // throw, rather than report, on "local"...
        $this->app['config']->get('app.env') === 'local'
            ? throw $exception
            : report($exception);
    }
});
```

## Response header

Something else I'm considering adding is a header to indicate that a lazy loading violation was triggered. This won't be on lottery basis - instead this would be added on every violation. You could probably add some nice smarts to it so that it tells you where in the code the violation was triggered. I tried adding the current stack trace, but that felt messy.

The response header is a nice signal that doesn't interrupt flow to indicate which endpoints are triggering issues while you are using the app.

Here is how you can add a indicator header to your response...

```php
Model::handleLazyLoadingViolationUsing(function (Model $model, string $relation): void {
    $lottery = $this->app['config']->get('logging.lazy_loading_reporting_lottery');

    if (random_int(1, $lottery[1]) <= $lottery[0]) {
        $exception = new LazyLoadingViolationException($model, $relation);

        $this->app['config']->get('app.env') === 'local'
            ? throw $exception
            : report($exception);
    }

    // Add a header to the response. It's crude but it works.
    if (! app()->runningInConsole()) {
        header('Lazy-Loading-Violation: 1');
    }
});
```


With all this in place we can slowly but surely move towards reporting each and every lazy loading violation in our system.

## Previous art

I can't take credit for any of this. I saw this "lottery" approach in Laravel itself and just reused it for this problem. The file based session driver uses a lottery to determine when it should clean up old session files. Check out your `config/session.php` file and you will see the lottery!

Have you used a lottery for something interesting? Hit me up on <a href="https://twitter.com/timacdonald87">Twitter</a> - I'd love to hear what you've done with one in your applications.
