<?php
/**
 * @package   FOF
 * @copyright Copyright (c)2010-2020 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 2, or later
 */

namespace FOF30\Factory;

use FOF30\Container\Container;
use FOF30\Controller\Controller;
use FOF30\Dispatcher\Dispatcher;
use FOF30\Factory\Exception\ControllerNotFound;
use FOF30\Factory\Exception\DispatcherNotFound;
use FOF30\Factory\Exception\FormLoadData;
use FOF30\Factory\Exception\FormLoadFile;
use FOF30\Factory\Exception\FormNotFound;
use FOF30\Factory\Exception\ModelNotFound;
use FOF30\Factory\Exception\ToolbarNotFound;
use FOF30\Factory\Exception\TransparentAuthenticationNotFound;
use FOF30\Factory\Exception\ViewNotFound;
use FOF30\Factory\Scaffolding\Controller\Builder as ControllerBuilder;
use FOF30\Factory\Scaffolding\Layout\Builder as LayoutBuilder;
use FOF30\Factory\Scaffolding\Model\Builder as ModelBuilder;
use FOF30\Factory\Scaffolding\View\Builder as ViewBuilder;
use FOF30\Form\Form;
use FOF30\Model\Model;
use FOF30\Toolbar\Toolbar;
use FOF30\TransparentAuthentication\TransparentAuthentication;
use FOF30\View\View;
use FOF30\View\ViewTemplateFinder;

defined('_JEXEC') or die;

/**
 * MVC object factory. This implements the basic functionality, i.e. creating MVC objects only if the classes exist in
 * the same component section (front-end, back-end) you are currently running in. The Dispatcher and Toolbar will be
 * created from default objects if specialised classes are not found in your application.
 */
class BasicFactory implements FactoryInterface
{
	/** @var  Container  The container we belong to */
	protected $container = null;

	/** @var  bool  Should I look for form files on the other side of the component? */
	protected $formLookupInOtherSide = false;

	/** @var  bool  Should I enable view scaffolding, i.e. automatic browse, read and add/edit XML form generation when there's no other view template? */
	protected $scaffolding = false;

	/** @var  bool  When enabled, FOF will commit the scaffolding results to disk. */
	protected $saveScaffolding = false;

    /** @var  bool  When enabled, FOF will commit controller scaffolding results to disk. */
    protected $saveControllerScaffolding = false;

    /** @var  bool  When enabled, FOF will commit model scaffolding results to disk. */
    protected $saveModelScaffolding = false;

    /** @var  bool  When enabled, FOF will commit view scaffolding results to disk. */
    protected $saveViewScaffolding = false;

    /**
     * Section used to build the namespace prefix. We have to pass it since in CLI scaffolding we need
     * to force the section we're in (ie Site or Admin). {@see \FOF30\Container\Container::getNamespacePrefix() } for valid values
     *
     * @var   string
     */
    protected $section = 'auto';

	/**
	 * Public constructor for the factory object
	 *
	 * @param  \FOF30\Container\Container $container  The container we belong to
	 */
	public function __construct(Container $container)
	{
		$this->container = $container;
	}

	/**
	 * Create a new Controller object
	 *
	 * @param   string  $viewName  The name of the view we're getting a Controller for.
	 * @param   array   $config    Optional MVC configuration values for the Controller object.
	 *
	 * @return  Controller
	 */
	public function controller($viewName, array $config = array())
	{
		$controllerClass = $this->container->getNamespacePrefix($this->getSection()) . 'Controller\\' . ucfirst($viewName);

		try
		{
			return $this->createController($controllerClass, $config);
		}
		catch (ControllerNotFound $e)
		{
		}

        $controllerClass = $this->container->getNamespacePrefix($this->getSection()) . 'Controller\\' . ucfirst($this->container->inflector->singularize($viewName));

        try
        {
            $controller = $this->createController($controllerClass, $config);
        }
        catch(ControllerNotFound $e)
        {
            // Do I have to create and save the class file? If not, let's rethrow the exception
            if(!$this->saveControllerScaffolding)
            {
                throw $e;
            }

            $scaffolding = new ControllerBuilder($this->container);

            // Was the scaffolding successful? If so let's call ourself again, otherwise throw a not found exception
            if($scaffolding->make($controllerClass, $viewName))
            {
                $controller = $this->controller($viewName, $config);
            }
            else
            {
                throw $e;
            }
        }

		return $controller;
	}

