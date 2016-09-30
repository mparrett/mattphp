<?php

namespace MP\Framework;

/**
 * Simple Autoloader for MattPHP
 * @author Matt Parrett
 */
class AutoLoader
{
    private $includePath;

    public function __construct($includePath = null)
    {
        $this->includePath = $includePath;
    }

    /**
     * SPL Autoload handler
     */
    public function loadOptionalClass($className)
    {
        $fileName = '';
        $namespace = '';
        error_log($className);
        if (false !== ($lastNsPos = strripos($className, '\\'))) {
            $namespace = substr($className, 0, $lastNsPos);
            $className = substr($className, $lastNsPos + 1);
            $fileName = str_replace('\\', DIRECTORY_SEPARATOR, $namespace) . DIRECTORY_SEPARATOR;
        }

        $fileName .= str_replace('_', DIRECTORY_SEPARATOR, $className) . '.php';

        $path = ($this->includePath !== null ? $this->includePath . DIRECTORY_SEPARATOR : '') . $fileName;

        if (file_exists($path)) {
            include $path;
        }
    }

    public function register()
    {
        spl_autoload_register(array($this, 'loadOptionalClass'));
    }
}
