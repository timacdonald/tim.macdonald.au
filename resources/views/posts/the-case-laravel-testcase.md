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
    title: "The case of the Laravel TestCase",
    description: "Deep diving the Laravel TestCase and pushing it to the limit, and yea...that's the title I went with 🕵️‍♂️",
    date: new DateTimeImmutable('@1561694400', new DateTimeZone('Australia/Melbourne')),
    image: $url->asset('laravel-testcase.png'),
);

?>
I saw a conversation on Twitter the other day discussing how Laravel was slowing down a test suite. I decided I wanted to dig into this and see if there was anything to it.

Just an early disclaimer that this post has nothing to do with your applications performance in production and is only reflective of times seen during a test suite run.

Before we jump into the benchmarking and potential performance improvements, here is some info that might be handy if you don't know about the Laravel `TestCase` and the container's boot process.

## The Laravel TestCase

Whenever you create a test it needs to be a child class of PHPUnit's `TestCase` (I am assuming your are using PHPUnit throughout this post). Out of the box Laravel comes with its own `TestCase` that you can extend in your test files. The Laravel `TestCase` gives you access to a "booted" container within your tests, the same kind of container your application relies on when it is serving web requests.

There is no requirement to extend Laravel's `TestCase` but I think out of habit or requirement (due to the type of tests you are writing) a lot of developers default to extending Laravel's instead of PHPUnit's.

Note: This is based on anecdotal evidence only - maybe this isn't true 🤷‍♂️

## What is booting?

You can think of "booting" as the process of "getting things ready". It does some setup of the world in which your application runs.

A lot of things you may take for granted with Laravel are made possible because the container is booted whenever you interact with an artisan command, handle a web request, or run your test suite.

You don't initiate the booting process, it is all handled for you.

## Features to boot 🥾

When the container boots, it does a few things under the hood, such as:

- Loading environment variables
- Loading the configuration
- Register facades
- Register and boot the service providers
- Setup error handling
- Register Artisan commands (only when booting in the CLI)

If you interact with anything related to these items in your tests, you are going to want to extend Laravel's `TestCase`.

## Benchmarking

I wanna start out by saying: I have no idea what I'm doing when it comes to benchmarking. This is probably a terrible benchmark. Sorry.

We are also not comparing apples with apples. We are essentially comparing apples _with nothing_. I feel there is value in knowing there is a time impact, but not sure how much futher you can read into these numbers. If nothing else, the benchmarking will be useful simply as a means to make / check performance improvements.

