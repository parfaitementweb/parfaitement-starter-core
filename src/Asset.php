<?php

if (! function_exists('asset')) {
    function asset($path)
    {
        return get_template_directory_uri() . '/resources/assets/' . ltrim($path, '/');;
    }
}