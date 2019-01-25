<?php

namespace Parfaitement;

use Illuminate\Http\Request;

class Controller
{
    public $request;

    public function __construct()
    {
        $this->request = Request::capture();
    }

    public function include_style($path)
    {
        add_action('wp_enqueue_scripts', function () use ($path) {
            wp_enqueue_style('extra-script' . $path, mix($path), [], null, 'all');
        });
    }

    public function include_script($path)
    {
        add_action('wp_enqueue_scripts', function () use ($path) {
            wp_enqueue_script('extra-script' . $path, mix($path), [], null, true);
        });
    }
}