To do the benchmarking I [created a repo](https://github.com/timacdonald/laravel-container-speed-test) that is a fresh Laravel setup with no customisations. I then created 2 test suites named "Container" and "WithoutContainer". The only difference between these two suites is that that the Container suite extends the Laravel `TestCase` and WithoutContainer extends PHPUnit's `TestCase`.

The build script generated 100 test classes, and each of those classes contains 15 tests. This results in 1,500 tests per suite. Each of those tests just does a `$this->assertTrue(true);`. You can take a quick peak at the test classes if you like:

- [Container test class](https://github.com/timacdonald/laravel-container-speed-test/blob/dd25509a720c813adae71c7f8be8d66090841c5e/tests/Container/Test0Test.php)
- [WithoutContainer test class](https://github.com/timacdonald/laravel-container-speed-test/blob/dd25509a720c813adae71c7f8be8d66090841c5e/tests/WithoutContainer/Test0Test.php)

## The results

⚠️  Your mileage may vary. Running 1,500 tests resulted in the following:

- WithoutContainer: 0.289 seconds on average
- Container: 9.15 seconds on average

This was on PHP 7.2.19 without Xdebug on the following machine.

![Macbook Pro Retina 2012. Operating system: macOS 10.14.5. Processor: 2.3 GHz Intel Core i7. Memory: 8 GB 1600 MHz DDR3.](<?php $e($url->asset('system-report.png')); ?>)

I was worried my machine would have a big impact on these results, so I [asked on Twitter](https://x.com/timacdonald87/status/1144067916146462720) and in the PHP Australia slack channel if others would be kind enough run the suite as well. [Jordan suggested](https://x.com/jordanpittman/status/1144070900947607557) it might also be good to repeat the suite a few times to account for PHPUnit's startup time.

Here are some of the results (some of which you can also see in the Twitter thread) from community members who were awesome enough to run the suite for me to gather some additional data points. These times are reflective of one loop through the test suite, i.e. 1,500 tests.

<div markdown="block" class="table-wrapper">
WithoutContainer | Container | PHP version | Xdebug | OS
 --- | --- | --- | ---
0.177s | 7.9s | 7.1.30 | Disabled | macOS 10.14.1
0.527s | 26s | 7.3.6 | Disabled | macOS 10.14.5
0.075s | 4.11s | 7.3 | Disabled | Ubuntu 18.04
0.134s | 5.73s | 7.2.19 | Disabled | macOS 10.14.5
0.213s | 17.09s | 7.2.19 | Disabled | Ubuntu 14.04
0.236s | 16.44s | 7.2.10 | Disabled | Win7 x64
0.142s | 11.4s | 7.3.6 | Disabled | macOS 10.14.5
0.41s | 10.16s | 7.3.6 | Disabled | macOS
0.58s | 26s | 7.3.2 | Disabled | Alphine Linux 3.6
0.257s | 6.47s | 7.2.13 | Disabled | Kali Linux 2018.4
0.137s | 5.49s | 7.3.6 | Disabled | Alphine Linux 3.6
0.261s | 12.347s | 7.3.6 | Disabled | Alphine Linux 3.6
0.220s | 12.74s | ? | Disabled | ?
0.64s | 36s | ? | ? | ?
0.457s | 17.11s | 7.1.23 | ? | macOS 10.14.5
0.101s | 4.85s | 7.3.6 | Enabled | macOS 10.14.5
0.697s | 40s | 7.2.13 | Enabled | Kali Linux 2018.4

</div>

There are of course a lot more variables that need to be taken into account, such as:

- Are the tests run locally / docker / etc
- Machine spec, such as RAM
- ...and probably a whole bunch more

but I think these numbers give a good overview without getting forensic about it.

## Release comparison

I thought it might also be interesting to run the suite against some previous versions of Laravel to see how the numbers have been changing over time. Here are the averages I've been seeing on my local machine. I'm only including results for the Container suite. These are from a single loop through the suite:

- [5.8](https://github.com/timacdonald/laravel-container-speed-test/tree/dd25509a720c813adae71c7f8be8d66090841c5e): 9.15s
- [5.7](https://github.com/timacdonald/laravel-container-speed-test/tree/5.7): 8.25s
- [5.6](https://github.com/timacdonald/laravel-container-speed-test/tree/5.6): 7.29s
- [5.5](https://github.com/timacdonald/laravel-container-speed-test/tree/5.5): 7.37s

Before we lose our minds about the fact that these numbers have been increasing in recent version, lets keep in mind that these are running the **entire** test suite - all 1,500 tests. If we boil this down to actual boot time **per test**:

- 5.8: 0.0061s
- 5.7: 0.0055s
- 5.6: 0.0049s
- 5.5: 0.0049s

## Making it faster

I've been digging through the bootstrapping process and I can't imagine there is anything in there you could "turn off" without causing yourself a headache in your test suite. The only optimisation I could suggest is to remove any aliases in the `config/app.php` file (which you've got full control of) if you aren't using them in your app. It trimmed off ~1 second for me running the benchmark.

## Must go faster!

`</jurassic-park-reference>`

But wait...there's more!

So I had all but shared this post on Twitter without providing any sigificant performance suggestions, which is fine - but then I remembered something [Jess Archer](https://x.com/jessarchercodes) and I had chatted about previously: caching the config, routes, etc before a test run. The thinking at the time was that it brought the test run more inline with production - which is always a good thing. Turns out that isn't the end of the story though.

As mentioned previously, everytime the kernel boots it loads the config. If you have a large test suite that is a whole heap of file I/O that is happening at the beginning of each test. So the big question is: what kind of impact would caching the config before the test run have? And the answer: it turns out...a decent one.

On my local machine I'm seeing a speed improvement of **~50%**. Considering I wasn't really starting the adventure out to make any sizable improvements...I'd say that is a pretty decent win. Unfortunately it isn't just "do this one simple trick". I believe there are a couple of things that need to be addressed before we can see this handled for us in core.


### The issues

To cache your testing environment config, you can currently run the following command.

```
php artisan config:cache --env=testing
```

However, running this command from the terminal doesn't take your `phpunit.xml` environment variables into account. You need to create a `.env.testing` file and put your variables in there instead. This is fine with me, as that is how I usually do things in a new project anyway, but I'm not sure how common that is amoung other projects / teams. I think an ideal solution would need to cache the environment variables in the projects `phpunit.xml`.

It also doesn't write to a separate config cache. This means that after you run the command your application will always load the testing environment variables, even when in the browser - when you would usually expect the local environment variables to be loaded. It turns out there is already an existing environment variable you can set to manipulate the config cache path named `APP_CONFIG_CACHE` but in order to automate the process I'm not sure this will be as handy as I'd hoped.

And lastly: Running the cache and clear cache command is a manual process. Wrapping the commands up in a [composer script](https://getcomposer.org/doc/articles/scripts.md) would definitely be possible.

```json5
{
    "scripts": {
        "test": [
            "php artisan config:cache --env=testing",
            "./vendor/bin/phpunit",
            "php artisan config:clear --env=testing"
        ]
    }
}
```

But often you need more control over what tests are being run. Again, I think the ideal solution should handle all of this for you out of the box.

### A potential solution

I'm going to propose that we introduce a process to allow this all to be handled by the framework. I've put together a couple of PRs to introduce some new functionality. If it isn't a suitable solution to be included in the framework, I'll hopefully be able to create a package to handle it. The functionality includes:

**Config cache files per environment**: As mentioned previously, when you call `php artisan config:cache --env=testing` it writes to the same file as when you call `php artisan config:cache`. This PR will use the value passed to `--env` to set the cache file, i.e. with `--env=testing` will store the config cache in `bootstrap/cache/config.testing.php` and without it, the config will be stored in `bootstrap/cache/config.php` as it currently does.

Having separate files for cached config will mean that your testing configuration will not leak into your local configuration, e.g. when you are in the browser.

**Introduce a PHPUnit listener to run the config caching**: This will addreses the other 2 issues with the current setup. Because it is a PHPUnit listener, the variables set in the `phpunit.xml` file will already be included in the cached config without any extra work being required by the framework. It also means that at the start of every test run the configuration will be cached automatically for you. At this point the process becomes seamless and hidden to the developer.

**Bonus points**: it is 100% opt-in. If you don't want any of this automatic caching, you can just not include the listener in your `phpunit.xml` configuration file.

### The improvements

With the introduction of these changes on my local machine I'm seeing a speed improvement of ~50% 🚀

- Before: 9.15s
- After: 4.2s

Gotta say - I'm pretty dang happy with that!

It is important to keep in mind that this won’t translate to a huge payoff in your test suite unless it is of substantial size, and that as soon as you start "doing stuff" in your tests, this improvement might not really be all that noticable.

### Where to from here

I'll keep this post updated - and probably [yell about it a bit on Twitter](https://x.com/timacdonald87) as well, so if you have any interest in keeping up with the status of the PRs (or if they aren't suitable, the package) check back soon.

**Update 1**: Just submitted the Pull Requests. Would love any feedback you have on the implementation! [Check them out](https://github.com/laravel/framework/pull/28998).

**Update 2**: Turns out the `APP_CONFIG_CACHE` is actually a workable approach! I've just closed the previous PR and [opened a much more concise PR](https://github.com/laravel/laravel/pull/5050).

**Update 3**: Some of you may have already picked up on this, but the listener is not really the ideal solution to this problem. Putting this in a PHPUnit bootstrap file is a much better approach. I've updated the latest PR to implement a bootstrap file instead of the listener.

## Final thoughts

Any optimisation to the booting process or the test suite is only going to have a big impact on a large test suite. I think this is just something to keep in mind as you build out your test suite. If you are doing unit tests that don't hit the DB and don't touch the framework, you can go ahead and extend the PHPUnit `TestCase` if you want. As soon as you need the framework, switch it out for Laravel's `TestCase`.

I do think it could be kinda interesting (and I've started hacking on it a little bit) to have an Artisan command or even a [Shift](https://laravelshift.com) that loops over each test file and:

1. Ensures that the tests pass.
2. Makes the file extend the PHPUnit `TestCase`
3. Re-run the test. If it passes again, leave it, otherwise revert it.

I'm not about to rush and change all my tests over to use the PHPUnit `TestCase` but I might start extending it if I'm writing some tests that don't require the framework on my larger projects. I write a heap of feature tests, at which point optimising the framework is the last thing that is gonna have an impact on my test suite speed.

I also think the config caching could just be the start. As I mentioned I write a lot of feature tests, so I think there is also possibly potential improvements by caching the routes and views, and also the new events caching. These would also need to be done per environment. If the config caching gets accepted I'll run some benchmarks on those as well and see what kinds of improvements we can squeeze out of them!

This was a fun experiement to dive into. I'm not sure how "real world" any of these improvements are, and it is important to keep in mind that benchmark improvements are not reflective of the payoffs you might see in a real test suite. But fun to tinker with and explore.

## Related articles

I've written a few other articles on making your test suite faster. If you enjoyed this you might also like to check these out:

- [Tips to Speed up Your PHPUnit Tests](/tips-speed-up-your-phpunit-tests/)
- [My feature test suite setup](/my-feature-test-suite-setup/)

