<?php
/**
 * Props.
 *
 * @var \TiMacDonald\Website\Page $page
 * @var string $projectBase
 * @var \TiMacDonald\Website\Request $request
 * @var \TiMacDonald\Website\Url $url
 * @var (callable(string): void) $e
 * @var \TiMacDonald\Website\Markdown $markdown
 * @var \TiMacDonald\Website\Collection $collection
 */
?>
        <footer class="border-t border-text-100 dark:border-text-700 mt-12 py-12">
            <nav class="px-6">
                <ul class="flex flex-wrap justify-center -mt-6 -ml-6 font-black text-xl leading-none ">
                    <li class="mt-6 ml-6">
                        <a href="<?php $e($url->to('/')); ?>" class="text-highlight text-electric-violet-600 dark:text-purple-400 hover:text-purple-700 dark:hover:text-purple-600">
                            Posts
                        </a>
                    </li>
                    <li class="mt-6 ml-6">
                        <a href="https://x.com/timacdonald87" class="text-electric-violet-600 dark:text-purple-400 hover:text-purple-700 dark:hover:text-purple-600">
                            X (Twitter)
                        </a>
                    </li>
                    <li class="mt-6 ml-6">
                        <a href="https://github.com/timacdonald" class="text-electric-violet-600 dark:text-purple-400 hover:text-purple-700 dark:hover:text-purple-600">
                            GitHub
                        </a>
                    </li>
                    <li class="mt-6 ml-6">
                        <a href="<?php $e($url->to('feed.xml')); ?>" class="text-electric-violet-600 dark:text-purple-400 hover:text-purple-700 dark:hover:text-purple-600">
                            RSS
                        </a>
                    </li>
                </ul>
            </nav>
            <div class="px-6 mt-8 text-center text-sm">
                I would like to acknowledge the Wurundjeri people who are the Traditional Custodians of the land on which I work
            </div>
        </footer>
        <div id="main-menu" aria-hidden="true">
            <div tabindex="-1" data-micromodal-close class="z-20 fixed inset-0 bg-white dark:bg-near-black flex items-center justify-center">
                <div role="dialog" aria-modal="true" aria-label="Main menu">
                    <nav class="flex items-center justify-center">
                        <ul class="font-black text-3xl leading-none text-center">
                            <li>
                                <a href="<?php $e($url->to('/')); ?>" class="text-highlight text-electric-violet-600 dark:text-purple-400 hover:text-electric-violet-700 dark:hover:text-purple-600">
                                    Posts
                                </a>
                            </li>
                            <li class="mt-6">
                                <a href="https://x.com/timacdonald87" class="text-electric-violet-600 dark:text-purple-400 hover:text-purple-700 dark:hover:text-purple-600">
                                    X (Twitter)
                                </a>
                            </li>
                            <li class="mt-6">
                                <a href="https://github.com/timacdonald" class="text-electric-violet-600 dark:text-purple-400 hover:text-purple-700 dark:hover:text-purple-600">
                                    GitHub
                                </a>
                            </li>
                            <li class="mt-6">
                                <a href="<?php $e($url->to('feed.xml')); ?>" class="text-electric-violet-600 dark:text-purple-400 hover:text-purple-700 dark:hover:text-purple-600">
                                    RSS
                                </a>
                            </li>
                        </ul>
                    </nav>
                    <button class="text-electric-violet-600 dark:text-purple-400 hover:text-purple-700 dark:hover:text-purple-600 fixed top-0 right-0 mt-4 mr-4 bg-electric-violet-200 dark:bg-text-100 bg-opacity-25 dark:bg-opacity-25 h-10 w-10 flex justify-center items-center rounded-full" aria-label="Close menu" onclick="window.MicroModal.close('main-menu');">
                        <svg role="img" class="fill-current h-5 w-5" focusable="false" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 14 14">
                            <path d="m 13,10.65657 q 0,0.40404 -0.28283,0.68686 l -1.37374,1.37374 Q 11.06061,13 10.65657,13 10.25253,13 9.9697,12.71717 L 7,9.74747 4.0303,12.71717 Q 3.74747,13 3.34343,13 2.93939,13 2.65657,12.71717 L 1.28283,11.34343 Q 1,11.06061 1,10.65657 1,10.25253 1.28283,9.9697 L 4.25253,7 1.28283,4.0303 Q 1,3.74747 1,3.34343 1,2.93939 1.28283,2.65657 L 2.65657,1.28283 Q 2.93939,1 3.34343,1 3.74747,1 4.0303,1.28283 L 7,4.25253 9.9697,1.28283 Q 10.25253,1 10.65657,1 q 0.40404,0 0.68686,0.28283 l 1.37374,1.37374 Q 13,2.93939 13,3.34343 13,3.74747 12.71717,4.0303 L 9.74747,7 12.71717,9.9697 Q 13,10.25253 13,10.65657 z"/>
                        </svg>
                    </button>
                </div>
            </div>
        </div>
    </body>
</html>
