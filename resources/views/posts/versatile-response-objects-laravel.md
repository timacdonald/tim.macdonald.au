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
    title: "Versatile response objects in Laravel",
    description: "I've found that introducing dedicated response objects that can handle multiple response formats is a really nice pattern to cleanup my controllers",
    date: new DateTimeImmutable('@1526004000', new DateTimeZone('Australia/Melbourne')),
    image: $url->asset('response-objects.png'),
);

?>

I've been tinkering with a new way of returning various response formats by introducing dedicated response objects to my Laravel web applications. This has been heavily inspired by [DHH](https://x.com/dhh) and [Adam Wathan](https://x.com/adamwathan)'s chats on the [Full Stack Radio Podcast](http://www.fullstackradio.com) and I thought I would share my journey through, and ideas on, it with you.

## CRUD controller
Within my application I generally, if not always, approach my controllers from a [CRUD](https://en.wikipedia.org/wiki/Create,_read,_update_and_delete) only perspective. A `SandwichController` would provide the standard CRUD controller methods: `index`, `create`, `store`, `show`, `edit`,  `update`, and `destroy`. Each of these methods would return a response suitable for my web interface, i.e. a view or a redirect.

Here is a bare bones example of my general structure and approach:

```php
class SandwichController
{
    public function index()
    {
        return view('sandwiches.index', ['sandwiches' => Sandwich::paginate()]);
    }

    public function create()
    {
        return view('sandwiches.create', ['sandwich' => new Sandwich]);
    }

    public function store(SandwichRequest $request)
    {
        $sandwich = Sandwich::create($request->validated());

        return redirect()->route('sandwiches.show', $sandwich)->with(['status' => 'Sandwich created successfully']);
    }

    public function show(Sandwich $sandwich)
    {
        return view('sandwiches.show', ['sandwich' => $sandwich]);
    }

    public function edit(Sandwich $sandwich)
    {
        return view('sandwiches.edit', ['sandwich' => $sandwich]);
    }

    public function update(SandwichRequest $request, Sandwich $sandwich)
    {
        $sandwich->update($request->validated());

        return redirect()->route('sandwiches.show', $sandwich)->with(['status' => 'Sandwich updated successfully']);
    }

    public function destroy(Sandwich $sandwich)
    {
        $sandwich->delete();

        return redirect()->route('sandwiches.index')->with(['status' => 'Sandwich deleted successfully']);
    }
}
```

Nothing out of the ordinary there. All the CRUD methods are there and doing what you would expect. But as you can see these are all HTML responses. So what happens when you receive a request for a CSV export of all the Sandwiches? Well previously I'd reach for a [single action controller](https://dyrynda.com.au/blog/single-action-controllers-in-laravel) to handle that response.

## Single action controller
I felt it was a good idea to have another controller handle the CSV export directly, as I didn't like depending on / injecting a `CsvWriter`  in my main `SandwichController@index` method when 99% of requests are for a web interface response. It didn't *feel* right.

A single action controller is a controller that does not adhere to the CRUD methods. It instead has only one method, hence "single action". So I would create a `SandwichCsvExportController` and that will do the work to export my CSV. I would also create a route to hit this controller. I always struggled with if the route should be `GET` or `POST`. Whenever I'm doing something that felt out of place I always want to go for a `POST`, so I'll do that...I'm not really sure why past me felt like exporting a CSV was out of place - but I did so 🤷‍♂️

I would end up with a second controller and another route to handle this scenario which would receive the `CsvWriter` as a method dependency:

```php
Route::post('sandwiches/export', SandwichCsvExportController::class);

// ...

class SandwichCsvExportController
{
    public function __invoke(CsvWriter $csvWriter)
    {
        $csvWriter->insertOne($attributes = ['id', 'brand', 'strength']);

        Sandwich::each(function ($sandwich) use ($csvWriter, $attributes) {
            $csvWriter->insertOne($sandwich->only($attributes));
        });

        return response($csvWriter->getContent(), 200, [
            'Content-Encoding' => 'none',
            'Content-Description' => 'File Transfer',
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="sandwich-export.csv"',
        ]);
      }
}
```

And for a time - things were good.

But I started to think it was a bit strange using the `__invoke()` method. When I took a step back it looked to me like an `index()` call. After all I am showing a list of sandwiches...just in a different format...right?!? 🤔 Also that `POST` method and URL structure feels really strange to me _now_.

So I started moving these over to use `index()` instead of `__invoke()`, and went with the `GET` route method.

But I’m still going to be adding another controller for each new format: XML, JSON, etc. Something just was not right either way I looked at it and I knew this was not a good approach to solving this problem.

## Seeing the light
Then it hit me: I was really doing the same thing with my `SandwichController@index` and `SandwichCsvExportController@index`. Pulling the models out of the database and pushing them to a response. I knew I had to find a simple solution to combine these controllers but still offer the different response formats.

This all became crystal clear when I started adding filters into the mix. When both my `index()` methods were adding query scopes based on HTTP query parameters - I instantly saw the duplicate code and knew there had to be a better way as both controllers would always *start* exactly the same.

```php
public function index(Request $request)
{
    $sandwiches = Sandwich::when($request->distributor, function ($query, $value) {
        $query->whereDistributor($value);
    })-> // format specific stuff would follow...
}
```

## Ah-hah! Response objects & extensions
I listened to DHH and Adam talking about dealing with different response formats by looking at the `Accepts` header which works well with an API driven project. I also saw Adam tweet that he had [put together a macro](https://x.com/adamwathan/status/898244245433266176) to help with this kinda thing. In addition to this, I also saw the [`Responsable` interface](https://github.com/laravel/framework/blob/56a58e0fa3d845bb992d7c64ac9bb6d0c24b745a/src/Illuminate/Contracts/Support/Responsable.php) Laravel provided.

It all looked pretty neat and the cogs started turning, but I was busy on other projects and didn't have time to play and work out a better solution.

I found myself starting to build more API first approaches to web systems and the whole time I'm thinking: if I needed to introduce a web interface here, I'm again going to be duplicating these controllers: there. must. be. a. better. way!

Finally got some time to come back to the multi response format idea on a project and look at some ways to clean this all up using a new approach. However, since that Twitter post there was another episode of the Full Stack Radio where Adam and DHH also discussed the idea of responding to *file extensions* and why that is valid - and I think that was the ah-hah moment for me. So lets cover a few things that lead me to my final resting spot with response objects.

## File extensions

As a web developer, you can feel dirty adding a file extension to a URL. You have flashbacks to all those single file PHP apps `members.php` ...am I right? We moved away from file extensions in URLs, to front controlled apps with pretty URLs and we all felt much better about it. But I feel like we kind of forgot along the way when file extensions make sense.

I would never mind seeing an image file `/profile.png` of an audio file `/episode.mp3`. This is because the extension represents the file type - but why is that okay for _some_ file formats and not others?

From our perspective the file format sets an expectation. However the `.php` extension is an implementation detail. It does not tell us what _type_ of content is being returned.

From a developers perspective, a `.php` extension it tells us the code behind the scenes is PHP. These are two distinctly different things. One if fine, the other is not.

How is `.json` or `.csv` any different to `.mp3`?
Answer: they aren't.

Why can't we use file extensions to indicate the type of response format we want?
Answer: we can!

Anyone who has worked with API's enough is probably thinking...sure you _could_ use file extensions, but that is what the `Accept` header is for, and they would be correct. The `Accept` header allows the end user to specify the format they would like returned. But for a web site form you cannot send headers - and this is why taking file extensions into account makes so much sense to me. I want to be able to provide an easy way to download a CSV from a web page, but in a way that also plays well with an API driven approach.

## Response objects

Response objects are classes that implement the `Responsable` interface and can be returned from a controller. Laravel's container will call the `toResponse($request)` method on it. This allows you to move any complexity you might have creating a response out of the controller and into a dedicated object. They are really nice.

You can implement this interface on any object.

```php
class CSV implements Responsable
{
    protected $file;

    protected $filename;

    public function __construct($file, $filename)
    {
        $this->file = $file;

        $this->filename = $filename;
    }

    public function toResponse($request)
    {
        return response()->download($this->file, "{$this->filename}.csv", [
            'Content-Type' => 'text/csv',
        ]);
    }
}
```

If you were to return a instance of `CSV` from a controller, the container would call the `toResponse` method and return the result of that method to the browser. As you can see the method accepts the `$request` instance as well, which gives you a chance to check for specific input, or other values on the request that might need to be taken into account when creating the response.

## One controller to rule them all
I had a play with response objects and built a base `Responsable` class that would determine the expected response format (HTML, JSON, CSV, etc) based on the URLs file extension, and it falls back to the `Accepts` header.

If the expected response format was HTML, the `toHtmlResponse()` method would be called, if it was JSON the `toJsonResponse()` method would be called, and so on. This allowed me to break up the logic required to create format specific responses into their own methods. Adding a sprinkle of magic: **these methods are called by the container**, meaning that you can inject format specific dependencies as well.

This was just the solution I was looking for to combine my controllers. Suddenly I'm cleaning things up and everything is starting to click. I can pipe all responses that need to list `Sandwich`'s through the `SandwichController@index` method. I can share the filtering across all response formats and defer the creation of the actual response to a dedicated object. Combining both the HTML and CSV controller resulted in a really streamlined controller:

```php
class SandwichController
{
    public function index()
    {
        $query = Sandwich::when($request->distributor, function ($query, $value) {
            $query->whereDistributor($value);
        });

        return new SandwichIndexResponse($query);
    }
}
```

Within my response object I can now decide how I want things to happen for each response format. Notice how the controller does not care how the models are going to be returned, it is only concerned with getting the models out of the database, and delegates the format to the response object, meaning that we can now share all our filters across formats as well.

Here is a response object that extends my base `Responsable` class that will return our HTML, CSV, and JSON for our Sandwiches:

```php
class SandwichIndexResponse extends Response
{
    protected $query;

    public function __construct($query)
    {
        $this->query = $query;
    }

    protected function toHtmlResponse()
    {
        return view('sandwiches.index', ['sandwiches' => $this->query->paginate()]);
    }

    protected function toCsvResponse(CsvWriter $csvWriter)
    {
        $csvWriter->insertOne($attributes = ['id', 'brand', 'strength']);

        $this->query->each(function ($sandwich) use ($csvWriter, $attributes) {
            $csvWriter->insertOne($sandwich->only($attributes));
        });

        return response($csvWriter->getContent(), 200, [
            'Content-Encoding' => 'none',
            'Content-Description' => 'File Transfer',
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="sandwich-export.csv"',
        ]);
    }
}
```

## Routing

I think it is best to create a list of allowed extensions, and not just allow any through. Better for the router to throw a 404 than having it pass through the app to the Response object, which then throws a 404. To achieve this I've been using the following routing:

```php
Route::get('sandwiches{extension?}', [
    'as' => 'sandwiches.index',
    'uses' => 'SandwichesController@index',
])->where(['extension' => '^(.pdf)|(.csv)|(.json)$']);
```

Now our file extension URLs have a lot of meaning to our system:

```
// html endpoint
GET: /sandwiches

// csv endpoint
GET: /sandwiches.csv

// PDF endpoint
GET: /sandwiches.pdf

// json endpoint
GET: /sandwiches.json
```

and we we are able to download our dynamic format responses via `GET` requests with really nice URLs from the website...

```html
<h2>Downloads</h2>
<ul>
    <li><a href="/sandwiches.csv">CSV</a></li>
    <li><a href="/sandwiches.pdf">PDF</a></li>
</ul>
```

I am *really* loving this new pattern and it has been the kind of thing, for me, where I want to go back and re-write everything right now instead of waiting until I touch the code again to update it.

I also feel that it is more readable when dealing with multiple formats that each might do a bit of work.

Either way I think it is a great pattern. I am really looking forward to implementing it, while both cleaning up and combining some of my controllers! Hopefully some of this might be new to you as well and you can give it a whirl in your own applications. If you have any thoughts or your own implementations of this kind of thing I'd love to see it and learn more.

If you are interested in the base class that makes all this possible, [I made a gist](https://gist.github.com/timacdonald/8946fc4da8be6f1b3cf9f6d6a591106d#file-response-php) where you can check it out. If you have any suggestions on improvements I'd love to hear them.

## Links
- [Building Basecamp 3 like a Porsche 911 on Full Stack Radio](http://www.fullstackradio.com/32)
- [Stimulus in Practice + On Writing Software Well on Full Stack Radio](http://www.fullstackradio.com/83)
