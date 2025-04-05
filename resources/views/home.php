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

$page = new Page(
    file: __FILE__,
    image: $url->asset('fallback.png'),
    title: 'Tim MacDonald • Laravel & PHP Developer • Melbourne, Australia',
    description: 'Developing engaging and performant web applications with Laravel and PHP. Love building for the web.',
);

?><div class="flex justify-center px-6">
    <div class="w-full max-w-xl">
        <header class="flex flex-col items-center mt-12 text-center">
            <div class="w-32 h-32 overflow-hidden rounded-full shadow-inner">
                <img
                    src="<?php $e($url->asset('profile.png')); ?>"
                    alt="Profile image"
                    height="175"
                    width="175"
                    class="relative w-full bg-electric-violet-200 -z-10"
                >
            </div>
            <h1 class="mt-4 text-4xl font-black leading-none text-electric-violet-950 dark:text-text-100">
                Tim MacDonald
            </h1>
            <div class="mt-4 leading-snug">
                Developing engaging and performant web apps with Laravel &amp; <abbr title="&quot;PHP: Hypertext Preprocessor&quot; or previously &quot;Personal Home Page&quot;">PHP</abbr>
            </div>
            <div class="flex items-center mt-2 text-electric-violet-900 dark:text-electric-violet-100">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" class="w-4 h-4 fill-current">
                    <title>Location</title>
                    <path d="M0 0l20 8-8 4-2 8z"/>
                </svg>
                <span class="ml-2">Melbourne, Australia</span>
            </div>
        </header>
        <ul class="pt-16 md:pt-24">
            <?php foreach ($collection->{$production ? 'published' : 'all'}('posts') as $post) { ?>
                <li class="relative pb-16 md:pb-24">
                    <h3 class="text-lg font-black text-electric-violet-600 dark:text-purple-400 hover:text-purple-700 dark:hover:text-purple-600 md:text-2xl">
                        <a href="<?php $e($url->page($post)); ?>">
                            <?php $e($post->title); ?>
                        </a>
                    </h3>
                    <div class="flex items-center mt-3">
                        <div class="flex gap-2 flex-shrink-0 mr-2 items-center">
                            <?php if (in_array(Format::Article, $post->formats)) { ?>
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" class="w-4 h-4 fill-current">
                                    <title>Blog post</title>
                                    <path d="M0 6c0-1.1.9-2 2-2h16a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2V6zm2 0v2h2V6H2zm1 3v2h2V9H3zm-1 3v2h2v-2H2zm3 0v2h10v-2H5zm11 0v2h2v-2h-2zM6 9v2h2V9H6zm3 0v2h2V9H9zm3 0v2h2V9h-2zm3 0v2h2V9h-2zM5 6v2h2V6H5zm3 0v2h2V6H8zm3 0v2h2V6h-2zm3 0v2h4V6h-4z"/>
                                </svg>
                            <?php } ?>
                            <?php if (in_array(Format::Video, $post->formats)) { ?>
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" class="w-3 h-3 fill-current">
                                    <title>Video post</title>
                                    <path d="M16 7l4-4v14l-4-4v3a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2V4c0-1.1.9-2 2-2h12a2 2 0 0 1 2 2v3zm-8 7a4 4 0 1 0 0-8 4 4 0 0 0 0 8zm0-2a2 2 0 1 1 0-4 2 2 0 0 1 0 4z"/>
                                </svg>
                            <?php } ?>
                        </div>
                        <div>
                            <time datetime="<?php $e($post->date->format(DateTimeImmutable::ATOM)); ?>" class="block text-sm">
                                <?php $e($post->date->format('jS F, Y')); ?>
                            </time>
                        </div>
                    </div>
                    <?php if ($post->hidden) { ?>
                        <div class="mt-3 bg-electric-violet-950 inline-flex text-white items-center justify-center text-center leading-none px-2 h-8 rounded-sm">
                            This post is hidden
                        </div>
                    <?php } ?>
                    <p class="mt-3 leading-snug">
                        <?php $e($post->description); ?>
                    </p>
                </li>
            <?php } ?>
        </ul>
    </div>
</div>
