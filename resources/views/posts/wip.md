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
    title: "WIP",
    description: "I've found that introducing dedicated response objects that can handle multiple response formats is a really nice pattern to cleanup my controllers",
    date: new DateTimeImmutable('now', new DateTimeZone('Australia/Melbourne')),
    image: $url->asset('fallback.png'),
    hidden: true,
);

?>
---
extends: _layouts.post
section: content
title: WIP
date: 0
description: WIP
image: "images/posts/wip.png"
hidden: true
---

I want a testsuite structure to be:

- **Conventional**: It should fit in with Laravel conventions.
- **Obvious**: I shouldn't have to question where a test belongs.
- **Programmable**: With an established convention for how to structure a test directory, I should be able to write tooling around those conventions, for example, I should be able to easily jump from a feature test to the feature implementation within my editor.

There are 3 testsuites "levels" that I care about: unit, feature, and end-to-end.

## Unit Tests

Unit tests are very developer-y. They test specific parts of the system in _some_ form of isolation. 

The test will interact with your application in ways that a user will not, for example, you may write a test that calls methods directly on an Eloquent model and makes assertions against the returned value; a user of our application would never directly call methods against an Eloquent.

Unit tests live in the `tests/Unit` directory. This directory should contain tests that map 1:1 to implementation files. Not every implementation must have a test, but every test should have a matching implementation.

Here are some example mappings:

```
# Implementation files...

app/Http/Middleware/IncrementInteractions.php
app/Listeners/SendPodcastNotification.php
app/Models/Episode.php
app/Providers/AppServiceProvider.php
app/Rules/EpisodeDescription.php

# Test files...

tests/Unit/Http/Middleware/IncrementInteractions.php
tests/Unit/Listeners/SendPodcastNotificationTest.php
tests/Unit/Models/EpisodeTest.php
tests/Unit/Providers/AppServiceProvider.php
tests/Unit/Rules/EpisodeDescriptionTest.php
```

A filename convention emerges when focusing in on a single implementation / test file pair:

```
tests/Unit/Listeners/SendPodcastNotificationTest.php  <- test
app/Listeners/SendPodcastNotification.php             <- implementation
```

Using square brackets to mark the parts that change, we see everything outside the square brackets matches and helps establish a file naming convention that is obvious, conventional, and programmable.

```
[tests/Unit/]Listeners/SendPodcastNotification[Test].php
[app/]Listeners/SendPodcastNotification.php
```

### Enforcing Unit Test Structure

With an explicit naming convention we are able to write tooling around our testsuite.

Enforcing a convention alone only serves to keep things structured, which is nice but not super valuable. It is, however, step one to allowing us to create more valuable tooling later.

If you are using Pest you can place the following in the `tests/Pest.php` file. If you are using PHPUnit you may put this code in a custom "bootstrap script" at `tests/bootstrap.php`. You should search the PHPUnit docs for more info on how to configure a bootstrap script.

```php
use Illuminate\Support\Str;
use Symfony\Component\Finder\Finder;

/**
 * Verify unit test structure.
 *
 * @see https://tim.macdonald.au/wip
 */
collect(Finder::create()->files()->in(__DIR__.'/Unit')->name('*Test.php'))

    /*
     * Map into a [$testPath => $implementationPath] structure...
     */
    ->map(fn ($_, $test): string => Str::of($test)
        ->replaceFirst(__DIR__.'/Unit', dirname(__DIR__).'/app')
        ->replaceLast('Test.php', '.php'))

    /*
     * Reject any files that we find an implementation for...
     */
    ->reject(fn ($implementation) => file_exists($implementation))

    /*
     * Make the remaining paths relative to the project root for human consumption...
     */
    ->mapWithKeys(fn ($implementation, $test) => [
        Str::after($test, dirname(__DIR__).'/') => Str::after($implementation, dirname(__DIR__).'/')
    ])

    /*
     * Map into per-test violation messages...
     */
    ->map(fn ($implementation, $test) => 'ℹ️ Found test: ['.$test.']'.PHP_EOL.'❌ Expected to find implementation: ['.$implementation.']')

    /*
     * Bail if we find unconventionally named unit tests...
     */
    ->whenNotEmpty(function ($violations)  {
        echo ($violations->count() === 1
            ? "1 unit test does not have a matching implementation."
            : "{$violations->count()} unit tests do not have matching implementations.").PHP_EOL.PHP_EOL.$violations->implode(PHP_EOL.PHP_EOL);

        exit(1);
    });
```

