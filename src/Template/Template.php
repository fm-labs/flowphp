<?php

namespace Flow\Template;

use Flow\Http\Message\Uri;
use Flow\Object\ConfigInterface;
use Flow\Object\ConfigTrait;
use Flow\Object\StaticFactoryTrait;
use FmLabs\Uri\UriFactory;

class Template implements ConfigInterface
{
    use ConfigTrait;
    use StaticFactoryTrait;

    protected $defaultConfig = [
        'baseDir' => 'templates/',
        'baseUrl' => '/',
        'ext' => 'phtml'
    ];

    /**
     * @var array Template vars
     */
    protected $vars = [];

    /**
     * @var string Template path relative to base dir
     */
    protected $template;

    /**
     * @var string Layout template path relative to base dir
     */
    protected $layout;

    /**
     * @var array Map of attached helpers
     */
    protected $helpers = [];

    /**
     * @var string Render result
     */
    protected $rendered;

    public function __construct(array $config = [])
    {
        $this->config($config);

        // Built-in helpers
        $this->addHelper('css', function ($uri = null, $options = []) {
            $uri = $this->getAssetUri($uri);
            return sprintf('<link rel="stylesheet" href="%s">', $uri);
        });
        $this->addHelper('script', function ($uri = null, $options = []) {
            $uri = $this->getAssetUri($uri);
            return sprintf('<script src="%s">', $uri);
        });
        $this->addHelper('link', function ($title = "", $uri = "#", $options = []) {
            //$uri = UriFactory::fromString($uri);
            return sprintf('<a href="%s">%s</a>', $uri, $title);
        });
        $this->addHelper('textBold', function ($str = "") {
            return sprintf('<span style="font-weight: bold;">%s</span>', $str);
        });
        $this->addHelper('element', function ($template = null, $data = []) {
            return (new Template($this->config))
                ->setTemplate('elements/' . $template)
                ->setLayout(false)
                ->set($this->vars)
                ->set($data);
        });
        $this->addHelper('nav', function () {
            $sections = $this->get('sections', []);
            $nav = [];
            $html = "";
            foreach ($sections as $sectionId => $section) {
                $html .= sprintf('<a class="p-2 text-dark" href="#%s">%s</a>', $sectionId, $section['title']);
            }
            return $html;
        });
    }

    public function __call($helperName, $args = [])
    {
        $helper = $this->helpers[$helperName] ?? null;
        if (!$helper) {
            throw new \RuntimeException("Template helper not loaded: $helperName");
        }

        return call_user_func_array($helper, $args);
    }

    public function addHelper($helperName, callable $helper)
    {
        $this->helpers[$helperName] = $helper;

        return $this;
    }

    public function setTemplate(string $template)
    {
        $this->template = $template;

        return $this;
    }

    public function setLayout(string $layout)
    {
        $this->layout = $layout;

        return $this;
    }

    public function set($key, $val = null)
    {
        if (is_array($key)) {
            foreach ($key as $_k => $_v) {
                $this->set($_k, $_v);
            }
            return $this;
        }

        $this->vars[$key] = $val;
        return $this;
    }

    public function get($key = null, $default = null)
    {
        if ($key === null) {
            return $this->vars;
        }
        if (isset($this->vars[$key])) {
            return $this->vars[$key];
        }

        return $default;
    }

    protected function renderLayout()
    {
        $layout = (new Template($this->config))
            ->setTemplate($this->layout)
            ->setLayout("")
            ->set($this->vars);

        return $layout->render();
    }

    protected function getTemplatePath($template)
    {
        return $this->config('baseDir') . $template . "." . $this->config('ext');
    }

    protected function getAssetPath($path)
    {
        if (preg_match('/^http/', $path) || preg_match('/\/\//', $path)) {
            return $path;
        }
        return dirname($this->config('baseDir')) . '/assets/' . $path;
    }

    protected function getAssetUri($path)
    {
        if (preg_match('/^http/', $path) || preg_match('/\/\//', $path)) {
            return $path;
        }
        return $this->config('baseUrl') . 'assets/' . $path;
    }

    public function render()
    {
        if ($this->rendered === null) {
            if (!$this->template) {
                return 'No template selected';
            }

            //$templatePath = preg_replace('@\/@', DIRECTORY_SEPARATOR, $this->template);
            $templatePath = $this->getTemplatePath($this->template);
            if (!file_exists($templatePath)) {
                return "Template not found: $templatePath";
            }

            $renderer = function ($templatePath) {
                extract($this->vars);
                ob_start();
                include($templatePath);
                $buffer = ob_get_clean();
                return $buffer;
            };
            $renderer = \Closure::bind($renderer, $this, get_class($this));

            $content = $renderer($templatePath);
            if ($this->layout) {
                $this->set('content', $content);
                $content = $this->renderLayout();
            }
            $this->rendered = $content;
        }

        return $this->rendered;
    }

    public function __toString()
    {
        try {
            return (string) $this->render();
        } catch (\Exception $ex) {
            return "Template rendering failed: " . $ex->getMessage() . "[" . $ex->getFile() . ":" . $ex->getLine() . "]";
        }
    }
}