	/**
	 * Create a new Model object
	 *
	 * @param   string  $viewName  The name of the view we're getting a Model for.
	 * @param   array   $config    Optional MVC configuration values for the Model object.
	 *
	 * @return  Model
	 */
	public function model($viewName, array $config = array())
	{
		$modelClass = $this->container->getNamespacePrefix($this->getSection()) . 'Model\\' . ucfirst($viewName);

		try
		{
			return $this->createModel($modelClass, $config);
		}
		catch (ModelNotFound $e)
		{
		}

		$modelClass = $this->container->getNamespacePrefix($this->getSection()) . 'Model\\' . ucfirst($this->container->inflector->singularize($viewName));

        try
        {
            $model = $this->createModel($modelClass, $config);
        }
        catch(ModelNotFound $e)
        {
            // Do I have to create and save the class file? If not, let's rethrow the exception
            if(!$this->saveModelScaffolding)
            {
                throw $e;
            }

            // By default model classes are plural
            $modelClass  = $this->container->getNamespacePrefix($this->getSection()) . 'Model\\' . ucfirst($viewName);
            $scaffolding = new ModelBuilder($this->container);

            // Was the scaffolding successful? If so let's call ourself again, otherwise throw a not found exception
            if($scaffolding->make($modelClass, $viewName))
            {
                $model = $this->model($viewName, $config);
            }
            else
            {
                throw $e;
            }
        }

        return $model;
	}

	/**
	 * Create a new View object
	 *
	 * @param   string  $viewName  The name of the view we're getting a View object for.
	 * @param   string  $viewType  The type of the View object. By default it's "html".
	 * @param   array   $config    Optional MVC configuration values for the View object.
	 *
	 * @return  View
	 */
	public function view($viewName, $viewType = 'html', array $config = array())
	{
        $container = $this->container;
        $prefix    = $this->container->getNamespacePrefix($this->getSection());

		$viewClass = $prefix . 'View\\' . ucfirst($viewName) . '\\' . ucfirst($viewType);

		try
		{
			return $this->createView($viewClass, $config);
		}
		catch (ViewNotFound $e)
		{
		}

		$viewClass = $prefix . 'View\\' . ucfirst($container->inflector->singularize($viewName)) . '\\' . ucfirst($viewType);

        try
        {
            $view = $this->createView($viewClass, $config);
        }
        catch(ViewNotFound $e)
        {
            // Do I have to create and save the class file? If not, let's rethrow the exception. Note: I can only create HTML views
            if(!$this->saveViewScaffolding)
            {
                throw $e;
            }

            // By default view classes are plural
            $viewClass = $prefix . 'View\\' . ucfirst($container->inflector->pluralize($viewName)) . '\\' . ucfirst($viewType);
            $scaffolding = new ViewBuilder($this->container);

            // Was the scaffolding successful? If so let's call ourself again, otherwise throw a not found exception
            if($scaffolding->make($viewClass, $viewName, $viewType))
            {
                $view = $this->view($viewName, $viewType, $config);
            }
            else
            {
                throw $e;
            }
        }

        return $view;
	}

	/**
	 * Creates a new Dispatcher
	 *
	 * @param   array  $config  The configuration values for the Dispatcher object
	 *
	 * @return  Dispatcher
	 */
	public function dispatcher(array $config = array())
	{
		$dispatcherClass = $this->container->getNamespacePrefix($this->getSection()) . 'Dispatcher\\Dispatcher';

		try
		{
			return $this->createDispatcher($dispatcherClass, $config);
		}
		catch (DispatcherNotFound $e)
		{
			// Not found. Return the default Dispatcher
			return new Dispatcher($this->container, $config);
		}
	}

	/**
	 * Creates a new Toolbar
	 *
	 * @param   array  $config  The configuration values for the Toolbar object
	 *
	 * @return  Toolbar
	 */
    public function toolbar(array $config = array())
	{
		$toolbarClass = $this->container->getNamespacePrefix($this->getSection()) . 'Toolbar\\Toolbar';

		try
		{
			return $this->createToolbar($toolbarClass, $config);
		}
		catch (ToolbarNotFound $e)
		{
			// Not found. Return the default Toolbar
			return new Toolbar($this->container, $config);
		}
	}

	/**
	 * Creates a new TransparentAuthentication handler
	 *
	 * @param   array $config The configuration values for the TransparentAuthentication object
	 *
	 * @return  TransparentAuthentication
	 */
    public function transparentAuthentication(array $config = array())
	{
		$authClass = $this->container->getNamespacePrefix($this->getSection()) . 'TransparentAuthentication\\TransparentAuthentication';

		try
		{
			return $this->createTransparentAuthentication($authClass, $config);
		}
		catch (TransparentAuthenticationNotFound $e)
		{
			// Not found. Return the default TA
			return new TransparentAuthentication($this->container, $config);
		}
	}

