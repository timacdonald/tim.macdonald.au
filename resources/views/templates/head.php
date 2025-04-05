<?php

use TiMacDonald\Website\OgType;

/**
 * Props.
 *
 * @var string $theme
 * @var \TiMacDonald\Website\Page $page
 * @var string $projectBase
 * @var \TiMacDonald\Website\Request $request
 * @var \TiMacDonald\Website\Url $url
 * @var (callable(string): void) $e
 * @var \TiMacDonald\Website\Markdown $markdown
 * @var \TiMacDonald\Website\Collection $collection
 */
?><!doctype html>
<html lang="en" class="md:text-xl text-lg font-sans antialiased leading-tight bg-white text-electric-violet-950 dark:text-electric-violet-100 dark:bg-near-black">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <!-- Assets -->
        <link rel="stylesheet" href="<?php $e($url->asset('site.css')); ?>">
        <script src="<?php $e($url->asset('site.js')); ?>" async></script>
        <!-- Meta -->
        <title><?php $e($page->title); ?></title>
        <meta name="description" content="<?php $e($page->description); ?>">
        <link rel="home" href="<?php $e($url->to('/')); ?>">
        <link rel="canonical" href="<?php $e($request->url()); ?>">
        <link type="application/atom+xml" rel="alternate" href="<?php $e($url->to('feed.xml')); ?>" title="Tim MacDonald">
        <?php if ($page->hidden) { ?>
            <meta name="robots" content="noindex">
        <?php } ?>
        <!-- Socials -->
        <meta property="og:site_name" content="Tim MacDonald">
        <meta property="og:locale" content="en_AU">
        <meta property="og:title" content="<?php $e($page->title); ?>">
        <meta property="og:description" content="<?php $e($page->description); ?>">
        <meta property="og:url" content="<?php $e($request->url()); ?>">
        <meta property="og:image" content="<?php $e($page->image); ?>">
        <meta property="og:image:height" content="630">
        <meta property="og:image:width" content="1200">
        <meta property="og:image:type" content="image/<?php $e(match (true) {
            str_contains($page->image, '.png?') => 'png',
            str_contains($page->image, '.jpeg?') => 'jpeg',
            str_contains($page->image, '.jpg?') => 'jpeg',
            default => throw new RuntimeException("Unknown og:image:type extension [{$page->image}].")
        }); ?>">
        <meta property="og:type" content="<?php $e($page->ogType->value); ?>">
        <?php if ($page->ogType === OgType::Article) { ?>
            <meta property="og:article:published_time" content="<?php $e($page->date->format(DateTimeImmutable::ATOM)); ?>">
        <?php } ?>
        <meta name="twitter:site" content="@timacdonald87">
        <meta name="twitter:creator" content="@timacdonald87">
        <meta name="twitter:card" content="summary_large_image">
        <meta name="twitter:title" content="<?php $e($page->title); ?>">
        <meta name="twitter:description" content="<?php $e($page->description); ?>">
        <meta name="twitter:image" content="<?php $e($page->image); ?>">
        <meta name="twitter:image:height" content="630">
        <meta name="twitter:image:width" content="1200">
        <!-- Favicons and device themes -->
        <link rel="apple-touch-icon" sizes="180x180" href="<?php $e($url->asset('apple-touch-icon.png')); ?>">
        <link rel="icon" type="image/png" sizes="32x32" href="<?php $e($url->asset('favicon-32x32.png')); ?>">
        <link rel="icon" type="image/png" sizes="16x16" href="<?php $e($url->asset('favicon-16x16.png')); ?>">
        <link rel="mask-icon" color="<?php $e($theme); ?>" href="<?php $e($url->asset('safari-pinned-tab.svg')); ?>">
        <link rel="shortcut icon" href="<?php $e($url->asset('favicon.ico')); ?>">
        <meta name="msapplication-TileColor" content="<?php $e($theme); ?>">
        <meta name="theme-color" content="<?php $e($theme); ?>">
    </head>
    <body class="flex flex-col min-h-screen">
        <div class="h-2 bg-purple-500 dark:bg-purple-400"></div>
        <?php if ($page->hidden) { ?>
            <div class="bg-electric-violet-950 flex text-white items-center justify-center text-center leading-none px-2 h-14">
                This post is hidden
            </div>
        <?php } ?>
        <button class="fixed top-0 right-0 z-10 flex items-center justify-center w-10 h-10 mt-4 mr-4 text-electric-violet-600 rounded-full dark:text-purple-400 hover:text-purple-700 dark:hover:text-purple-600 bg-electric-violet-200 dark:bg-text-100 bg-opacity-25 dark:bg-opacity-25 bg-blur-5" aria-label="Open menu" data-micromodal-trigger="main-menu">
            <svg role="img" class="w-5 h-5 fill-current" focusable="false" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 14 14">
                <path d="m 1,9.6262715 12,0 0,2.6837195 -12,0 z m 0,-3.909844 12,0 0,2.68288 -12,0 z m 0,-4.026418 12,0 0,2.684558 -12,0 z"/>
            </svg>
        </button>
