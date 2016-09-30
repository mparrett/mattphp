<?php

namespace MP\Framework;

/**
 * MattPHP
 * Simple CakePHP-style template rendering
 * @author Matt Parrett
 */
class Templates
{
    public $app;
    public $template_dir;
    public $parsedown;

    public function __construct($template_dir, $parsedown = null)
    {
        $this->template_dir = $template_dir;
        if ($parsedown !== null) {
            $this->parsedown = $parsedown;
        }
    }

    public function getTemplatePath($template)
    {
        return $this->template_dir . '/' . $template;
    }

    /**
     * Render a single template
     * Extracting $vars into the symbol table before
     * inclusion
     */
    public function renderSingle($template, $_vars)
    {
        if (!file_exists($template)) {
            throw new \Exception("Template not found: " . $template);
        }

        // Only prefix invalid/numeric variable names with prefix
        // TODO: Should we have EXTR_OVERWRITE here as well?
        extract($_vars, EXTR_PREFIX_INVALID, 'var');

        // Each inclusion should set this or not
        $_extends = null;

        // Include the template
        // Can set $_extends
        // vars available as $_vars
        ob_start();
        include $template;
        $_out = ob_get_clean(); // Capture output

        foreach (['_out', '_extends', '_title'] as $var) {
            if (isset($$var)) {
                $ret[$var] = $$var;
            }
        }
        return $ret; // Return contents and template to extend
    }

    /**
     * Render a (possible) chain of templates
     */
    public function render($template, &$_vars = array())
    {
        if (isset($_vars['_extends'])) {
            // TODO: All $_ variables?
            throw new \Exception("Don't clobber _extends please");
        }

        $t = array();

        $calls = 0; // Recursion counter
        $target = self::getTemplatePath($template); // Initial target

        // TODO: support .md.php?
        if (strpos($template, '.md') !== false && $this->parsedown !== null) {
            if (!file_exists($target)) {
                throw new \Exception("Template not found: " . $target);
            }

            return $this->parsedown->text(file_get_contents($target));
        }

        while (true) {
            if (++$calls > 10) {
                throw new \Exception("Recursion limit exceeded!");
            }

            // TODO: Could support named templates ($_name = 'something')
            // pretty easily
            $_ret = $this->renderSingle($target, $_vars);

            // Did not extend
            if (!isset($_ret['_extends'])) {
                return $_ret['_out'];
            }

            // Does extend, set new target
            $target = self::getTemplatePath($_ret['_extends']);

            // Make $_child available to parent templates
            // Gets overwritten each time as it should
            $_vars['_child'] = $_ret['_out'];

            if (isset($_ret['_title'])) {
                $_vars['_title'] = $_ret['_title'];
            }
        }

        throw new \Exception("Templates::render - SHOULD NOT GET HERE");
    }

    /**
     * Check if the template exists
     */
    public function exists($template)
    {
        return file_exists($this->getTemplatePath($template));
    }
    
    /**
     * Cache a template by writing it to a file
     */
    public function cache($template, $output)
    {
        file_put_contents($this->getTemplatePath($template), $output);
    }

    public static function getCacheKey($template)
    {
        $path_parts = pathinfo($template);
        $dir_parts = explode('/', substr($path_parts['dirname'], 1));

        if (count($dir_parts) == 0) {
            $key = $path_parts['filename'];
        } else {
            $key = array_pop($dir_parts).'/'.$path_parts['filename'];
        }

        return $key;
    }
}
