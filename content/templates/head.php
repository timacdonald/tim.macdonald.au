<!doctype html>
<html lang="en" class="md:text-xl text-lg font-sans antialiased leading-tight bg-white text-electric-violet-950 dark:text-electric-violet-100 dark:bg-near-black">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <!-- Assets --><!-- TODO: preloads -->
        <link rel="stylesheet" href="<?php echo e($url->asset('assets/css/style.css')); ?>">
        <script src="<?php echo e($url->asset('assets/js/app.js')); ?>" async></script>
        <!-- Meta -->
        <title><?php echo e($page->title); ?></title>
        <meta name="description" content="<?php echo e($page->description); ?>">
        <link rel="home" href="<?php echo e($url->to('/')); ?>">
        <link rel="canonical" href="<?php echo e($request->url()); ?>">
        <link type="application/atom+xml" rel="alternate" href="<?php echo e($url->to('feed.xml')); ?>" title="Tim MacDonald">
        <?php if ($page->hidden) { ?>
            <meta name="robots" content="noindex">
        <?php } ?>
        <!-- Socials -->
        <meta property="og:site_name" content="Tim MacDonald">
        <meta property="og:locale" content="en_AU">
        <meta property="og:title" content="<?php echo e($page->title); ?>">
        <meta property="og:description" content="<?php echo e($page->description); ?>">
        <meta property="og:url" content="<?php echo e($request->url()); ?>">
        <meta property="og:image" content="<?php echo e($page->image); ?>">
        <meta property="og:image:height" content="630">
        <meta property="og:image:width" content="1200">
        <meta property="og:image:type" content="image/<?php echo e(match (true) {
            str_contains($page->image, '.png?') => 'png',
            str_contains($page->image, '.jpeg?') => 'jpeg',
            str_contains($page->image, '.jpg?') => 'jpeg',
        }); ?>">
        <meta property="og:type" content="<?php echo e($page->ogType); ?>">
        <?php
                // @if($page->type === 'article' || $page->type === 'talk')
                //     <meta property="article:publisher" content="{{ $page->profiles['twitter'] }}">
                //     <meta property="og:article:published_time" content="{{ $page->published_at->toIso8601String() }}">
                //     <meta property="og:article:modified_time" content="{{ $page->modified_at->toIso8601String() }}">
                // @endif
        ?>
        <meta name="twitter:site" content="@timacdonald87">
        <meta name="twitter:creator" content="@timacdonald87">
        <meta name="twitter:card" content="summary_large_image">
        <meta name="twitter:title" content="<?php echo e($page->title); ?>">
        <meta name="twitter:description" content="<?php echo e($page->description); ?>">
        <meta name="twitter:image" content="<?php echo e($page->image); ?>">
        <meta name="twitter:image:height" content="630">
        <meta name="twitter:image:width" content="1200">
        <!-- Verifications -->
        <meta name="google-site-verification" content="iCKi0Ly3F3YRL_RJ_RImfZCyQjso8mWzwmsqg__7u4U">
        <meta name="msvalidate.01" content="72E9C6204C7ED590A00C0D9D5AED2D52">
        <!-- Favicons and device themes -->
        <link rel="apple-touch-icon" sizes="180x180" href="<?php echo e($url->asset('images/favicon/apple-touch-icon.png')); ?>">
        <link rel="icon" type="image/png" sizes="32x32" href="<?php echo e($url->asset('images/favicon/favicon-32x32.png')); ?>">
        <link rel="icon" type="image/png" sizes="16x16" href="<?php echo e($url->asset('images/favicon/favicon-16x16.png')); ?>">
        <link rel="mask-icon" color="#5f40f6" href="<?php echo e($url->asset('images/favicon/safari-pinned-tab.svg')); ?>">
        <link rel="shortcut icon" href="<?php echo e($url->asset('images/favicon/favicon.ico')); ?>">
        <meta name="msapplication-TileColor" content="#5f40f6">
        <meta name="theme-color" content="#5f40f6">
    </head>
    <body class="flex flex-col min-h-screen">
        <?php if ($page->showMenu) { ?>
            <div class="h-2 bg-purple-500 dark:bg-purple-400"></div>
            <button class="fixed top-0 right-0 z-10 flex items-center justify-center w-10 h-10 mt-4 mr-4 text-electric-violet-600 rounded-full dark:text-purple-400 hover:text-purple-700 dark:hover:text-purple-600 bg-electric-violet-200 dark:bg-text-100 bg-opacity-25 dark:bg-opacity-25 bg-blur-5" aria-label="Open menu" data-micromodal-trigger="main-menu">
                <svg role="img" class="w-5 h-5 fill-current" focusable="false" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 14 14">
                    <path d="m 1,9.6262715 12,0 0,2.6837195 -12,0 z m 0,-3.909844 12,0 0,2.68288 -12,0 z m 0,-4.026418 12,0 0,2.684558 -12,0 z"/>
                </svg>
            </button>
        <?php } ?>
