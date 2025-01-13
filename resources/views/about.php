<?php

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

$page = (object) [
    'showMenu' => true,
    'hidden' => false,
    'template' => 'page',
    'image' => $url->asset('fallback.png'),
    //
    'title' => 'About',
    'description' => 'About me',
    'ogType' => 'website',
];

?>---<?php

echo 'About';

?>---

