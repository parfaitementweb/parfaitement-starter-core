<?php

add_filter('script_loader_tag', function ($tag) {
    return str_replace(' src', ' async="async" src', $tag);
}, 10);