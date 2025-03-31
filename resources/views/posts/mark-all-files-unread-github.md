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
    title: 'Mark all files as un-viewed in a GitHub pull request',
    description: 'Use the "viewed" feature on GitHub PRs? Me too, and I wanted to ability to mark *all* files as un-viewed.',
    date: new DateTimeImmutable('@1743457936', new DateTimeZone('Australia/Melbourne')),
    image: $url->asset('magic-values.png'),
    formats: [Format::Article],
);

?>

GitHub has a feature to mark individual files as _viewed_ while reviewing a pull request.

![GitHub viewed feature in use while reviewing a pull request](<?php $e($url->asset('github-viewed-feature.png')); ?>)

I use this feature extensively while reviewing pull requests, including while I review my own pull requests before requesting a review from the team.

When I review a pull request, I often do several passes. The first is usually a general code scan that allows me to take in the full context of the change. Another to review the feature with all the context in my head. Another to check for security or performance issues. Perhaps another to ensure the tests cover the functionality implemented, or at least the critical paths.

I use the viewed feature to track where I'm up to and what files I've covered, as a review is not always top-to-bottom. This means I'm often marking all files as viewed, then marking all files as un-viewed ready for my next pass at the change.

Being able to collapse files is also a feature GitHub exposes. Files can be collapsed one-by-one by clicking on the left-hand chevron. You can also bulk-collapse files by holding option (on Mac) and clicking a chevron on any file.

Unlike collapsing files, marking all files as un-viewed is a manual process that can often take a while. As far as I can tell, no built-in bulk mark-as-un-viewed feature seems to exist and I got tired of doing it manually. I decided to automate the process:


```javascript
document.querySelectorAll('.js-reviewed-checkbox[checked]')
        .forEach(checkbox => checkbox.click())
```

GitHub sends a request for each checkbox being toggled. So, to be a good citizen, I've augmented the script to put a short rest between each request. On my connection, each checkbox-toggle request takes ~400ms. For this reason, I've set the `delay = 400` in an attempt to not have more than one in-flight request at any given time.

```javascript
const delay = 400
let wait = 0

document.querySelectorAll('.js-reviewed-checkbox[checked]')
        .forEach(checkbox => {
            setTimeout(() => checkbox.click(), wait)
            wait += delay
        })
```

If you use the _viewed_ feature, hopefully you will find this useful. Fingers cross this becomes a first-party feature some day.
