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
 * @var \TiMacDonald\Website\Collection $collection
 */

require "{$projectBase}/resources/views/templates/head.php";
echo $content;
require "{$projectBase}/resources/views/templates/foot.php";
