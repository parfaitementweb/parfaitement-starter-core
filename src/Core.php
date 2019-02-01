<?php

namespace Parfaitement;

use Dotenv\Dotenv;
use Illuminate\Config\Repository;
use Illuminate\Container\Container;
use Illuminate\Events\Dispatcher;
use Illuminate\Filesystem\Filesystem;
use Illuminate\View\Compilers\BladeCompiler;
use Illuminate\View\Engines\CompilerEngine;
use Illuminate\View\Engines\EngineResolver;
use Illuminate\View\Engines\PhpEngine;
use Illuminate\View\Factory;
use Illuminate\View\FileViewFinder;

class Core
{
    public $config;

    public function __construct()
    {
        $this->init();
    }

    protected function init()
    {
        $this->load_environment();
        $this->load_config();
        $this->add_blade_files_in_hierarchy();
        $this->enable_blade_compiler();
        $this->load_scripts();
        $this->clean_wordpress();
    }

    private function load_environment()
    {
        if (file_exists(get_template_directory() . '/.env')) {
            $dotenv = Dotenv::create(get_template_directory());
            $dotenv->load();
        }
    }

    public function blade_compiler($template)
    {
        if (strpos($template, '.blade.php') !== false) {
            // Configuration
            $pathsToTemplates = [get_template_directory() . $this->config->get('view.path', '/resources/views'), get_template_directory()];
            $pathToCompiledTemplates = get_template_directory() . $this->config->get('view.compiled', '/compiled');
            // Dependencies
            $filesystem = new Filesystem;
            $eventDispatcher = new Dispatcher(new Container);
            // Create View Factory capable of rendering PHP and Blade templates
            $viewResolver = new EngineResolver;
            $bladeCompiler = new BladeCompiler($filesystem, $pathToCompiledTemplates);
            $viewResolver->register('blade', function () use ($bladeCompiler) {
                return new CompilerEngine($bladeCompiler);
            });
            $viewResolver->register('php', function () {
                return new PhpEngine;
            });
            $viewFinder = new FileViewFinder($filesystem, $pathsToTemplates);
            $viewFactory = new Factory($viewResolver, $viewFinder, $eventDispatcher);

            // Get the name of the template file, without extensions
            $clear_name = str_replace('.blade.php', '', basename($template));

            $templateData = $this->get_template_data($clear_name);

            $compiled = $viewFactory->make($clear_name, $templateData)->render();

            echo $compiled;

            return null;
//            return get_template_directory() . '/index.php';
        }

        return $template;
    }

    public function filter_templates($templates)
    {
        $original = collect($templates);
        $blade_versions = collect([]);

        $original->each(function ($item) use ($blade_versions) {
            $blade_versions->push(str_replace('.php', '.blade.php', 'resources/views/' . $item));
            $blade_versions->push(str_replace('.php', '.blade.php', $item));
        });

        return $blade_versions->merge($original)->toArray();
    }

    protected function enable_blade_compiler()
    {
        add_filter('template_include', [$this, 'blade_compiler'], 99);
    }

    protected function add_blade_files_in_hierarchy()
    {
        collect([
            'index',
            '404',
            'archive',
            'author',
            'category',
            'tag',
            'taxonomy',
            'date',
            'embed',
            'home',
            'frontpage',
            'page',
            'paged',
            'search',
            'single',
            'singular',
            'attachment',
        ])->map(function ($type) {
            add_filter("{$type}_template_hierarchy", [$this, 'filter_templates']);
        });
    }

    /**
     * @param $class_name
     *
     * @return mixed
     */
    protected function get_template_data($class_name)
    {
        $templateData = [];

        $controller_class = '\App\Controllers\\' . ucfirst(camel_case($class_name));
        if (class_exists($controller_class)) {
            $controller = new $controller_class;

            if (method_exists($controller, 'view')) {
                $templateData = call_user_func([$controller, 'view']);
            }
        }

        return $templateData;
    }

    public function load_config()
    {
        $configPath = get_template_directory() . '/config/';
        $this->config = new Repository([
            'view' => require $configPath . 'view.php',
            'theme' => require $configPath . 'view.php'
        ]);
    }

    public function theme_scripts()
    {
        wp_enqueue_style('theme-style', mix('main.css'), [], null, 'all');
        wp_enqueue_script('theme-script', mix('app.js'), [], null, true);
    }

    private function load_scripts()
    {
        add_action('wp_enqueue_scripts', [$this, 'theme_scripts']);
    }

    public function clean_wordpress()
    {
        $files = glob(__DIR__ . '/clean_wordpress/*.php');
        collect($files)->each(function ($file) {
            $env_var = strtoupper(basename(str_replace('.php', '', $file)));
            if (env($env_var, true)) {
                require_once $file;
            }
        });
    }
}


