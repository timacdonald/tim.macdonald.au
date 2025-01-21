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
    title: 'Overriding Laravel Mix during testing in 5.8',
    description: "Laravel's global mix helper function can be replaced during testing in Laravel 5.8. Here is why, and how, you might want to do this.",
    date: new DateTimeImmutable('@1541293200', new DateTimeZone('Australia/Melbourne')),
    image: $url->asset('mix.png'),
);

?>

On a project I am currently working on I ran into an issue due to the global `url($asset)` helper function. In my feature tests I was hitting a route that used the global helper, but I was passing in a dynamic value. This caused me a bit of a headache. Let me jump into what happened, what solutions are currently possible, and how in Laravel 5.8 we can override the `mix()` helper.

We start with a `Company` model. There is no UI for adding or deleting a company, as it happens so rarely, so to get this app off the ground we decided that I would just create them using Tinker. When a new company comes on board we create them, with Tinker, and give each of them a `resouce_key` attribute that we use to identify their logo (and a few other things). Each company also provides me with an image of their logo (and a few other assets).

Using Laravel Mix I publish their logo to `/public/images/logos/{resource_key}.png`, meaning I call the asset with the mix helper like so...

```html
<img src='{{ url("images/logos/{$company->resource_key}.png") }}' alt='...' height='200' width='200'>
```

So far so good, right? But what happens when we go to test this? Why don't we setup a factory and a test and see where I was hitting a wall.

```php
$factory->define(App\Models\Company::class, function ($faker) {
    return [
        'name' => $name = $faker->company,
        'resource_key' => str_slug($name, '_'),
    ];
});
```

So this factory is creating a fake company name and "slugifying" that name for the resource key. Seems sensible to me...but you might already be able to tell what is going to happen next...

```php
public function test_some_stuff()
{
    $company = factory(Company::class)->create();

    // this route uses the mix helper as we illustrated previously...

    $response = $this->get("{$company->slug()}/users");
}
```

During this test if the company was given the resource key "amazon" we would get an exception with the message: "Unable to locate Mix file: /images/logos/amazon.png".  Why? Well the `resource_key` is different on each test run. When we call the `url()` helper it reaches into the `mix-manifest.json` which contains an array of files. Here is an example of what the `mix-manifest.json` might contain.

```json5
{
    "/images/logos/apple.png": "/images/logos/apple.png?id=3fbeeaed8340a72d2a4e",
    "/images/logos/google.png": "/images/logos/google.png?id=56df966576b7a6d2bb71"
}
```


As you can see we do not have an `amazon.png` in our manifest, so when we request it from mix in our view it throws this exception.

## Some possible solutions

### Known images

One solution might be to specify, in the factory, a set of known images.

```php
$factory->define(App\Models\Company::class, function ($faker) {
    return [
        'name' => $name = $faker->company,
        'resource_key' => $faker->randomElement(['github', 'gitlab', 'bitbucket']),
    ];
});
```

But this is tying my tests to my production data, which does not feel right to me. Another thing is that we do occasionally remove businesses. So this means I would have to update my tests when this happens. Not the greatest solution - but it is an option.

### Dedicated test image

Another solution might be to create a dedicated image just for testing. Perhaps I add a `"testing.png"` to Mix and in my factory I specify that all businesses use that resource key...

```php
$factory->define(App\Models\Company::class, function ($faker) {
    return [
        'name' => $name = $faker->company,
        'resource_key' => 'testing',
    ];
});
```

This would also work, and is probably an okay solution. I won't have to update my tests when my production data / assets change, so that is a bonus!

### These both suck

Unfortunately we run into another issue with both of these approaches. We **have** to run `npm run dev` before our tests will pass as we need mix to publish the `mix-manifest.json` file, otherwise we are back in Exception town.

### An okay-ish option

One last solution I came up with is to create the manifest file during the test and clean it up afterwards. My tests ended up looking like this...

```php
public function test_company_data_is_visible_on_information_page()
{
    $this->app->singleton('path.public', function () {
        return __DIR__;
    });
    $manifest = public_path('mix-manifest.json');
    $content = json_encode(["/images/logos/{$company->resource_key}.png" => '/whatever.png']);
    file_put_contents($path, $content);
    $company = factory(Company::class)->create();

    // test some things...

    unlink($manifest);
}
```

One thing to note here is that we need to override the public directory otherwise when we run our tests we will overwrite the *actual* mix manifest in our public directory.

The problem with this approach is that the logo is needed on many different pages and across a bunch of tests, so I have to extract this stuff out to a trait. This is the solution I landed on and am currently using but it just was not the nicest experience and I knew we could do better!

## Just don't use mix

These examples are all revolving around the logo, but we also have a dedicated SASS file, in version control, for some of the companies. This means we do want to use mix to utilise the minification, and versioning, that it provides.

## Overriding Laravel Mix

During Adam Wathan's talk [Resisting Complexity](https://www.youtube.com/watch?v=dfgtKb-VpRk) I was introduced to the idea that you could bind a function to the container. This is a really nifty trick and something I hadn't seen before.

This technique seemed like the perfect candidate to help me override the mix helper functionality in my test. Wrapping up the existing mix helper code into an invokable class, binding that class to the container as a singleton, and boom 💥 we can replace the global helper functionality on the fly.

The mix global helper function now resolves the invokable `Illuminate\Foundation\Mix` class from the container and invokes it, which means we can replace it in the container with anything, but really I just want to silence mix altogether. In Laravel 5.8 we can now do the following to override the mix helper...

```php
public function test_company_data_is_visible_on_information_page()
{
    $this->app->singleton(Mix::class, function () {
        //
    });
    $company = factory(Company::class)->create();

    // test some things...
}
```

I find this approach much nicer than my previous solutions. It is probably a bit of an edgecase to have to do this, and as this app grows no doubt we will be making changes like giving a UI to add and remove logos which will remove the need for this, but hopefully if you ever do need it you will find this helpful!

You can checkout the [PR adding this new functionality over on GitHub](https://github.com/laravel/framework/pull/26289).
