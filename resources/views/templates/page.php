<?php

/**
 * Props.
 *
 * @var \TiMacDonald\Website\Page $page
 * @var string $content
 * @var string $projectBase
 * @var \TiMacDonald\Website\Request $request
 * @var \TiMacDonald\Website\Url $url
 * @var (callable(string): void) $e
 * @var \TiMacDonald\Website\Markdown $markdown
 * @var \TiMacDonald\Website\Template $template
 * @var \TiMacDonald\Website\Collection $collection
 */

// ...

$template('head', ['page' => $page]);
echo $content;
$template('foot');
