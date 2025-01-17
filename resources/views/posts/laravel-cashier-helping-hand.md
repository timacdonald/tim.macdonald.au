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
    title: 'Laravel Cashier: a helping hand',
    description: "If you are creating a Laravel app with subscriptions via Stripe - Laravel Cashier might just be the helping hand you were looking for.",
    date: new DateTimeImmutable('@1525399200', new DateTimeZone('Australia/Melbourne')),
    image: $url->asset('laravel-cashier.png'),
);

?>

[Laravel Cashier](https://github.com/laravel/cashier) is an "expressive, fluent interface to Stripe's subscription billing services". I have really enjoyed using Cashier on my latest project and I thought a bit of guidance on what Cashier does and some clarification on the terminology used could help newcomers 🤞

Cashier itself is not a super complex codebase and is relatively small to consume and understand if you jump in and source dive. Although lightweight, it does bring a very nicely thought-out API for dealing with Stripe and customer subscriptions that I think you will enjoy using.

[The docs](https://laravel.com/docs/5.6/billing) will run you through the setup and use of Cashier in greater detail. This article is not designed to get you setup - it is here to help you decide if it might work for your project and to help you hit the ground running 🏃‍♀️

## Cashier: what it isn't

Cashier doesn't bring any extra functionality to [Stripe's billing services](https://stripe.com/au/billing) which already offer a rich feature set likely suitable for most subscription based services.

It is best to think of it as a syntactic sugar wrapper around [Stripe's PHP library](https://github.com/stripe/stripe-php) that makes it much simpler to work with and feels more at home in the Active Record world Laravel apps lives in.

This isn't to say that is *all* Cashier does. Laravel Cashier also has built in local database caching, including:

- **Subscriptions** so that you can easily check users subscription states (on trial, canceled, etc).
- **Payment methods** allowing you to show the user the last four digits of their credit card and brand (Visa, Mastercard etc).

The Stripe PHP library itself is fantastic as it is. I personally find it very easy to use directly - but at the end of the day I definitely prefer Cashier to hide the complexity and potential things I might have missed that Cashier has checks in place for. Not having to reinvent the wheel with the local database caching is an obvious winner as well.

If you were thinking Cashier might give you super powers without having to lift a finger - you might want to look at [Laravel Spark](https://spark.laravel.com).

## Life with Cashier

Imagine we live in a dystopian future where my articles are worth while reading AND people have to pay a subscription to read them (I know...terrifying right 😱). Let's take a quick look at how we might utilise Cashier to create this subscription in its simplest form:

```php
$user->newSubscription('articles', 'silver')->create($token);
```

With this one line of code we have successfully subscribed the user to my Articles via Stripe. This little snippet takes care of a lot of complexity for us under the hood.

## Life without Cashier

To get an idea of what it is like to not use Cashier, let's break the previous example down to see roughly how Cashier is taking the leg work out of using the underlying Stripe PHP library.

### Creating a Stripe\Customer

Any subscription created in Stripe needs to be related to a customer. This is not a customer in *your* database, but a customer in *Stripe's* backend. The following will show how we would create a customer, and also retrieve the customer once created:

```php
public function createStripeCustomer($user)
{
    $customer = \Stripe\Customer::create(['email' => $user->email]);

    $user->update(['stripe_customer_id' => $customer->id]);

    return $customer;
}

public function getStripeCustomer($user)
{
    return \Stripe\Customer::retrieve($user->stripe_customer_id);
}
```

Keep in mind that both of these methods will hit the Stripe API. Not great for response times!

### Create a Stripe\Card

As you would expect, we need to collect the customers credit card so we can charge them $$'s. If we don't charge them - we might not be able to afford wine! It is basically life or death at this point.

On our websites frontend we will want to setup [Stripe elements](https://stripe.com/docs/stripe-js/elements/quickstart) which will tokenize the credit card. Tokenizing a credit card is a strange phrase at first, but I think it is easier to understand if you know that a token *represents* a credit card. It is a reference to the card but does not contain any information about the card i.e. the number or expiry date. Read more about [Tokenization on Wikipedia](https://en.wikipedia.org/wiki/Tokenization_(data_security)).

Once we have the token we will submit *that* to our server and associate it with our Stripe customer with the following snippet:

```php
$customer->sources->create(['source' => $token);
```

We have a customer - and that customer now has a payment source (credit card) related to them. When we bill them for their subscription this card will be charged unless it is updated before the invoice is issued.

### Create a Stripe\Subscription

To sell a subscription through Stripe we will first need to create a product in the backend. Each product may have several different plans. For our example we are creating an "Articles" product that has 3 different plans: Gold, Silver, and Bronze. Each plan can cost a different monthly amount and give the user access to different features on the site. You will need to do this if you are using Cashier or the Stripe library directly.

> **Terminology**: Stripe *customers* have *subscriptions* to *product plans*.

Once we have our product and plans setup, we will want to make sure our system is creating our users subscriptions. Here is an example snippet of what we might end up with to make this happen:

```php
$subscription = $customer->subscriptions->create([
    'plan' => 'silver',
]);
```

We have successfully used the Stripe library to create a customer, associate a payment method with that customer, and subscribed them to our our Articles product on the Silver plan.

## Should I use it

Cashier adds a very simple, readable, and intuitive interface to Stripe’s PHP library that feels at home in a Laravel application. It is well worth a look.

As you can see there are only a few steps to get our subscription working with Stripe's PHP library directly with our simple example. Keep in mind however that we haven't even looked at modifying, canceling, resuming, checking for existing subscriptions, caching subscriptions locally in the database, and a number of other things you will often find yourself wanting to do with subscriptions.  What's more is we also still require the code to glue all this functionality together!

Cashier, on the other hand, has us up and running with one line:

```php
$user->newSubscription('articles', 'silver')->create($token);
```

Want to add a trial and use a coupon - no worries!

```php
$user->newSubscription('articles', 'silver')->trialDays(30)->withCoupon($coupon)->create($token);
```

I was able to build out the entire subscription functionality for a new app without once glancing at Stripe documentation - while using the Stripe PHP library directly I have found myself checking it now and then for parameter keys etc. This could be taken two ways:

1. That is awesome #productivity #gettinItDid
2. That is sad...their documentation design is beautiful 😍

It is also worth noting that this is a quick walk through and does not cover *all* the things that Cashier has done for us in the under the hood. As an example: if the token you submit is already set locally as the customers credit card, Cashier won't hit the Stripe API as the credit card is already setup: nice 👍

There are many little things like this that have been taken care of so we no longer need to worry about them. It is pretty great.

## Notable helpers

Some of these are covered in the docs, but here are some of the helpers I have personally found very handy / awesome / 🔥

### Subscription trials

```php
$user->newSubscription('articles', 'silver')->trialDays(30)->create($token);

$user->onTrial('articles'); // true
```

### Coupons

```php
$user->newSubscription('articles', 'silver')->withCoupon($coupon)->create($token);
```

### One off charges

```php
$user->charge(9900); // $99
```

### Refunding charges

```php
$charge = $user->charge(9900);

$user->refund($charge);
```

### Add charge to upcoming invoice

```php
$user->tab('Article PDF download', 100);
```

### Retrieve the upcoming invoice

```php
$user->upcomingInvoice();
```

### Cancel and resume subscriptions

```php
$subscription = $user->subscription('articles');

$subscription->cancel(); // sorry to see you go!

$subscription->resume(); // oh hey! you're back :)
```

### Download a PDF invoice

```php
$user->invoices->first()->download();
```

## Terminology

The only barrier I found to hitting the ground running with Laravel Cashier myself was some of the terminology utilised.  After using the package for a little while it does make sense...well, except for one thing!

### Subscription name vs plan

When creating a new subscription with Cashier you pass two arguments. The first is the subscription name. This is for local use only and does not reference anything on Stripe. In my previous examples I used `Articles`.

The plan however, should reference a product plan's ID. You may set this in the Stripe backend when you are creating the plan. My examples used `silver`.

I could very well achieve the same thing the subscription name `cheese` as long as I kept the plan the same. The following would work fine and I would not have to make any changes in Stripe's backend.

```php
$user->newSubscription('cheese', 'silver')->create($token);
```

### Active vs valid

This is the one that still has me stumped.

In my travels through Cashier I really struggled to pin down the differences between `active()` and `valid()` subscriptions. I thought creating a table would really help clear things up...and also pointed out to me that they are in fact **always** the same.

```
                | Trial | Trial + Canceled | Recurring | Recurring + Canceled | Canceled [ended] |
      onTrial() |  ✅   |         ✅        |    ❌    |           ❌          |        ❌         |
       active() |  ✅   |         ✅        |    ✅    |           ✅          |        ❌         |
        valid() |  ✅   |         ✅        |    ✅    |           ✅          |        ❌         |
    canceled()  |  ❌   |         ✅        |    ❌    |           ✅          |        ✅         |
onGracePeriod() |  ❌   |         ✅        |    ❌    |           ✅          |        ❌         |
```

My personal feelings are that `active` should represent a subscription that has not been canceled, i.e. one you are expecting to make money off it still.

`valid` on the other hand is any subscription that has not ended, i.e. any `active` subscription or any subscription that is in its grace period since being canceled.

I'm hoping to address this and make this clearer in the package and the documentation. I'll drop some updates in here once I get around to getting the PRs sorted.

**Update**: I've submitted a [Pull Request](https://github.com/laravel/cashier/pull/511) to hopefully start the process of clarifying the differences here (unless I've totally missed something obvious with this!)

**Update 2**: Looks like I got greedy and delved to deep into the mountain. Undocumented methods and not recommended for use looks like the consensus. Well..at least now I know 😅

### Grace period

If a subscription expires on the 30th of May, i.e. has been paid up until the 30th of May, but is canceled on the 15th of May, the duration between the 15th and the 30th is the *grace period*.

During a grace period a subscription may be resumed, however once the grace period expires (and the subscription has not been renewed) a new subscription must be created.

### Trial

If you offer a trial period, e.g. first 30 days are free, the subscription will automatically be on trial. After the 30 days the first invoice will be issued and the subscription is no longer on trial.

## Extra goodies

I found the addition of a few extra helpers methods would have really made my code more readable. I'm hoping to also PR these into the package.

```
            | Trial | Trial + Canceled  | Recurring | Recurring + Canceled  | Canceled [ended]  |
    ended() |  ❌   |         ❌        |    ❌    |           ❌          |        ✅         |
recurring() |  ❌   |         ❌        |    ✅    |           ❌          |        ❌         |
```

**Update**: I've submitted a [Pull Request](https://github.com/laravel/cashier/pull/513) to add these. I'm not 100% confident it will be accepted as I'm sure if there was a huge need for them they would have already been added. But we'll wait and see!

**Update 2**: 🎉 These methods have been added to Cashier. They will come in handy - at least for my project!

I also think it could be pretty handy to cache invoices locally, but I'm not sure if that is something enough people would need that it would make it into the package. Right now if you ask for the invoices it hits the API every time.
