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
    title: 'Giving collections a voice',
    description: "Laravel collections have become an essential part of my codebases and I couldn't imagine working without them. I have found giving collections the voice of the problem domain makes for a much nicer API when compared to the generic collection methods.",
    date: new DateTimeImmutable('@1543205093', new DateTimeZone('Australia/Melbourne')),
    image: $url->asset('collection-voices.png'),
    formats: [Format::Article, Format::Video],
);

?>

<?php $template('update', ['content' => $capture(static function () use ($template) { ?>
    I gave a talk on this topic at LaraconAU, 2019. It covers all the ways custom collections can improve your systems design and contains some more guidance on when you would reach for this pattern.

    <div class="mt-6">
        <?php $template('youtube', [
            'id' => '06--kezKc0Q',
        ]) ?>
    </div>
<?php })[0]]); ?>

When working with eloquent models I am always adding methods to make the code speak the _domain language_. If an invoice is able to be paid I would generally have an `→isPaid()` method and an inverse `→isNotPaid()` method, and I would usually have corresponding query scopes as well.

```php
// instead of...

if ($invoice->payment !== null) {
    // invoice is paid
}

// we can do...

if ($invoice->isPaid()) {
    // invoice is paid
}
```

No one that issues invoices is asking if the payment is not equal to null, they are asking if the invoice is paid. These methods make our models speak the language of the domain.

During a recent codebase refactor I got to thinking about how I utilise this domain language with collections and the answer was....I don't. All my collections are vanilla. All of them. They don't speak the same language as my eloquent models.

If I want to check if a collection of invoices have been paid I might end up with something like this.

```php
$containsUnpaid = $invoices->contains(function ($invoice) {
    return $invoice->isNotPaid();
});

if (! $containsUnpaid) {
    // all invoice are paid
}
```

Or if you embrace the higher order collection proxies it can shortened to this.

```php
if (! $invoices->contains->isNotPaid()) {
    // all invoice are paid
}
```

But I got to thinking: if I'm so pedantic about having these domain specific methods on my models, why am I stopping there? Why can't my collections speak the same language, why do they only speak the language associated with an array of generic items? 🤔

## Extending the collection

I have been extending eloquent collections on my current project for each model to give them domain specific methods and I am finding it makes for some much clearer code. The previous example ends up looking like this.

```php
if ($invoices->areAllPaid()) {
    // 🎉
}
```

This new `->areAllPaid()` method wraps up the previous code into an API that speaks the same language as our models. Let's take a look at how we achieve this.

First we want to extend the base eloquent collection class and add the new method.

```php
use Illuminate\Database\Eloquent\Collection;

class InvoiceCollection extends Collection
{
    public function areAllPaid()
    {
        return ! $this->contains->isNotPaid();
    }
}
```

Now we need to tell our `Invoice` class to utilise the `InvoiceCollection` instead of the base eloquent collection. We can achieve this by overriding the model's `newCollection()` method.

```php
class Invoice extends Eloquent
{
    public function newCollection(array $models = [])
    {
        return new InvoiceCollection($models);
    }

    // ...
}
```

Now when we retrieve invoice models from the database, we will be able to use these domain methods instead of the generic collection methods.

```php
$invoices = Invoice::latest()->take(10)->get();

if ($invoices->areAllPaid()) {
    //
}

// instead of...

if (! $invoices->contains->isNotPaid()) {
    //
}

```

At first I was hesitant to introduce extended collections for all my models, but the more I did it, the more I felt my code was easier to read. Eloquent collections are inherently tied to eloquent models, so they should share a similar vocabulary.

I am really digging these extended collections and I really recommend giving this a go if you aren't already. It is a small thing, but I feel in the end it makes a big difference.