Hopefully the comments help with what is happening in the script. If you do not understand it on first read: don't sweat. Once you see the output I think it will all click into place for you.

With the script in place, when we run the testsuite one of two things will happen:

1. If all our unit tests are in their conventional location you will not see any output and your testsuite will run normally.
2. If you have tests that are not in their conventional location you will see an error output similar to the following:

```
$ pest
  2 unit tests do not have matching implementations.

  ℹ️ Found test: [tests/Unit/UserTest.php]
  ❌ Expected to find implementation: [app/User.php]

  ℹ️ Found test: [tests/Unit/Listeners/SendShipmentNotificationsTest.php]
  ❌ Expected to find implementation: [app/Listeners/SendShipmentNotifications.php]
```

We can see in our example application that we have two tests in the wrong location. Our `User` model implementation is location in the `app/Models` directory for this example application. It needs to move the expected location, i.e., `tests/Unit/Models`, to fix the violation.

Once we do this we should only have on incorrectly placed test file.

```
$ mv tests/Unit/UserTest.php tests/Unit/Models/UserTest.php

$ pest
  1 unit test does not have a matching implementation.

  ℹ️ Found test: [tests/Unit/Listeners/SendShipmentNotificationsTest.php]
  ❌ Expected to find implementation: [app/Listeners/SendShipmentNotifications.php]
```

When all the violations have been address the test suite will run as expected.

### Navigating In Your Editor / IDE

I've mentioned that I want a conventional Laravel testsuite so I can program around the convention. We now have our conventional file naming for unit tests (not yet for feature tests; more on that shortly), the first tooling I want to build is editor navigation; not just for me, but for everyone.

I want us to collaborate and create navigation tooling for Sublime, PHPStorm, VSCode, etc. I'm going to create an initial VIM implementation here in the blog post, publish it on GitHub, and request that you help fill out the repository with implementations or instructions for other editors and IDEs or improve what already exists if it is outdated.

