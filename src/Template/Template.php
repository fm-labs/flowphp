<?php
namespace Flow\Template;

use Flow\Object\ConfigTrait;
use Flow\Object\StaticFactoryTrait;

class Template
{
    use ConfigTrait;
    use StaticFactoryTrait;

    protected $defaultConfig = [
        'baseDir' => 'templates/',
        'ext' => 'phtml'
    ];

    protected $vars = [];

    protected $template;

    public function __construct(array $config = [])
    {
        $this->config($config);
    }

    public function setTemplate($template)
    {
        //if (!is_string($template) && !is_null($template)) {
        //    throw new \InvalidArgumentException('Param $template expects NULL or STRING');
        //}
        $this->template = $template;
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

    public function get($key = null)
    {
        if ($key === null) {
            return $this->vars;
        }
        if (isset($this->vars[$key])) {
            return $this->vars[$key];
        }

        return null;
    }

    public function render()
    {
        if (!$this->template) {
            return 'No template selected';
        }

        //$templatePath = preg_replace('@\/@', DIRECTORY_SEPARATOR, $this->template);
        $templatePath = $this->config('baseDir') . $this->template . "." . $this->config('ext');
        if (!file_exists($templatePath)) {
            return "Template not found: $templatePath";
        }

        ob_start();
        include ($templatePath);
        $buffer = ob_get_clean();
        return $buffer;
    }

    public function __toString()
    {
        return (string) $this->render();
    }
}