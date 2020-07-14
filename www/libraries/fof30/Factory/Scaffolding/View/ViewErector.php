<?php
/**
 * @package   FOF
 * @copyright Copyright (c)2010-2020 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 2, or later
 */

namespace FOF30\Factory\Scaffolding\View;

use FOF30\View\DataView\Html;

defined('_JEXEC') or die;

/**
 * @package FOF30\Factory\Scaffolding
 *
 * @deprecated 3.1  Support for XML forms will be removed in FOF 4
 */
class ViewErector implements ErectorInterface
{
    /**
     * The Builder which called us
     *
     * @var \FOF30\Factory\Scaffolding\View\Builder
     */
    protected $builder = null;

    /**
     * The Controller attached to the view we're building
     *
     * @var \FOF30\View\DataView\Html
     */
    protected $view = null;

    /**
     * The name of our view
     *
     * @var string
     */
    protected $viewName = null;

    /**
     * The type of our view
     *
     * @var string
     */
    protected $viewType = null;

    /**
     * Section used to build the namespace prefix. We have to pass it since in CLI scaffolding we need
     * to force the section we're in (ie Site or Admin). {@see \FOF30\Container\Container::getNamespacePrefix() } for valid values
     *
     * @var   string
     */
    protected $section = 'auto';

    public function __construct(Builder $parent, Html $view, $viewName, $viewType)
    {
        $this->builder  = $parent;
        $this->view     = $view;
        $this->viewName = $viewName;
        $this->viewType = $viewType;
    }

	public function build()
	{
        $container = $this->builder->getContainer();
        $view      = ucfirst($container->inflector->pluralize($this->viewName));
        $fullPath  = $container->getNamespacePrefix($this->getSection()) . 'View\\' . $view.'\\'.ucfirst($this->viewType);

        // Let's remove the last part and use it to create the class name
        $parts     = explode('\\', trim($fullPath, '\\'));
        $className = array_pop($parts);
        // Now glue everything together
        $namespace = implode('\\', $parts);
        // Let's be sure that the parent class extends with a backslash
        $baseClass = '\\'.trim(get_class($this->view), '\\');

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

        $path .= '/View/'.$view.'/'.$className.'.php';

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
