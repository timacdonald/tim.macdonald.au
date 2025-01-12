<?php

$page = (object) [
    'showMenu' => true,
    'hidden' => false,
    'template' => 'page',
    'image' => $url->asset('fallback.png'),
    // ...
    'title' => 'Tim MacDonald • Laravel & PHP Developer • Melbourne, Australia',
    'description' => 'Developing engaging and performant web applications with Laravel and PHP. Love building for the web.',
    'ogType' => 'website',
];

$posts = array_map(static function (string $path) use ($__data): object {
    extract($__data);

    if (! ob_start()) {
        throw new RuntimeException('Unable to start output buffering.');
    }

    require $path;

    ob_end_clean();

    // TODO ignore hidden

    return $page;
}, glob("{$projectBase}/resources/views/posts/*"));

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
        <ul>
            <?php foreach ($posts as $post) { ?>
                <li class="relative pb-16 md:pb-8 md:pt-4 my-16 md:-ml-8 md:pl-8 md:border-l-2 border-electric-violet-100 dark:border-electric-violet-900">
                    <h3 class="text-lg font-black text-electric-violet-600 dark:text-purple-400 hover:text-purple-700 dark:hover:text-purple-600 md:text-2xl">
                        <a href="<?php $e($post->url); ?>">
                            <?php $e($post->title); ?>
                        </a>
                    </h3>
                    <div class="flex items-center mt-2">
                        <div class="flex-shrink-0 mr-2">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" class="w-4 h-4 fill-current">
                                <?php if ($post->format === 'video') { ?>
                                    <title>Video post</title>
                                    <path d="M16 7l4-4v14l-4-4v3a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2V4c0-1.1.9-2 2-2h12a2 2 0 0 1 2 2v3zm-8 7a4 4 0 1 0 0-8 4 4 0 0 0 0 8zm0-2a2 2 0 1 1 0-4 2 2 0 0 1 0 4z"/>
                                <?php } else { ?>
                                    <title>Blog post</title>
                                    <path d="M0 6c0-1.1.9-2 2-2h16a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2V6zm2 0v2h2V6H2zm1 3v2h2V9H3zm-1 3v2h2v-2H2zm3 0v2h10v-2H5zm11 0v2h2v-2h-2zM6 9v2h2V9H6zm3 0v2h2V9H9zm3 0v2h2V9h-2zm3 0v2h2V9h-2zM5 6v2h2V6H5zm3 0v2h2V6H8zm3 0v2h2V6h-2zm3 0v2h4V6h-4z"/>
                                <?php } ?>
                            </svg>
                        </div>
                        <?php if ($post->external_link) { ?>
                            <div class="flex-shrink-0 mr-2">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" class="w-4 h-4 fill-current">
                                    <title>Guest post</title>
                                    <path d="M9.26 13a2 2 0 0 1 .01-2.01A3 3 0 0 0 9 5H5a3 3 0 0 0 0 6h.08a6.06 6.06 0 0 0 0 2H5A5 5 0 0 1 5 3h4a5 5 0 0 1 .26 10zm1.48-6a2 2 0 0 1-.01 2.01A3 3 0 0 0 11 15h4a3 3 0 0 0 0-6h-.08a6.06 6.06 0 0 0 0-2H15a5 5 0 0 1 0 10h-4a5 5 0 0 1-.26-10z"/>
                                </svg>
                            </div>
                        <?php } ?>
                        <div>
                            <time datetime="<?php $e($post->date->format(DateTimeImmutable::ATOM)); ?>" class="block text-sm">
                                <?php $e($post->date->format('jS F, Y')); ?>
                            </time>
                        </div>
                    </div>
                    <p class="mt-4 leading-snug">
                        <?php $e($post->description); ?>
                    </p>
                    <div class="bg-gradient-to-r from-electric-violet-100 to-white dark:from-electric-violet-900 dark:to-near-black w-32 absolute left-0 ml-0 bottom-0" style="height:2px" aria-hidden></div>
                </li>
            <?php } ?>
        </ul>
    </div>
</div>
