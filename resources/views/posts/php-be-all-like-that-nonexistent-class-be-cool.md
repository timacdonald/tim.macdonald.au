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
    title: 'PHP be all like: that nonexistent class be cool',
    description: "There are certain scenarios where PHP does not check or error if a class you reference does not exist. These are their stories. *dun-dun*",
    date: new DateTimeImmutable('@1548234000', new DateTimeZone('Australia/Melbourne')),
    image: $url->asset('class-not-imported.png'),
);

?>

I was recently helping out on an open source issue where the developer was having trouble catching exceptions the library was throwing. Having a look at their code snippets I realised they had not imported the exception classes, but also that PHP was not checking that the exception classes they were attempting to catch *actually* existed.

I've noticed this kind of thing before in other scenarios, so I thought I would collate a list of them in hope that it could help others in the future. It really boils down to: if you don't check yourself, attempt to instantiate the class, or attempt to access something on the class (such as a static method), PHP may not tell you anything is wrong.

## References using the ::class constant

One of these cases is when using the special `::class` constant, PHP does not check that the class you are referencing actually exists. It is not until you manually check with `class_exists($class)`, or try to instantiate an instance of the class, that PHP will complain. This is probably the least troublesome of the lot, as if you try to work with the class you are going to know if something is wrong.

```php
// PHP does not complain about this
$class = NonExistentClass::class;

// will return false
class_exists($class);

// Fatal error: Uncaught Error: Class 'NonExistentClass' not found
new $class();
```

Check out a [demo on 3v4l](https://3v4l.org/Yob61).

## instanceof checks

This one could trip you up if you are not careful. When performing an `instanceof` check PHP does not ensure that the class you are checking against actually exists. Checks against nonexistent classes will just evaluate to false and continue on their merry way.

```php
class ClassThatExists
{
    //
}

$class = new ClassThatExists();

if ($class instanceof NonExistentClass) {
    // does *not* evaluate to true OR cause an error
}

if ($class instanceof ClassThatExists) {
    // *does* evaluate to true.
}
```

Check out a [demo on 3v4l](https://3v4l.org/rrQOB).

## try / catch blocks

Chances are this one is less of an issue as the exception will bubble up and hopefully you will be notified of the error if you have [error tracking](https://www.bugsnag.com/platforms/php/) setup.

```php
class ExceptionThatExists extends Exception
{
    //
}

try {
    throw new ExceptionThatExists();
} catch (NonExistentException $exception) {
    // does *not* execute this catch block or cause an error
} catch (ExceptionThatExists $exception) {
    // *does* execute this catch block.
}
```

Check out a [demo on 3v4l](https://3v4l.org/Aneo6).

As you can see it is possible to reference classes in PHP that do not exist. This can be a problem in different scenarios, such as if you think you are catching an exception but have not imported the class properly, or perhaps misspelt the class name. I guess this comes from the dynamic nature of PHP, and if PHP were to check that each class you reference existed at runtime, this would no doubt have a performance impact. There is plenty you can do to avoid this happening, but it is something you should keep in mind,  especially if you are newer to PHP.

## The remedy

1. Make sure you always [import the classes](https://secure.php.net/manual/en/language.namespaces.importing.php) you are working with. If you are using an editor / IDE where you can have PHP analysis available as you type (such as VIM or PHPStorm), it will yell at you about the nonexistent classes.
2. [Write tests](https://phpunit.de) that will help detect these issues before they become a problem. However, this issue can _also_ happen on your tests!
3. Use static analysis tools that will help catch these types of errors. Checkout [Psalm](https://getpsalm.org) and [PHPStan](https://github.com/phpstan/phpstan) to get started.
4. All of the above.

## Not always the case

There are times when PHP will check that the class exists without you attempting to interact with it. As an example, if you are declaring a class that extends another class. If you attempt to extend a class that does not exist PHP will yell at you.

```php
class BaseClass
{
    //
}

class ThisIsOkay extends BaseClass
{
    //
}

class PhpWillYellAtYou extends NonExistentClass
{
    //
}
```

Check out a [demo on 3v4l](https://3v4l.org/4LM5A).

If you know of any other scenarios where this can happen, I would love to hear about them. [Hit me up on Twitter]({{ $page->profiles['twitter'] }}) and I will update the post.