	/**
	 * Creates a new Form object
	 *
	 * @param   string  $name      The name of the form.
	 * @param   string  $source    The form source filename without path and .xml extension e.g. "form.default" OR raw XML data
	 * @param   string  $viewName  The name of the view you're getting the form for.
	 * @param   array   $options   Options to the Form object
	 * @param   bool    $replace   Should form fields be replaced if a field already exists with the same group/name?
	 * @param   bool    $xpath     An optional xpath to search for the fields.
	 *
	 * @return  Form|null  The loaded form or null if the form filename doesn't exist
	 *
	 * @throws  \RuntimeException If the form exists but cannot be loaded
	 *
	 * @deprecated 3.1  Support for XML forms will be removed in FOF 4
	 */
    public function form($name, $source, $viewName, array $options = array(), $replace = true, $xpath = false)
	{
        $formClass = $this->container->getNamespacePrefix($this->getSection()) . 'Form\\Form';

        try
        {
            $form = $this->createForm($formClass, $name, $options);
        }
        catch (FormNotFound $e)
        {
            // Not found. Return the default Toolbar
            $form = new Form($this->container, $name, $options);
        }

		// If $source looks like raw XML data, parse it directly
		if (strpos($source, '<form') !== false)
		{
			if ($form->load($source, $replace, $xpath) === false)
			{
				throw new FormLoadData;
			}

			return $form;
		}

		$formFileName = $this->getFormFilename($source, $viewName);

		if (empty($formFileName))
		{
			if ($this->scaffolding)
			{
				$scaffolding = new LayoutBuilder($this->container);
				$xml = $scaffolding->make($source, $viewName);

				if (!is_null($xml))
				{
					return $this->form($name, $xml, $viewName, $options, $replace, $xpath);
				}
			}

			return null;
		}

		if ($form->loadFile($formFileName, $replace, $xpath) === false)
		{
			throw new FormLoadFile($source);
		}

		return $form;
	}

	/**
	 * Creates a view template finder object for a specific View
	 *
	 * The default configuration is:
	 * Look for .php, .blade.php files; default layout "default"; no default subtemplate;
	 * look only for the specified view; do NOT fall back to the default layout or subtemplate;
	 * look for templates ONLY in site or admin, depending on where we're running from
	 *
	 * @param   View  $view   The view this view template finder will be attached to
	 * @param   array $config Configuration variables for the object
	 *
	 * @return  ViewTemplateFinder
	 *
     * @throws \Exception
	 */
    public function viewFinder(View $view, array $config = array())
	{
		// Initialise the configuration with the default values
		$defaultConfig = array(
			'extensions'    => array('.php', '.blade.php'),
			'defaultLayout' => 'default',
			'defaultTpl'    => '',
			'strictView'    => true,
			'strictTpl'     => true,
			'strictLayout'  => true,
			'sidePrefix'    => 'auto'
		);

		$config = array_merge($defaultConfig, $config);

		// Apply fof.xml overrides
		$appConfig = $this->container->appConfig;
		$key = "views." . ucfirst($view->getName()) . ".config";

		$fofXmlConfig = array(
			'extensions'    => $appConfig->get("$key.templateExtensions", $config['extensions']),
			'strictView'    => $appConfig->get("$key.templateStrictView", $config['strictView']),
			'strictTpl'     => $appConfig->get("$key.templateStrictTpl", $config['strictTpl']),
			'strictLayout'  => $appConfig->get("$key.templateStrictLayout", $config['strictLayout']),
			'sidePrefix'    => $appConfig->get("$key.templateLocation", $config['sidePrefix'])
		);

		$config = array_merge($config, $fofXmlConfig);

		// Create the new view template finder object
		return new ViewTemplateFinder($view, $config);
	}

	/**
	 * Is scaffolding enabled?
	 *
	 * @return boolean
	 */
	public function isScaffolding()
	{
		return $this->scaffolding;
	}

	/**
	 * Set the scaffolding status
	 *
	 * @param boolean $scaffolding
	 */
	public function setScaffolding($scaffolding)
	{
		$this->scaffolding = (bool) $scaffolding;
	}

