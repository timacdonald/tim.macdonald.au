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

$now = new DateTimeImmutable('now', new DateTimeZone('Australia/Melbourne'));

echo "<?xml version=\"1.0\" encoding=\"utf-8\"?>\n"; ?>
<feed xmlns="http://www.w3.org/2005/Atom">
    <link href="<?php $e($url->to('/')); ?>" rel="alternate" type="text/html" />
    <link href="<?php $e($url->to('feed.xml')); ?>" rel="self" type="application/atom+xml" />
    <id><?php $e($url->to('feed.xml')); ?></id>
    <updated><?php $e($now->format(DateTimeImmutable::ATOM)); ?></updated>
    <title>Tim MacDonald</title>
    <subtitle>Developing engaging and performant web applications with Laravel and PHP. Love building for the web.</subtitle>
    <author>
        <name>Tim MacDonald</name>
        <uri>https://x.com/timacdonald87</uri>
    </author>
    <icon><?php $e($url->asset('profile.png')); ?></icon>
    <logo><?php $e($url->asset('fallback.png')); ?></logo>
    <?php foreach ($collection('posts') as $post) { ?>
        <entry>
            <id><?php $e($url->page($post)); ?></id>
            <title><?php $e($post->title); ?></title>
            <published><?php $e($post->date->format(DateTimeImmutable::ATOM)); ?></published>
            <updated><?php $e($post->date->format(DateTimeImmutable::ATOM)); ?></updated>
            <summary><?php $e($post->description); ?></summary>
            <content type="html"><![CDATA[Check out <a href="<?php $e($url->page($post)); ?>">the full article</a>.]]></content>
            <link href="<?php $e($url->page($post)); ?>" rel="alternate" type="text/html" title="<?php $e($post->title); ?>" />
            <media:thumbnail xmlns:media="http://search.yahoo.com/mrss/" url="<?php $e($post->image); ?>" />
            <author>
                <name>Tim MacDonald</name>
                <uri>https://x.com/timacdonald87</uri>
            </author>
        </entry>
    <?php } ?>
</feed>
