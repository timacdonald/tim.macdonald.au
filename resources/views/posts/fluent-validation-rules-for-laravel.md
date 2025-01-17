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
    title: 'Fluent validation rules for Laravel',
    description: "Laravel validation rules are great, but wouldn't it be awesome if there was a fluent interface for all the rules.",
    date: new DateTimeImmutable('@1483189080', new DateTimeZone('Australia/Melbourne')),
    image: $url->asset('fluent-validation-rule-builder.png'),
);

?>

## What is data validation

Software applications, both web and native, are continually receiving user "input". When you post a status to Facebook, or when you enter you date of birth on a form, you are sending input to the application. That input needs to be validated. If you are sending you date of birth, the application should ensure that the date exists, and you should not be able to send `"99/01/2010"` as that date does not exist. That input just failed validation and you, as the user, should receive a prompt in the app to let you know the data is invalid.

Validation ensures that the input is in a format, or is a value, that the system is expecting. It can also have security implications for the system, but we won't go into here.

Let’s say we are allowing a user to register with our app. We might have the following requirements for their input:

- __Email__
  - Must be provided.
  - Must be a valid email address.
- __Password__
  - Must be provided.
  - Must be at least 6 characters in length.

## Laravel validation

In order to use the validation tools provided by Laravel out of the box, we build up an array of rules. These can be pipe `|` separated rules, or another array. We'll stick with the pipes for now. Here are the Laravel validation rules that correspond to our registration requirements:

```php
$rules = [
    'email' => 'required|email',
    'password' => 'required|min:6',
];
```

Once you have your rules mapped out like this, you can pass them to a validator object to determine if the data passes, or fails, and perform actions based on the outcome. The validator will also provide you with error messages, or you can specify custom messages.

## Rule builder

To make the creation of validation rules feel more at home for developers who prefer to use objects instead of magic strings, I’ve develop a [rule builder](https://github.com/timacdonald/rule-builder) that implements the validation rules with a [fluent interface](http://martinfowler.com/bliki/FluentInterface.html).

Laravel as a whole has a large focus on the feel of the framework API and the fluent interface pops up a lot when working with the code base. It actually already has a fluent rule builder for some more complex rules, but more on that shortly.

Instead of concatenating all these rules with the pipe character, we can simply call method names that correspond with the rules and pass in the arguments.

```php
use TiMacDonald\Rule\Builder as Rule;

$rules = [
    'email' => Rule::required()
                   ->email()
                   ->get(),
    'password' => Rule::required()
                  ->min(6)
                  ->get(),
];
```

## Built in rule class

As I mentioned earlier Laravel actually comes with [some built in classes for building validation rules](https://laracasts.com/series/whats-new-in-laravel-5-3/episodes/18), however it only allows for the creation of the `dimensions`,  `exists`,  `in`,  `not_in` and `unique` rules (at the time of writing). I would hate to reinvent the wheel, so I incorporated them into this builder and calls to these rules are proxied internally to the built in validation rules on the fly.

One thing to keep in mind is that the built in rules have additional methods of their own for building up the validation logic, so you must call any methods on a built in rule directly after the initial call to it. As a example if we want to ensure that the users email is _unique_ in the database, but we want to ignore a specific users ID we could do something like this...

```php
use TiMacDonald\Rule\Builder as Rule;

$rules = [
    'email' => Rule::required()
                   ->unique($user->getTable())->ignore($user->id)
                   ->email()
                   ->get(),
    'password' => Rule::required()
                  ->min(6)
                  ->get(),
];
```

Here you can see the built in `unique` rule with a following `ignore` method call on it. This ignore method is proxied to the unique rule and so are any following method calls until another rule is encountered, in this case `email` is the next encountered rule. Nice, right?

If you wanna utilise the rule builder, you can [check it out on GitHub](https://github.com/timacdonald/rule-builder). If you are using the package, or have any feedback, please feel free to reach out to me about it.
