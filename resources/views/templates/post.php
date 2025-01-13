<?php

/**
 * Props.
 *
 * @var object $page
 * @var string $content
 * @var string $projectBase
 * @var \TiMacDonald\Website\Request $request
 * @var \TiMacDonald\Website\Url $url
 * @var (callable(string): void) $e
 * @var \TiMacDonald\Website\Markdown $markdown
 * @var \TiMacDonald\Website\Collection $collection
 */

require "{$projectBase}/resources/views/templates/head.php"; ?>
<article class="flex justify-center flex-grow px-6">
    <div class="w-full max-w-xl">
        <header class="mt-12 md:mt-16">
            <h1 class="text-3xl font-black leading-none text-center md:text-4xl text-electric-violet-900 dark:text-text-100">
                <?php $e($page->title); ?>
            </h1>
            <div class="flex items-center justify-center mt-3">
                <div class="flex-shrink-0 mr-2">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" class="w-4 h-4 fill-current">
                        <?php if ($page->format === 'video') { ?>
                            <title>Video post</title>
                            <path d="M16 7l4-4v14l-4-4v3a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2V4c0-1.1.9-2 2-2h12a2 2 0 0 1 2 2v3zm-8 7a4 4 0 1 0 0-8 4 4 0 0 0 0 8zm0-2a2 2 0 1 1 0-4 2 2 0 0 1 0 4z"/>
                        <?php } else { ?>
                            <title>Blog post</title>
                            <path d="M0 6c0-1.1.9-2 2-2h16a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2V6zm2 0v2h2V6H2zm1 3v2h2V9H3zm-1 3v2h2v-2H2zm3 0v2h10v-2H5zm11 0v2h2v-2h-2zM6 9v2h2V9H6zm3 0v2h2V9H9zm3 0v2h2V9h-2zm3 0v2h2V9h-2zM5 6v2h2V6H5zm3 0v2h2V6H8zm3 0v2h2V6h-2zm3 0v2h4V6h-4z"/>
                        <?php } ?>
                    </svg>
                </div>
                <div class="text-sm">
                    by Tim MacDonald on the
                    <time datetime="<?php $e($page->date->format(DateTimeImmutable::ATOM)); ?>">
                        <?php $e($page->date->format('jS F, Y')); ?>
                    </time>
                </div>
            </div>
        </header>
        <div class="mt-8 md:mt-12 rich-text">
            <?php echo $markdown($content); ?>
        </div>
    </div>
</article>
<?php require "{$projectBase}/resources/views/templates/foot.php"; ?>