For VIM we are going to use the [projectionist.vim](https://github.com/tpope/vim-projectionist) plugin. The cool thing about projectionist is that its configuration is JSON, so it is editor agnostic meaning we could use the same configuration across all editors - assuming there is a plugin that supports it.


Here is the initial VIM configuration for Pest projects (we'll have PHPUnit configurations in the repository):

```vimscript
g:projectionist_heuristics = {
  "artisan&vendor/bin/pest": {
    "app*.php": {
      "type": "implementation",
      "alternate": "tests/Unit{}Test.php",
      "template": [
        "<?php $e('<?php'); ?>",
        "",
        "namespace App{dirname|backslash};",
        "",
        "class {basename}",
        "{",
        "    //",
        "}"
      ]
    },
    "tests/Unit*Test.php": {
      "type": "unit",
      "alternate": "app{}.php",
      "template": [
        "<?php $e('<?php'); ?>",
        "",
        "use App{backslash};",
        "",
        "it('', function () {",
        "    //",
        "});"
      ]
    },
  }
}
```

This configuration does a few things that I consider important:

#### Project Detection

The plugin will activate the file mappings only when it detects an `artisan` file in the project root.

#### Navigation

- Navigation from a unit test to the corresponding implementation can be achieved with a command / keyboard shortcut.
- Navigation from an implementation to the corresponding unit test can be achieved with a command / keyboard shortcut.
- The keyboard shortcuts may be configured.
- When navigating from unit test to implementation, if the implementation does not yet exist, which may be the case if you are doing Test Driven Development, it is created and populated with an empty class that has the correct namespace declaration and class name, e.g., if you are in the test file `tests/Unit/Models/PostTest.php` the following file would be created at `app/Models/Post.php`:

```php
use App\Listeners\UpdateStats;

it('', function () {,
    //,
});
```

#### Unit test to implementation navigation

1. It detects if the mappings are relevant for the current project by looking for an `artisan` file in the project root.
2. From any unit test file I can navigate to the corresponding implementation via a keyboard shortcut.
3. From any implementation file I can navigate to the corresponding unit test via a keyboard shortcut.
4. When navigating from a unit test to an implementation, if the implementation does not exist the file will be created containing a correctly namespaced empty class structure.
5. When navigating from an implementation to a unit test, if the unit test does not exist the file will be created containing an empty test and the implementation class will be already imported. Although a unit test will not _always_ reference the implementation directly, I think importing it is a good default.

For the tooling installation and configuration instructions for you editor / IDE, or for an updated version of the VIM configuration, please visit the [Laravel Conventional Testsuite Tooling](#) repository, as what follows could be outdated by now.
// TODO: video

## Feature Tests

Feature tests are less developer-y than unit tests and more focused on interactions your end users would have with your application. They test the _entry points_ of your application in isolation, for example, you might write a feature test that makes a `POST /users` request to your application and then makes some assertions against the response.

The interactions you make in feature tests are a simulated version of an end user's interaction, e.g., your feature test does not make an _actual_ HTTP call - it simulates a HTTP call. This takes the test further away from the code and closer to the end user.

I also mentioned that feature tests still test in some form of isolation. This is because end users do not have a single interaction with our application and then they are done. Users have rich interactions consisting of multiple requests throughout a single flow - maybe they visit the homepage, login, select the "create episode" button, fill out a form, hit submit, see a success page, etc. This is why feature tests are still isolated: they break these rich interactions down into isolated on-off interactions.

Features tests live in the `tests/Feature` directory. This directory should contain tests that map 1:1 to implementations - but the files we expect to find here are different. Because feature tests are focused on the entry points into your application, you should only find tests that map to your application entry points. They are: Channels, Commands, Requests, and Jobs.

Here is an example of some of the implementations and corresponding tests we might find in an application:

```
# Implementations...

/app/Channels/EpisodeReleased.php
/app/Console/Commands/SendUnpublishedEpisodeNotifications.php
/app/Http/Controllers/EpisodeController.php
/app/Jobs/ProcessEpisode.php

# Tests...

/tests/Channels/EpisodeReleased.php
/tests/Commands/SendUnpublishedEpisodeNotifications.php
/tests/Controllers/EpisodeController.php
/tests/Jobs/ProcessEpisode.php
```

Because we are testing the entry points of our application, there is a finite number of top level directories we should find in our feature tests. This may differs slightly if you introduce other entry points, but this is for a "bog standard" Laravel application. The top level directories should be limited to:

- `/tests/Feature/Channels`
- `/tests/Feature/Commands`
- `/tests/Feature/Controllers`
- `/tests/Feature/Jobs`

One might argue that jobs are not an entry point into your application. Argue away...and then come to peace with it.

** Mention here that there are only certain directories that should appear at the top level. This will match the unit test section layout.

# TODO

- Give a PHPUnit bootstrap example.
- Include script to test that files all end in `Test.php`
- make:test --unit
- move fixtures and other things out to a global fixtures directory or exclude from the Symfony finder
- Output to stderr?
- Should channels be present in the feature testing example? I think they should. Need to look at testing them.
- Maybe add something about Livewire components or something?
- Make it work for you. If you only have one channel, maybe you have `tests/Feature/Channels.php` as a single entry point. (do a test on testing channels in Laravel?)
- Using class based files with Pest feels weird. Don't care. Stop bikeshedding.
