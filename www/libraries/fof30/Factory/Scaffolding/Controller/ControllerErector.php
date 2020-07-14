<?php
/**
 * @package   FOF
 * @copyright Copyright (c)2010-2020 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 2, or later
 */

namespace FOF30\Factory\Scaffolding\Controller;

use FOF30\Controller\DataController;

defined('_JEXEC') or die;

/**
 * @package FOF30\Factory\Scaffolding
 *
 * @deprecated 3.1  Support for XML forms will be removed in FOF 4
 */
class ControllerErector implements ErectorInterface
{
    /**
     * The Builder which called us
     *
     * @var \FOF30\Factory\Scaffolding\Controller\Builder
     */
    protected $builder = null;

    /**
     * The Controller attached to the view we're building
     *
     * @var \FOF30\Controller\DataController
     */
    protected $controller = null;

    /**
     * The name of our view
     *
     * @var string
     */
    protected $viewName = null;

    /**
     * Section used to build the namespace prefix. We have to pass it since in CLI scaffolding we need
     * to force the section we're in (ie Site or Admin). {@see \FOF30\Container\Container::getNamespacePrefix() } for valid values
     *
     * @var   string
     */
    protected $section = 'auto';

    public function __construct(Builder $parent, DataController $controller, $viewName)
    {
        $this->builder      = $parent;
        $this->controller   = $controller;
        $this->viewName     = $viewName;
    }

	public function build()
	{
        $container = $this->builder->getContainer();
        $fullPath  = $container->getNamespacePrefix($this->getSection()) . 'Controller\\' . ucfirst($container->inflector->singularize($this->viewName));

        // Let's remove the last part and use it to create the class name
        $parts     = explode('\\', trim($fullPath, '\\'));
        $className = array_pop($parts);
        // Now glue everything together
        $namespace = implode('\\', $parts);
        // Let's be sure that the parent class extends with a backslash
        $baseClass = '\\'.trim(get_class($this->controller), '\\');

        $code  = '<?php'.PHP_EOL;
        $code .= PHP_EOL;
        $code .= 'namespace '.$namespace.';'.PHP_EOL;
        $code .= PHP_EOL;
        $code .= "defined('_JEXEC') or die;".PHP_EOL;
        $code .= PHP_EOL;
        $code .= 'class '.$className.' extends '.$baseClass.PHP_EOL;
        $code .= '{'.PHP_EOL;
        $code .= PHP_EOL;
        $code .= '}'.PHP_EOL;

        $path = $container->backEndPath;

        if(in_array('Site', $parts))
        {
            $path = $container->frontEndPath;
        }

        $path .= '/Controller/'.$className.'.php';

        $filesystem = $container->filesystem;

        $filesystem->fileWrite($path, $code);

        return $path;
	}

    /**
     * @return string
     */
    public function getSection()
    {
        return $this->section;
    }

    /**
     * @param string $section
     */
    public function setSection($section)
    {
        $this->section = $section;
    }
}
