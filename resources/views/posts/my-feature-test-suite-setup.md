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
    title: 'My feature test suite setup',
    description: "I do love a good feature test suite. I especially like them to be fast and, most importantly, trustworthy! As my approach has matured, and become stable, I thought I'd share how I go about setting things up.",
    date: new DateTimeImmutable('@1541898000', new DateTimeZone('Australia/Melbourne')),
    image: $url->asset('feature-tests.png'),
);

?>

In this post I am going to run through my setup, but by no means do I believe this is the best way of doing things. This is what I've landed on after some trial and error, but am always on the lookout for improvements. As a matter of fact if you have any improvements to this setup I would love to hear about them.

## 1. Database parity

When I was researching and learning about testing I always saw people were using SQLite for their tests, in-memory for extra points, but I never saw anyone ever saying they use it in production (even through it could probably handle more traffic than we'd like to think it can).

On the other hand, can you imagine the comments if you recommended on /r/php to use PHP 7 in production but run your tests with PHP 5. That would be crazy. So why do we see the database as any different, especially when:

1. Chances are you are running a database, such as MySQL, for you local data anyway.
2. Each database has certain quirks to them that can affect your code and tests.

The conversation around this is gonna go on a little longer than you are probably interested in...so you can just skip ahead if you are already on board with this idea! Otherwise - strap yourself in...

### But isn't it slower?

Maybe 🤷‍♂️  but is anyone really expecting a feature test suite to be super fast? Unit tests, sure, I want that nice and fast, but I know from the outset that my feature tests are gonna be slow. Plus you will need a pretty decent sized feature test before you are making a coffee during a test run.

I would rather know that my tests confirm my stuff will work in production than have them maybe confirm that it might work...but there could be database issues. Later on we will look at speeding things up, but not at the cost of confidence in our test suite!

### Foreign keys are off by default

Did you know that SQLite has [foreign key constraints off by default](https://sqlite.org/faq.html#q22) for backwards compatibility reasons? This means you need to remember to call `Schema::enableForeignKeyConstraints();` in a migration to make sure they are respected. Not a deal breaker - but you will wanna have that in place.

### Snozberries ≠ Snozberries

SQLite does not return values like MySQL does. If you specify that a column is an integer - MySQL will return an integer, however SQLite will return a string. Have a look at the following and notice that the returned age differs...

```php
Schema::create('examples', function ($table) {
    $table->integer('age');
});
DB::table('examples')->insert(['age' => 22]);

// SQLite...
DB::table('examples')->select('age')->first();

=> {
     +"age": "22",
   }

// MySQL...
DB::table('examples')->select('age')->first();

=> {
     +"age": 22,
   }
```

This means you will need to add it to the models `$casts` property...

```php
Sandwich extends Eloquent
{
    protected $casts = [
        'stock_count' => 'integer',
    ];
}
```

This isn't a big deal, but it is extra code you have to write specifically so you can test in SQLite. Then consider foreign keys: in my models I often do comparisons like this so that I don't need to load the relationship just to check if they are the same.

```php
Sandwich extends Eloquent
{
    public function usesPackaging($packaging)
    {
        // this
          return $this->packaging_id === $packaging->id;

        // instead of
        return $this->packaging->is($packaging);
    }
}
```

Eloquent automatically casts our `id` attribute to an integer, so I would now have to add `packaging_id` to the `$casts` array as well. However MySQL will return foreign keys as integers.

None of this is bad, and you might think it is crazy to not do all this stuff to the `$casts` array by default - but what I don't like is that I've got to do all this just to use SQLite in my tests.

The next thing I'm sure you are thinking is *"what if you need to change database"*. Well, I'll deal with that when it happens, but I cannot imagine any of the projects will ever need to change database.

And I'm not saying you'll never need to cast any attributes if you use MySQL over SQLite. MySQL returns, for example, `boolean` column fields as integers - so you'll wanna cast those to get proper `true` / `false` values.

### Feature support

SQLite and MySQL, or any other database for that matter, do not have complete feature parity at all times. JSON column support is a good example. I think that has rolled out to most databases now. I'm not 100% sure if SQLite has json column types. Do you? [I found a bunch of stuff about json functions](https://www.sqlite.org/json1.html) but I have no idea if that means they support it or what.

Either way this is something you will need to keep in mind when working with a different database for testing and production: Does it support the same features?

### SQLite is awesome!

Yes, yes it is. I am definitely not arguing that point. I have implemented it in Android and iOS apps and it works great. If my Laravel app were using it in production I would also use it in my tests without hesitation.

If you are building a package that is going to be used in different environments outside of your control, sure you wanna make sure it works everywhere, but if your app is on MySQL - make sure it works on MySQL.

Okay, enough about that, lets move on to how I setup the database.

## 2. Prefixing the test table

Using an in-memory SQLite database, or file based, is great because you don't have to do any cleanup. Its sole purpose is to be the testing database. The last thing we want is for any seeded data you use to view you app locally to interfere with your test suite, and vice versa.

When my test suite runs I want a clean database ready to roll. I achieve this by making the `prefix` an environment variable:

```php
// file: /config/database.php

'connections' => [

    'mysql' => [

        // 'prefix' => '',
        'prefix' => env('DB_PREFIX', ''),
    ],
],
```

Now in my testing environment I can set `DB_PREFIX=testing_` so that my local data and test data do not populate the same database tables. This also keeps the number of databases consistent with your apps, rather than having to create a new database for local data and an extra database for testing. These can both now share the same database and credentials.

## 3. Testing environment file

Laravel ships with testing environment variables in the `phpunit.xml` configuration file. I prefer to remove all of these and put everything into a dedicated `.testing.env` file. This keeps all your environment configuration consistent. You end with these 3 environment files:

```
.env
.env.example
.env.testing
```

Laravel does everything else for us, we do not need to tell it that we are moving the configuration from the `phpunit.xml` to a dedicated file - sweeeeet 👍

## 4. Base test class

There are certain things I want available, and to happen, in my features tests that I don't necessarily want in the unit test. To achieve this I always create the following class in the  `tests/Feature` folder.

```php
namespace Tests\Feature;

use Tests\TestCase as BaseTestCase;
use Illuminate\Foundation\Testing\DatabaseTransactions;

abstract class TestCase extends BaseTestCase
{
    use DatabaseTransactions;
}
```

All my feature tests will extend this base class. It adds the database trait to help keep the database fresh for my tests and it also gives me a place to add any additional assertions I might want that relate specifically to testing responses.

Right now I can tell what you are probably thinking...

*Aren't we all using `RefreshDatabase` now days? And won't I have to remember to migrate the database before each test run.*

Do not fret! I have a good reason which is...

## 5. Run your tests in parallel

This is the real kicker! There is a [fantastic package called Paratest](https://github.com/paratestphp/paratest) that will run your tests in parallel, meaning that instead of running each test one after the other, it will actually run several tests at the same time! Here is the TL;DR;

> The objective of ParaTest is to support parallel testing in PHPUnit. Provided you have well-written PHPUnit tests, you can drop paratest in your project and start using it with no additional bootstrap or configurations!

And I gotta tell ya, this was my exact experience...not that I have well written tests 😅 I would never attest to that (see what I did there 🤓)  but I could drop it in and everything worked as expected.  You just add it to your dev dependencies, like you do with phpunit, and you are off 🚀

Running my current feature test suite with Paratest is giving a speed increase of ~60%. That is a **huge** increase! If your tests run for 20 seconds it could potentially bring that down to 8 seconds!

I played with a few configuration options to try and get the most out of the package and here is what I landed on.

```bash
$ paratest --processes 4 --testsuite Feature --runner WrapperRunner
```

My machine seemed to get the most performance when working with 4 processes. You will wanna tweak this for your machine - but don't be greedy - just bumping this number up to 100 is not going to make your tests run faster. Read the docs, check your machine’s specs, and pick a suitable number.

The `testsuite` argument asks paraunit to only run the tests in the `tests/Feature` folder. Laravel has this already setup out of the box for you, so you can just throw that in the command and everything works as expected.

I found the `WrapperRunner` also made things a bit faster. It is reducing some of the cost of bootstrapping the tests. Jump in and have a read over the readme - it really is an awesome package and I highly recommend dropping it in to your workflow.

It is also worth noting that paratest is the reason I don't use the `RefreshDatabase` trait. If I was to use that trait after each test it would drop the database. This is an issue because now several tests are running at the same time so you might be trying to drop the database as another is trying to write to it!

`DatabaseTransactions` on the other hand never drops the database. It just makes sure that no data is ever actually written to the database, as everything is rolled back. So now our only pain point it remembering to migrate the database - which is the perfect intro into...

## 6. Composer scripts

This is the final key to my setup which is how I actually run my tests. Composer allows us to add our own custom scripts. This is nice because you already have your composer file in version control, and the scripts are so short they don't really need their own dedicated file, but you could always do that it you wanted.

Dig into you `composer.json` file and you will see Laravel already comes with a few scripts, including `post-root-package-install`, `post-create-project-cmd`, and `post-autoload-dump`. I add a few additional scripts in here to make testing a breeze.

We are going to add an `ftest` script that will make sure our test database (the prefixed tables we configured earlier) has been migrated with the latest migrations, after it has run any migrations it runs our feature test suite in parallel with paraunit.

```json5
{
    "scripts": {
        "ftest": [
            "php artisan migrate --env=testing",
            "./vendor/bin/paratest --processes 4 --runner WrapperRunner --testsuite Feature"
        ]
    }
}
```

Now when I want to run my feature tests I can switch over to the terminal and run `composer ftest`. You might consider setting up some more helper scripts to run you unit tests, run both unit and feature tests together, produce coverage reports, run CS-Fixer etc. They are pretty handy.

## Wrap up

Use the same database you use in production, or at least start there and only move to something else when there is a compelling reason to. If speed is the problem, check out Paratest. It is a great package and making my tests run 60% faster is pretty darn wicked. The composer scripts means you never need to remember to run your migrations before meaning that you don't lose any of the benefit of the `RefreshDatabase` trait.

If you can think of anything I've missed or could improve on for my feature test setup I would love to hear it!
