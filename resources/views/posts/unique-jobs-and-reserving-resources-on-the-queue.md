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
    title: "Unique jobs and reserving resources on the queue",
    description: "This post covers some interesting features of Laravel's queueing system. Forcing unique jobs and reserving resources across different job types.",
    date: new DateTimeImmutable('@1668983929', new DateTimeZone('Australia/Melbourne')),
    image: $url->asset('dogs-in-a-queue.png'),
);

?>

When focusing in on some specific functionality of an application, such as notifications or queuing, I often head over to the docs and catch up on the current state of the art for different Laravel components.

Recently I have been hacking on some refactorings and improvements to a project's queued jobs, which is all powered by Laravel's Wicked Good Queuing System™. So as I do, I went over and read up on the current feature set for queues. I had seen the [`ShouldBeUnique`](https://laravel.com/docs/queues#unique-jobs) contract and the [`WithoutOverlapping`](https://laravel.com/docs/queues#preventing-job-overlaps) middleware before, but not yet had a chance to implement and internalise their functionality.

I had thought, on first read, that I would want to use one or the other, that is `ShouldBeUnique` or `WithoutOverlapping`. To my surprise, these two features work really well together. They simplify handling duplicate requests and ensure that resources stay in a good state across conflicting background jobs.

## Duplicate requests

If you have built web based forms and had enough people using those forms, you have likely run into the problem of duplicate form submissions. This problem, in my personal experience, feels especially true for <abbr title="Asynchronous JavaScript and XML">AJAX</abbr> powered form submissions, but can impact traditional forms as well. The user of the form, through some kind of dark twisted magic, is able to trigger multiple requests even though you cannot seem to replicate that behaviour, no matter how hard you try.

A fix I often see is to disable the button in the <abbr title="User interface">UI</abbr> once it has been activated, preventing the user from triggering requests back-to-back.

The problem appears to be fixed, but:

1. We are now disabling UI elements, which I know my <abbr title="User experience">UX</abbr> friends are not happy with me about.
2. We now have a dependency on JavaScript for our application to function _correctly_. This might not be a big issue if you are deep in a JavaScript powered front-end, because that is already the case (for better or worse).
3. By-passing the UI and firing off requests manually could still be a way around our "solution".

The root problem still exists and you may want to address it properly if a malicious actor can leverage it to put your system into a bad state.

Additionally, if you build out an <abbr title="Application Programming Interface">API</abbr> where your consumers are in control of how requests are made, you can't introduce this "fix".

The API based solution I usually see for duplicate requests is to make consumers provide a <abbr title="Number used once">nonce</abbr> with each request. If the API receives a request with a nonce that has already been seen, the request is considered a duplicate and is discarded.

Although on the surface this appears to fix the problem, there is nothing stopping an API consumer from firing off duplicate requests, and just auto-generating a nonce because "the docs said to add one".

In the following example of a potential consumer, I could mash the submit button and it would generate a new nonce for each submission.

```html
<script>
const generateNonce = () => { /* ... */ }

const submit = (e) => {
    e.preventDefault()

    form.submitWithNonce(generateNonce())
}
</script>

<form onsubmit="submit" ...>
    <!-- ... -->
    <button>Submit</button>
</form>
```

Thus, in both the first party UI and API consumer scenarios, shifting the responsibility to the client is, in my opinion, a flawed approach for dealing with duplicate requests.

_^ I'm not saying these solutions aren't ever valid for other problems. A nonce, for example, can serve other purposes for an API._

## Atomic locks

The solution I like to reach for when handling duplicate requests is [atomic locks](https://laravel.com/docs/cache#atomic-locks). This removes the responsibility from the client and allows our application to internally ensure that duplicate requests are handled gracefully.

Imagine a user is attempting to delete a resource and the deletion process is expensive. Perhaps you need to connect to different providers to de-register the resource / delete files, etc. A controller might look something like the following:

```php
namespace App\Http\Controllers;

use App\Jobs\DestroyPodcast;
use App\Models\Podcast;
use Illuminate\Support\Facades\Response;

class PodcastController
{
    /* ... */

    public function destroy(Podcast $podcast)
    {
        DestroyPodcast::dispatch($podcast->markAsDeleting());

        return Response::noContent();
    }
}
```

If we invoke this controller method multiple times, we are going to flood the queue with duplicate jobs that are all trying to perform the same work, which can result in application errors and queue backlogs. To remedy this, we may wrap the job in an atomic lock:

```php
namespace App\Http\Controllers;

use App\Jobs\DestroyPodcast;
use App\Models\Podcast;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Response;

class PodcastController
{
    /* ... */

    public function destroy(Podcast $podcast)
    {
        $podcast->markAsDeleting();

        if (Cache::lock("podcast:{$podcast->id}:destroy")->get()) {
            DestroyPodcast::dispatch($podcast);
        }

        return Response::noContent();
    }
}
```

This controller may now be invoked many times, and only a single job will be dispatched, that is, until the lock is released. To take care of releasing the lock, we manually release it when the job has processed or failed.

```php
namespace App\Jobs;

use App\Models\Podcast;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Throwable;

class DestroyPodcast implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public Podcast $podcast)
    {
        //
    }

    public function handle()
    {
        /* ... */

        Cache::lock("podcast:{$this->podcast->id}:destroy")->forceRelease();
    }

    public function failed(Throwable $e)
    {
        /* ... */

        Cache::lock("podcast:{$this->podcast->id}:destroy")->forceRelease();
    }
}
```

This approach is okay, however we have to do a lot of manual work. This is where Laravel has improved things for us with the `ShouldBeUnique` contract. When we implement the contract on the job, the need to manually lock / unlock disappears. The resulting refactor is much more declarative, where we tell the computer _what_ to do, not _how_ to do it - which I'm all about 💞

```php
namespace App\Http\Controllers;

use App\Jobs\DestroyPodcast;
use App\Models\Podcast;
use Illuminate\Support\Facades\Response;

class PodcastController
{
    /* ... */

    public function destroy(Podcast $podcast)
    {
        DestroyPodcast::dispatch($podcast->markAsDeleting());

        return Response::noContent();
    }
}
```

In our job we will implement the `ShouldBeUnique` contract and return an identifier via the `uniqueId()` method:

```php
namespace App\Jobs;

use App\Models\Podcast;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Throwable;

class DestroyPodcast implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public Podcast $podcast)
    {
        //
    }

    public function handle()
    {
        /* ... */
    }

    public function failed(Throwable $e)
    {
        /* ... */
    }

    public function uniqueId()
    {
        return $this->podcast->id;
    }
}
```

Laravel will now ensure that only a single instance of `DestroyPodcast` can exist on the queue for any given podcast. That is, the uniqueness is scoped to the podcast record plus the job class. Now, if a duplicate job is dispatched, like we are doing in the controller, it will be discarded. Just like in our initial manual version, when the job is either successful or fails, the lock will be cleaned up for us.

Under the hood, Laravel uses the cache lock `laravel_unique_job:App\Jobs\DestroyPodcast95`, where `95` is the id of the podcast, which we returned via the `uniqueId()` method.

So we can utilise `ShouldBeUnique` to ensure that only one instance of `DestroyPodcast` is queued at any one time for a specific podcast. That might feel like we have solved the problem. But there is something else lingering here that we also want to address.

## Different operations on the same resource

What happens when the system gets two requests, at the same time, for conflicting operations? What if updating a podcast is also expensive and the following occurs:

```
> "PATCH /podcasts/95" request received.
> Processing UPDATE job starts...
> "DELETE /podcasts/95" request received.
> Processing DESTROY job starts...
> Processing DESTROY job ends.
> Processing UPDATE job ends.
```

As you can see, the update job ends after we have deleted the podcast. What if the update job needs to perform actions on the podcast while it is processing? In this particular scenario, it isn't a big deal for the user. Their podcast was deleted after all, however the developers may be facing some false-positive errors in their exception tracker, and the world can get into a bad state.

This scenario is not covered by the `ShouldBeUnique` contract, as that is scoped to the job class and the podcast instance. The unique keys for both of these jobs are different:

 - `laravel_unique_job:App\Jobs\DestroyPodcast95`
 - `laravel_unique_job:App\Jobs\UpdatePodcast95`

 This means that Laravel will allow both jobs to be dispatched to the queue at any given time.

This is where the `WithoutOverlapping` middleware can come into play.

The `WithoutOverlapping` middleware allows us to reserve a resource across _different_ jobs. In our case, we don't want different operations occurring on the same `$podcast` instance. 

When two jobs on the queue want to reserve the same podcast, rather than discarding the job, like we saw with the `ShouldBeUnique` contract, the second job will instead be released back onto the queue for processing again in the future.

Our jobs may implement the `WithoutOverlapping` middleware via the `middleware()` method:

```php
namespace App\Jobs;

/* ... */
use App\Models\Podcast;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;

class DestroyPodcast implements ShouldQueue, ShouldBeUnique
{
    /* ... */

     public function middleware()
     {
        return [
            (new WithoutOverlapping(Podcast::class.':'.$this->podcast->id))
                ->releaseAfter(5)
                ->expireAfter(15)
                ->shared(),
        ];
     }
}
```

Configuration used here:

- `->releaseAfter(5)`: Release the conflicting job back onto the queue and delay execution by `5` seconds.
- `->expireAfter(15)`: If something unexpected happens and the lock is not released, ensure the lock is released after `15` seconds.
- `->shared()`: This will ensure that the lock is shared across different job types. This is important for our use-case.

With all this in place, we can now guarantee that only one job will be manipulating our podcast at any given time.

```
> "PATCH /podcasts/95" request received.
> Processing UPDATE job starts...
> "DELETE /podcasts/95" request received.
> Lock hit. Processing DESTROY job released back onto the queue...
> Lock hit. Processing DESTROY job released back onto the queue...
> Processing UPDATE job ends.
> Processing DESTROY job starts...
> Processing DESTROY job ends.
```

It should be noted that each time the job is released back onto the queue, the "attempts" count is incremented. This means we need to tweak our `$maxExceptions` and `$tries` properties to get a more expected outcome. After all, if the `DestroyPodcast` job was pushed back onto the queue 3 times, it might be considered "failed".

We will bump up the number of times the job may be attempted to `30`, but limit the number of exceptions that may be thrown while the job is executing to `3`.

```php
namespace App\Jobs;

/* ... */
use App\Models\Podcast;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;

class DestroyPodcast implements ShouldQueue, ShouldBeUnique
{
    public $tries = 30;

    public $maxExceptions = 3;

    /* ... */

     public function middleware()
     {
        return [
            (new WithoutOverlapping(Podcast::class.':'.$this->podcast->id))
                ->releaseAfter(5)
                ->expireAfter(15)
                ->shared(),
        ];
     }
}
```

You should of course tweak all these configuration values to work for your system.

We have now solved our two problems. Duplicate requests are now handled gracefully by our system, and we have stopped simultaneous manipulation of resources from different job types. But I wanted to cover one more aspect of the `WithoutOverlapping` middleware that is pretty sweet.

## Reserving mulitple individual resources

The `middleware()` method returns an array of middleware. This is nice because we can return different instances of the `WithoutOverlapping` middleware to reserve multiple resources for a single job.

Perhaps when we are deleting a podcast, we don't want to be manipulating one of the episodes. We could return an instance of `WithoutOverlapping` for each episode of the podcast...

```php
namespace App\Jobs;

/* ... */
use App\Models\Episode;
use App\Models\Podcast;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;

class DestroyPodcast implements ShouldQueue, ShouldBeUnique
{
    /* ... */

     public function middleware()
     {
        return [
            (new WithoutOverlapping(Podcast::class.':'.$this->podcast->id))
                ->releaseAfter(5)
                ->expireAfter(15)
                ->shared(),
            ...$this->podcast->episodes->map(
                fn ($episode) => (new WithoutOverlapping(Episode::class.':'.$episode->id))
                    ->releaseAfter(5)
                    ->expireAfter(15)
                    ->shared()
            ),
        ];
     }
}
```

Now we have a job that will be unique on the queue, and that will also reserve the podcast and all episodes while it is executing. If other jobs try to reserve the podcast or any of its episodes, they will not execute in parallel, but instead be executed in series.