	/**
	 * Is saving the scaffolding result to disk enabled?
	 *
	 * @return boolean
	 */
	public function isSaveScaffolding()
	{
		return $this->saveScaffolding;
	}

	/**
	 * Set the status of saving the scaffolding result to disk.
	 *
	 * @param boolean $saveScaffolding
	 */
	public function setSaveScaffolding($saveScaffolding)
	{
		$this->saveScaffolding = (bool) $saveScaffolding;
	}

    /**
     * Should we save controller to disk?
     *
     * @param   boolean $state
     */
    public function setSaveControllerScaffolding($state)
    {
        $this->saveControllerScaffolding = (bool) $state;
    }

    /**
     * Should we save controller scaffolding to disk?
     *
     * @return  boolean $state
     */
    public function isSaveControllerScaffolding()
    {
        return $this->saveControllerScaffolding;
    }

    /**
     * Should we save model to disk?
     *
     * @param   boolean $state
     */
    public function setSaveModelScaffolding($state)
    {
        $this->saveModelScaffolding = (bool) $state;
    }

    /**
     * Should we save model scaffolding to disk?
     *
     * @return  boolean $state
     */
    public function isSaveModelScaffolding()
    {
        return $this->saveModelScaffolding;
    }

    /**
     * Should we save view to disk?
     *
     * @param   boolean $state
     */
    public function setSaveViewScaffolding($state)
    {
        $this->saveViewScaffolding = (bool) $state;
    }

    /**
     * Should we save view scaffolding to disk?
     *
     * @return  boolean $state
     */
    public function isSaveViewScaffolding()
    {
        return $this->saveViewScaffolding;
    }

	/**
	 * Creates a Controller object
	 *
	 * @param   string  $controllerClass  The fully qualified class name for the Controller
	 * @param   array   $config           Optional MVC configuration values for the Controller object.
	 *
	 * @return  Controller
	 *
	 * @throws  \RuntimeException  If the $controllerClass does not exist
	 */
	protected function createController($controllerClass, array $config = array())
	{
		if (!class_exists($controllerClass))
		{
			throw new ControllerNotFound($controllerClass);
		}

		return new $controllerClass($this->container, $config);
	}

	/**
	 * Creates a Model object
	 *
	 * @param   string  $modelClass  The fully qualified class name for the Model
	 * @param   array   $config      Optional MVC configuration values for the Model object.
	 *
	 * @return  Model
	 *
	 * @throws  \RuntimeException  If the $modelClass does not exist
	 */
	protected function createModel($modelClass, array $config = array())
	{
		if (!class_exists($modelClass))
		{
			throw new ModelNotFound($modelClass);
		}

		return new $modelClass($this->container, $config);
	}

	/**
	 * Creates a View object
	 *
	 * @param   string  $viewClass  The fully qualified class name for the View
	 * @param   array   $config     Optional MVC configuration values for the View object.
	 *
	 * @return  View
	 *
	 * @throws  \RuntimeException  If the $viewClass does not exist
	 */
	protected function createView($viewClass, array $config = array())
	{
		if (!class_exists($viewClass))
		{
			throw new ViewNotFound($viewClass);
		}

		return new $viewClass($this->container, $config);
	}

	/**
	 * Creates a Toolbar object
	 *
	 * @param   string  $toolbarClass  The fully qualified class name for the Toolbar
	 * @param   array   $config        The configuration values for the Toolbar object
	 *
	 * @return  Toolbar
	 *
	 * @throws  \RuntimeException  If the $toolbarClass does not exist
	 */
	protected function createToolbar($toolbarClass, array $config = array())
	{
		if (!class_exists($toolbarClass))
		{
			throw new ToolbarNotFound($toolbarClass);
		}

		return new $toolbarClass($this->container, $config);
	}

    /**
     * Creates a Form object
     *
     * @param   string  $formClass     The fully qualified class name for the Form
     * @param   string  $name          The name of the form
     * @param   array   $options       The options values for the Form object
     *
     * @return  Toolbar
     *
     * @throws  FormNotFound  	If the $formClass does not exist
     */
    protected function createForm($formClass, $name, array $options = array())
    {
        if (!class_exists($formClass))
        {
            throw new FormNotFound($formClass);
        }

        return new $formClass($this->container, $name, $options);
    }

	/**
	 * Creates a Dispatcher object
	 *
	 * @param   string  $dispatcherClass  The fully qualified class name for the Dispatcher
	 * @param   array   $config            The configuration values for the Dispatcher object
	 *
	 * @return  Dispatcher
	 *
	 * @throws  \RuntimeException  If the $dispatcherClass does not exist
	 */
	protected function createDispatcher($dispatcherClass, array $config = array())
	{
		if (!class_exists($dispatcherClass))
		{
			throw new DispatcherNotFound($dispatcherClass);
		}

		return new $dispatcherClass($this->container, $config);
	}

