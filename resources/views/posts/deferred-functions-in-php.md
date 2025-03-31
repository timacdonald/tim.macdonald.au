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
    title: 'Deferred functions in PHP',
    description: "TODO",
    date: new DateTimeImmutable('now', new DateTimeZone('Australia/Melbourne')),
    image: $url->asset('dedicate-eloquent-model-query-builders.png'),
);

?>

A deferred function, or more generally deferred functionality, allows PHP to perform work after a response has been sent to the browser and the HTTP connection has been closed.

In this post we explore this idea, why it might be useful, and provide some ways this can be achieved in a PHP application.

To get started, we should make sure we have a solid understanding of the problem. Let's write some code. We are going to create a script that sends the browser the current date:

```php
<?php echo '<?php'; ?>


// Output the response...
echo 'The current datetime is ', date('Y-m-d H:i:s');
```


When a request is made to this script, we see that the response time is one millisecond.

![Browser screenshot showing application output and a response time of 1 milliseconds](<?php $e($url->asset('basic-request-duration.png')); ?>)

This is extremely fast; I'm sure this duration is not surprising to you right now.

Now we will introduce some work that is performed after the response has been generated. We'll make a `GET` request to an analytics service, although to simulate the service I'm going to hit [`httpbin`](https://httpbin.org/) with an artificial delay:

```php
<?php echo '<?php'; ?>


// Output the response...
echo 'The current datetime is ', date('Y-m-d H:i:s');

// Make a request to our analytics service...
file_get_contents('https://httpbin.org/delay/1');
```

As you might expect, the response time has increased. We now see the response taking nearly three seconds; a significant increase.

![Browser screenshot showing application output and a response time of 2.76 seconds](<?php $e($url->asset('request-with-outgoing-http-request.png')); ?>)


Browser = client
To illustrate a deferred function, imagine a web browser (the client) sends a request to your PHP powered application (the server). The PHP application generates a response, perhaps the basic string `"Hello world"`

> 💡 Did you know you can provide a comma separated list to `echo`.

## Outline

1. What are deferred functions any why are they useful.
    - Definition
    - Mentioned queues
    - Problem example
2. HTTP/1 headers example
- `Connection: close`
- `Content-length: 1`
`flush`
- https://datatracker.ietf.org/doc/html/rfc7540#section-8.1.2.2
3. CGI(?) / Nginx functions?
4. Why
    - Prioritize user facing work
    - Examples in other languages

FAQs
    - Can't you use Fibres to defer work?

---


---

## TODO

- [ ] Description
- [ ] Date
- [ ] Social image
