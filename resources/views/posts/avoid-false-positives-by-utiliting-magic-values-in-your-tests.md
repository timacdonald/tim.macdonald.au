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
    title: 'Avoid false positives by utiliting magic values in your tests',
    description: 'It can be very hard to spot a false positive within a test. So how do you avoid them?',
    date: new DateTimeImmutable('@1591581600', new DateTimeZone('Australia/Melbourne')),
    image: $url->asset('magic-values.png'),
    formats: [Format::Video],
);

?>

<?php $template('vimeo', ['id' => '426674162']); ?>

In this video we look at how you can avoid false positives in your tests by making use of magic values. I'll show you some tests that seem to be passing, but in fact are a false positive. We refactor the tests to utilise magic values and fix the code to make sure our now failing tests pass ✅
