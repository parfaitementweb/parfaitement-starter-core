<?php

namespace Parfaitement;

use Dotenv\Dotenv;
use Illuminate\Config\Repository;
use Illuminate\Container\Container;
use Illuminate\Events\Dispatcher;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Http\Request;
use Illuminate\View\Compilers\BladeCompiler;
use Illuminate\View\Engines\CompilerEngine;
use Illuminate\View\Engines\EngineResolver;
use Illuminate\View\Engines\PhpEngine;
use Illuminate\View\Factory;
use Illuminate\View\FileViewFinder;

class Core
{
    protected $config;
    public $request = null;

    public function __construct()
    {
        $this->init();
    }

    protected function init()
    {
        $this->load_environment();
        $this->load_config();
        $this->theme_scripts();
        $this->clean_wordpress();

        $this->request = Request::capture();
    }

    protected function load_environment()
    {
        if (file_exists(get_template_directory() . '/.env')) {
            $dotenv = Dotenv::create(get_template_directory());
            $dotenv->load();
        }
    }

    protected function load_config()
    {
        $configPath = get_template_directory() . '/config/';
        $this->config = new Repository([
            'view' => require $configPath . 'view.php',
            'theme' => require $configPath . 'view.php'
        ]);
    }

    protected function theme_scripts()
    {
        add_action('wp_enqueue_scripts', function() {
            wp_enqueue_style('theme-style', mix('main.css'), [], null, 'all');
            wp_enqueue_script('theme-script', mix('app.js'), [], null, true);
        });
    }

    protected function clean_wordpress()
    {
        $files = glob(__DIR__ . '/clean_wordpress/*.php');
        collect($files)->each(function ($file) {
            $env_var = strtoupper(basename(str_replace('.php', '', $file)));
            if (env($env_var, true)) {
                require_once $file;
            }
        });
    }

    public function render($template, $view_data)
    {
        // Configuration
        $pathsToTemplates = [get_template_directory() . $this->config->get('view.path', '/resources/views'), get_template_directory()];
        $pathToCompiledTemplates = get_template_directory() . $this->config->get('view.compiled', '/compiled/views');

        if (! file_exists(get_template_directory() . $this->config->get('view.compiled', '/compiled/views'))) {
            die('Folder ' . get_template_directory() . $this->config->get('view.compiled', '/compiled/views') . ' does not exist.');
        }

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
        $clear_name = str_replace(['.blade.php', '.php'], '', basename($template));

        $compiled = $viewFactory->make($clear_name, $view_data)->render();

        echo $compiled;

        return null;
    }

    public function include_style($path)
    {
        add_action('wp_enqueue_scripts', function () use ($path) {
            wp_enqueue_style(str_replace(['.css', '.js'], '', $path), mix($path), [], null, 'all');
        });
    }

    public function include_script($path)
    {
        add_action('wp_enqueue_scripts', function () use ($path) {
            wp_enqueue_script(str_replace(['.css', '.js'], '', $path), mix($path), [], null, true);
        });
    }
}


