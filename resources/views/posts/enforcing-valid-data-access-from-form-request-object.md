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
    title: 'Enforcing valid data access from a form request object',
    description: "A little trick I have been implementing recently to ensure that data accessed from a Laravel request object has first been validated.",
    date: new DateTimeImmutable('@1491789600', new DateTimeZone('Australia/Melbourne')),
    image: $url->asset('accessing-validated-request-inputs.png'),
);

?>

Making sure you validate incoming request data from the user is crucial for security and ensuring your app stays in a valid state. Validating data is easy peasy lemon squeezy with Laravel's [validation rules](https://laravel.com/docs/master/validation#available-validation-rules) in combination with [form request objects](https://laravel.com/docs/master/validation#creating-form-requests).

Another thing Laravel does is make it super easy to access request input. A form request object extends Laravel's request object, so accessing a *title* input is as easy as `$request->title`, but there lays the potential issue. It can be too easy, let's take a look at why.

## Creating models from request data

In your controller you have probably done something like this to create a model:

```php
public function store(PostRequest $request)
{
    Post::create([
        'title' => $request->title,
        'content' => $request->content
    ]);

    // or

    Post::create($request->only(['title', 'content']));
}
```

Both a perfectly valid options and I've been doing this myself in my projects. However, recently I was thinking it is very possible to access input from the form that has not been validated. Looking at this example, how do I **know** those are validated inputs?

When I'm in my controller I do not know if the form has validated the `title` or `content` input...and that scares me. Sure, I'm a big kid and should trust that I did validate the data, but with such an important piece of the puzzle I don't like to take any risks.

## Enforcing valid data access

Let's wrap ourselves up in a security blanket so we can sleep easy. In a form request object we set our rules as per normal:

```php
public function rules()
{
    return [
        'title' => 'required|string|max:255',
        'content' => 'required|string',
    ];
}
```

and then we add a new method to our request object to keep ourselves in check:

```php
public function validatedInputs()
{
    return $this->only(array_keys($this->rules()));
}
```

This method is getting all the rules array keys and *only* returning the input of those keys. Now in our controller we know that our input has been validated by our rules.

```php
public function store(PostRequest $request)
{
    Post::create($request->validatedInputs());
}
```

😌 that makes me feel better.

## Notes

- This could of course be extended to include other helpful methods such as `validatedInput($key, $default)`, `onlyValidatedInput($keys)` etc.
- This works great with flat arrays, but once you you are creating more complex rules such as the following, things start to get a little hairy, but by then you're probably doing other trickery anyway.

```php
public function rules()
{
    return [
        'users.*.email' => 'required|email',
    ];
}
```

## Update

I attempted to [PR this into the core](https://github.com/laravel/framework/pull/18665), however it did not get merged. Thankfully though Joseph Silber‏ added a much more [comprehensive set of PRs](https://github.com/laravel/framework/pull/19033) to add this functionality to the core which will be baked into the upcoming Laravel release 5.5. Very cool - looking forward to trying it!
