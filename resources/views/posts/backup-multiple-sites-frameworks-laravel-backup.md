<?php

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

$page = Page::fromPost(
    file: __FILE__,
    title: 'Backup multiple sites and frameworks with Laravel Backup',
    description: "Using Spatie's Laravel Backup package you can backup several sites on a server from a single app install, including Laravel apps and WordPress sites.",
    date: new DateTimeImmutable('@1509411600', new DateTimeZone('Australia/Melbourne')),
    image: $url->asset('backup-multiple-sites-frameworks-laravel-backup.png'),
);

?>

[Laravel Backup](https://github.com/spatie/laravel-backup) is a fantastic package brought to us by the team at [Spatie](https://spatie.be), and is only one of the many [great packages](https://github.com/spatie) they have in the wild. The backup package makes it super easy to backup your Laravel applications, including all your files and production database, to a 3rd party storage facility such as [Amazon S3](https://aws.amazon.com/s3/).

I'm not going to run you through the standard setup or all the great features of the package here, you should definitely get your feet wet and give it a go. You'll be up and running with backups in no time at all. From here on I'll assume you've had some experience with the package, as to not over explain every step along the way...I do tend to rant off topic otherwise 🤓

I currently have a server that hosts all of my smaller sites comfortably. These smaller sites are mostly static websites held in Git, a few of them are Laravel apps, but several of them are also....wait for it....WordPress sites.

I wanted to have a standardised backup system in place for all my sites. This system would have to include Laravel and WordPress installs - so I tinkered with Spatie's Laravel Backup package and have managed to get a single install of Laravel to backup all my sites independently, including my WordPress sites 🎉

Having a single app managing the server-wide backups gives me a couple of benefits. Depending on your sites, these might not be benefits, but I'm happy with the result so far.

1. **Single retention policy.** This is great as I am more than happy for all my sites to have a consistent retention policy. Makes it really quick to adjust the policy when needed across all my sites being backed up.
2. **Single backup schedule.** If I ever want to change the time my backups are run, I have one single location to do that. Want to move the backups from midnight to 9am, one change and your done.

We'll break this whole thing down into 4 sections: the sites, the app, the command, and the schedule. Let's dig in.

## The sites

We'll use this server structure as our example through the post.

```
home/forge/staticsite1.com.au/
home/forge/laravelsite1.com.au/
home/forge/laravelsite2.com.au/
home/forge/wordpresssite1.com.au/
home/forge/wordpresssite2.com.au/
```

Looking at this structure we can easily determine that we don't need to backup our `staticsite1.com.au` as it is held in Git and built with a static site generator. So if we were to have a server meltdown, we'd easily be able to restore it.

Based on that, now we only need to concern ourselves with these sites:

```
home/forge/laravelsite1.com.au/
home/forge/laravelsite2.com.au/
home/forge/wordpresssite1.com.au/
home/forge/wordpresssite2.com.au/
```

Then one could easily say: "well let's just install the Laravel Backup package on both the Laravel sites and we can tick them off". So now we are left with these WordPress sites:

```
home/forge/wordpresssite1.com.au/
home/forge/wordpresssite2.com.au/
```

Just get a WordPress backup plugin. Yea...I'd rather not. I really try not to populate my WordPress install with plugins and just use it for it's CMS functionality in most cases.

I'd also then have to maintain settings across several sites and different tools. It just does not sound great to me. Instead, why don't we use the hard work of Spatie's team to standardise our backups across this whole server.

## The app

Let's start by creating a fresh install of Laravel. You'll understand my naming choice for the app later on. For now, let's just get this thing going.

I'm going to backup to S3, so we are going to need to make our project from `laravel/laravel` then install both `spatie/laravel-backup` and `league/flysystem-aws-s3-v3`.

```bash
composer create-project --prefer-dist laravel/laravel app.breezy-thunder
cd app.breezy-thunder
composer require spatie/laravel-backup
composer require league/flysystem-aws-s3-v3
```

Next we wanna make sure that Laravel Backup will push our generated backups to S3. We'll want to edit the config so let's publish it.

```bash
php artisan vendor:publish --provider="Spatie\Backup\BackupServiceProvider" --tag="config"
```

Then we need to set the disk to S3 in the config file, and I also don't want a notification every time a backup runs. Open `config/backup.php` and adjust these lines:

```php
'disks' => [
    // 'local',
    's3',
],
...
'mail' => [
    // 'to' => 'your@example.com',
    'to' => null,
],
```

There are plenty of other great settings you can play with, but I'm only covering the essential one's for this walk-through.

## The command

We are going to wrap the existing backup command provided by the Laravel Backup package in our own command to add some extra functionality. This is going to be where the magic happens 🎩

```bash
php artisan make:command BackupCommand
```

We are going to provide the `backup:run` and `backup:clean` functionality via this new command by calling `app:backup` and `app:backup --clean` respectively. We'll first setup our basic structure.

```php
namespace App\Console\Commands;

use Illuminate\Console\Command;

class BackupCommand extends Command
{
    protected $signature = 'app:backup {--clean}';

    protected $description = 'Backup all the sites';

    public function handle()
    {
        //
    }
}
```

As mentioned we are going to be backing up several sites. Although the Laravel Backup package seems to be Laravel specific, at the end of the day it runs in Laravel but really doesn't care what type of site you are backing up.

It does however assume you are backing up a single site\*\*, so we are going to play with the config while backing up each site to ensure we get the right sites in the right backup folders in our S3 bucket.

\*\* I should mention that it is _possible_ to back up multiple sites using the package as is, however it would mean that all the sites would end up in a single folder on your backup disk. This means that keeping track of the backups and also moving a site from one server to another is going to cause headaches for you. Using the method I outline in the article will keep your site backups separated for easier access and the ability to move from server to server with ease! Also, when the times comes to restore in a panic, you'll appreciate the separation 😅

We are going to add a `$sites` array to hold our site information, specifying the site name (domain), database, and folder paths for inclusion in the backups. The folder paths are all based from the root  `home/forge` directory.

```php
protected $sites = [
    [
        'domain' => 'laravelsite1.com.au',
        'database' => 'laravel_site_1',
        'paths' => ['laravelsite1.com.au/storage/app/public'],
    ],
    [
        'domain' => 'laravelsite2.com.au',
        'database' => 'laravel_site_2',
        'paths' => ['laravelsite2.com.au/storage/app/public'],
    ],
    [
        'domain' => 'wordpresssite1.com.au',
        'database' => 'wordpress_site_1',
        'paths' => ['wordpresssite1.com.au/public/wp-content/uploads'],
    ],
    [
        'domain' => 'wordpresssite2.com.au',
        'database' => 'wordpress_site_2',
        'paths' => ['wordpresssite2.com.au/public/wp-content/uploads'],
    ],
];
```

To add new sites to your backup schedule, you can simply add to this list and boom - it'll be taken care of.

We are very close now - I can already feel the sleep we are going to get knowing everything is safely backed up 😴

All we need to do is loop through these sites, setup the config (site name, database, and includes) and run the underlying package backup command. Let's see how we get our config setup:

```php
use Illuminate\Support\Facades\Config;

...

public function handle()
{
    foreach ($this->sites as $site) {
        $this->setupConfigForSite($site);
        // @todo: call backup command
    };
}

protected function setupConfigForSite($site)
{
    Config::set('backup.backup.name', $this->siteName($site));

    Config::set('database.connections.mysql.database', $site['database']);

    Config::set('backup.backup.source.files.include', $this->siteIncludes($site));
}

protected function siteName($site)
{
    return 'https://'.$site['domain'];
}

protected function siteIncludes($site)
{
    return array_map(function ($path) use ($site) {
        return base_path("../{$site['domain']}/$path");
    }, $site['paths']);
}
```

The `siteIncludes` function might just need a quick once over here. This method will return the `$site['paths']` array, but we prepend each path in the array with `../SITE_DOMAIN/`. This allows us to specify paths relative to the `home/forge` directory in our `$sites` array.

As we loop through each site, the app's config is being adjusted so that when we run the underlying backup command, it knows which paths to include, and what to call the backup, i.e. the sites name.

Now let's look at calling the underlying backup command. By the way, it's all down-hill from here - we've covered the hard stuff!

```php
public function handle()
{
    foreach ($this->sites as $site) {
        $this->setupConfigForSite($site);
        $this->callBackupCommand();
    };
}

protected function callBackupCommand()
{
    $this->call('backup:'.$this->backupType());
}

protected function backupType()
{
    return $this->option('clean') ? 'clean' : 'run';
}
```

As you can see with the above code snippet, we simply call the underlying backup command and check for the `--clean` flag to determine which one, i.e `backup:run` or `backup:clean`, to call. This allows us to not only backup multiple sites, but also clean multiple sites - it's dreamy 😍

Now that we have these in place, we can manually call on the command line:

```bash
# backup
php artisan app:backup

# clean
php artisan app:backup --clean
```

Make sure you give this app root DB access in your `.env` (on Forge it's the `forge` user) as it will need to be able to hit all of your databases. You’ll also wanna make sure you have you S3 credentials in your `.env` file as well.

## The schedule

Now that we have our command in place, we will take a look at the schedule. Because we are wrapping the package, the command is as straightforward as using the underlying package directly.

I like to backup at midnight each night with a cleanup at 1:00. Let's crack open our `Console/Kernel.php` and setup our schedule.

```php
protected function schedule(Schedule $schedule)
{
    $schedule->command('app:backup')
        ->dailyAt('12:00')
        ->timezone('Australia/Sydney');

    $schedule->command('app:backup --clean')
        ->dailyAt('1:00')
        ->timezone('Australia/Sydney');
}
```

## We are ready for action

Just to wrap up what we've done:

1. Start a new Laravel app and install the Laravel Backup package and the Flysystem S3 package.
2. Setup our config for the Laravel Backup package.
3. Built our own Backup command that wraps the underlying package command.
4. Setup our schedule to run the backups at specific intervals.

If you would like to see a more comprehensive version that also supports running, cleaning, monitoring, and listing your backups with the approach, you can [check out the package I put together](https://github.com/timacdonald/multisite-backup-command)). But please remember to also monitor your backups from a separate server isolated from your backup location!

Well it has been a blast getting here. I love the simplicity of this solution to have a single app running my backups on my server. Feels gooooood. Oh yea...the server. Well, if you've come this far you might as well hang around while we get the schedule running on the server....right?!?

## The server

We are going to create a site on the server – using [Laravel Forge](https://forge.laravel.com) – that isn't reachable by the outside world i.e. I'm not going to register a domain for it. My servers name is `breezy-thunder`, so I'm going to create a site named `app.breezy-thunder`.

![create new site in forge ui](<?php $e($url->asset('create-new-site-in-forge.png')); ?>)

Put the app we just built into version control and get it deployed to the server (I'm not going to cover this here - there are lots of great articles out there already covering this). Once deployed to your server, add a CRON job to make sure the schedule runs.

![add cron job in forge ui](<?php $e($url->asset('add-cron-job-in-forge.png')); ?>)

## Final thoughts

- In this article I've focused on how great Laravel Backup is - but just wanted to take a chance to say that life would absolutely suck without the hard work by [Frank de Jonge](https://twitter.com/frankdejonge) to create [Flysystem](http://flysystem.thephpleague.com/). Go tell him he is awesome - "Frank - thank you - you are awesome!".
- I've utilised S3 for my backups - but with the power of Flysystem powering the backup transfer, you can easily use this solution for other providers as well.
- I think this should go without saying, but just in case - please make sure you don't backup to the same provider that your website is hosted with. If they go down - you could lose access to your site AND your backups.
- Practice your backup restore. It is no good having a backup system in place only to find you forgot to include a specific folder in the backups. When you realise that - it could already be to late.
- You will want to setup a monitor for your backups to alert you when they are failing. Luckily the Laravel Backup package already has that functionality. Honestly - go play with it if you didn't already know that.
- If you can, thank Spatie for this great package by [sending them a postcard](https://github.com/spatie/laravel-backup#postcardware) and [supporting them on Patreon](https://www.patreon.com/spatie)
- There are some great hosted solutions for backups out there as well. You could check out [Snapshooter](https://snapshooter.com) as a great starting point.
