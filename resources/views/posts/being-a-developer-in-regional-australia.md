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

$page = Page::fromPost(
    file: __FILE__,
    title: 'Being a developer in regional Australia',
    description: "If you're in the Riverina and wanna meet up to discuss all things software - join our local developer Meetup.",
    date: new DateTimeImmutable('@1483069740', new DateTimeZone('Australia/Melbourne')),
    image: $url->asset('developer-meetup.png'),
);

?>

Finding a community of like-minded people to interact with is important for everyone. Whether you are a car enthusiast, Pokemon card collector, Xbox’r, or a software developer, a community gives us an outlet to meet new people, discuss topics, come up with new ideas, but most importantly, for me, to learn from others. People start clubs, get jobs that align with their interests, or have friends in the same circles, but as you move more regional it becomes harder and harder to find that niche community.

_But Tim, you’re a software developer…computers and the Internet are your jam – the community is available online 24/7!_

I’ve jumped into the online community, [joined Twitter]({{ $page->profiles['twitter'] }}).. and followed a bunch of people who are influential in my areas of interest – that’s the easy part. But who can I hit up to checkout a Github repository I’ve created, or a feature I’m thinking about adding to a project? Human interaction in real life is second to none, even for us coding nerds sitting in the corner of a dark room with our terminals open - hissing at people as they walk past. It’s how you meet people in other locations that you then can communicate with online, but now you know who this person is and you have some context for conversation.

I also feel that meeting someone and having a discussions with them can break down barriers and alleviate that [impostor syndrome](https://en.wikipedia.org/wiki/Impostor_syndrome) that we all get. When scrolling through Twitter, or reading some blog posts, you can feel it creeping up on you and all of a sudden you’re thinking...

_I’m not even going to be able to say something half intelligent to this person. They have obviously been developing for ~293, 389 years and know everything there is to know about every variation of tech out there…and now I need to start learning everything all over again_

For a while I looked for local communities in the software realm, but couldn’t find anything. I started looking more metro and found some established communities in Sydney and Melbourne, which lucky for me because Wagga is roughly halfway between them, so I can easily travel to be apart of them. But it got me thinking, there has to be other’s out there nearby with a similar problem. Now, no doubt it’s going to be a handful of people, but still we can get together and build a tight nit community. I know there are a stack load of web development businesses in Wagga Wagga alone, so I hope we can all come together, ditch that impostor syndrome and discuss our interests!

Having never seen the movie, I’ll use the quote anyway: _if you build it, they will come_. I encourage all and anyone interested in web, software, tools, services, or whatever it is related to tech, to join out Developer Meetup (Unfortunately the meetup has closed up shop and the link is no longer live). I specialise in PHP, but I want to interact with Python developers, database maintainers, or any other field. I also encourage others in far reaching remote areas to start your own Meetup. The [Meetups website](https://www.meetup.com) makes it really easy to get it off the ground and with a small amount of sponsorship from a local business you won’t even need to worry about the costs.

At the time or writing this we only have 7 members and are yet to have our first Meetup, but I am looking forward to 2017 and getting this thing off the ground. I’ve got no idea what we’re going to do, but we will all work it out together as we go. If you have any ideas for the Meetup, please join up and let us know, we would love to get people, both local and metro, to give talks and be apart of our community. Hit me up on [Twitter]({{ $page->profiles['twitter'] }}) if you want to discuss the Meetup without joining the page.

@component('_partials.update')
@slot('title')
    Update 27th October, 2018
@endslot
<p>
    I really enjoyed my time organising the meetup and getting to meet so many interesting people along the way. I have moved to Sydney but I have handed off the organisational responsibilities to some of the members of the meetup and I'm sure it is in good hands. I might even make it back for a few to catch up with everyone!
</p>
@endcomponent