	/**
	 * Creates a TransparentAuthentication object
	 *
	 * @param   string  $authClass  The fully qualified class name for the TransparentAuthentication
	 * @param   array   $config     The configuration values for the TransparentAuthentication object
	 *
	 * @return  TransparentAuthentication
	 *
	 * @throws  \RuntimeException  If the $authClass does not exist
	 */
	protected function createTransparentAuthentication($authClass, $config)
	{
		if (!class_exists($authClass))
		{
			throw new TransparentAuthenticationNotFound($authClass);
		}

		return new $authClass($this->container, $config);
	}

	/**
	 * Tries to find the absolute file path for an abstract form filename. For example, it may convert form.default to
	 * /home/myuser/mysite/components/com_foobar/View/tmpl/form.default.xml.
	 *
	 * @param   string  $source    The abstract form filename
	 * @param   string  $viewName  The name of the view we're getting the path for
	 *
	 * @return  string|bool  The fill path to the form XML file or boolean false if it's not found
	 */
	protected function getFormFilename($source, $viewName = null)
	{
		if (empty($source))
		{
			return false;
		}

		$componentName = $this->container->componentName;

		if (empty($viewName))
		{
			$viewName = $this->container->dispatcher->getController()->getView()->getName();
		}

		$viewNameAlt = $this->container->inflector->singularize($viewName);

		if ($viewNameAlt == $viewName)
		{
			$viewNameAlt = $this->container->inflector->pluralize($viewName);
		}

		$componentPaths = $this->container->platform->getComponentBaseDirs($componentName);

		$file_root      = $componentPaths['main'];
		$alt_file_root  = $componentPaths['alt'];
		$template_root  = $this->container->platform->getTemplateOverridePath($componentName);

		// Basic paths we need to always search
		$paths = array(
			// Template override
			$template_root . '/' . $viewName,
			$template_root . '/' . $viewNameAlt,
            // Forms inside the specialized folder for easier template overrides
            $file_root . '/ViewTemplates/' . $viewName,
            $file_root . '/ViewTemplates/' . $viewNameAlt,
			// This side of the component
			$file_root . '/View/' . $viewName . '/tmpl',
			$file_root . '/View/' . $viewNameAlt . '/tmpl',
		);

		// The other side of the component
		if ($this->formLookupInOtherSide)
		{
            // Forms inside the specialized folder for easier template overrides
            $paths[] = $alt_file_root . '/ViewTemplates/' . $viewName;
            $paths[] = $alt_file_root . '/ViewTemplates/' . $viewNameAlt;

			$paths[] = $alt_file_root . '/View/' . $viewName . '/tmpl';
			$paths[] = $alt_file_root . '/View/' . $viewNameAlt . '/tmpl';
		}

		// Legacy paths, this side of the component
		$paths[] = $file_root . '/views/' . $viewName . '/tmpl';
		$paths[] = $file_root . '/views/' . $viewNameAlt . '/tmpl';
		$paths[] = $file_root . '/Model/forms';
		$paths[] = $file_root . '/models/forms';

		// Legacy paths, the other side of the component
		if ($this->formLookupInOtherSide)
		{
			$paths[] = $file_root . '/views/' . $viewName . '/tmpl';
			$paths[] = $file_root . '/views/' . $viewNameAlt . '/tmpl';
			$paths[] = $file_root . '/Model/forms';
			$paths[] = $file_root . '/models/forms';
		}

		$paths = array_unique($paths);

		// Set up the suffixes to look into
		$suffixes = array();
		$temp_suffixes = $this->container->platform->getTemplateSuffixes();

		if (!empty($temp_suffixes))
		{
			foreach ($temp_suffixes as $suffix)
			{
				$suffixes[] = $suffix . '.xml';
			}
		}

		$suffixes[] = '.xml';

		// Look for all suffixes in all paths
		$result     = false;
		$filesystem = $this->container->filesystem;

		foreach ($paths as $path)
		{
			foreach ($suffixes as $suffix)
			{
				$filename = $path . '/' . $source . $suffix;

				if ($filesystem->fileExists($filename))
				{
					$result = $filename;
					break;
				}
			}

			if ($result)
			{
				break;
			}
		}

		return $result;
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
