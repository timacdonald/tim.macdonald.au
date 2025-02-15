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
$template('head', ['page' => $page]); ?>
<article class="flex justify-center flex-grow px-6">
    <div class="w-full max-w-xl">
        <header class="mt-12 md:mt-16">
            <h1 class="text-3xl font-black leading-none text-center md:text-4xl text-electric-violet-900 dark:text-text-100">
                <?php $e($page->title); ?>
            </h1>
            <div class="text-center text-sm mt-3 text-electric-violet-900">
                    <time datetime="<?php $e($page->date->format(DateTimeImmutable::ATOM)); ?>">
                        <?php $e($page->date->format('jS F, Y')); ?>
                    </time>
            </div>
        </header>
        <div class="mt-8 md:mt-12 rich-text">
            <?php echo $markdown($content); ?>
        </div>
    </div>
</article>
<?php $template('foot'); ?>
