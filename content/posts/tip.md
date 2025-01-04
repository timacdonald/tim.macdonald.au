<?php

$page = (object) [
    'template' => 'post',
    'showMenu' => true,
    'hidden' => false,

    // ...

    'title' => 'Tip!',
    'description' => 'This is a tip!',
    'image' => 'image.png?v=1',
    'ogType' => 'article',
];

?># <?php echo 'Tip'; ?>

This *is* _the_ [tip](https://google.com).
