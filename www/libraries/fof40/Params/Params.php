<?php
/**
 * @package   FOF
 * @copyright Copyright (c)2010-2022 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace FOF40\Params;

defined('_JEXEC') || die;

use FOF40\Container\Container;
use FOF40\JoomlaAbstraction\CacheCleaner;

/**
 * A helper class to quickly get the component parameters
 */
class Params
{

	/** @var  Container  The container we belong to */
	protected $container;

	/**
	 * Cached component parameters
	 *
	 * @var \Joomla\Registry\Registry
	 */
	private $params;

	/**
	 * Public constructor for the params object
	 *
	 * @param   \FOF40\Container\Container  $container  The container we belong to
	 */
	public function __construct(Container $container)
	{
		$this->container = $container;

		$this->reload();
	}

	/**
	 * Reload the params
	 */
	public function reload(): void
	{
		$db = $this->container->db;

		$sql  = $db->getQuery(true)
			->select($db->qn('params'))
			->from($db->qn('#__extensions'))
			->where($db->qn('type') . " = " . $db->q('component'))
			->where($db->qn('element') . " = " . $db->q($this->container->componentName));
		$json = $db->setQuery($sql)->loadResult();

		$this->params = new \Joomla\Registry\Registry($json);
	}

	/**
	 * Returns the value of a component configuration parameter
	 *
	 * @param   string  $key      The parameter to get
	 * @param   mixed   $default  Default value
	 *
	 * @return  mixed
	 */
	public function get(string $key, $default = null)
	{
		return $this->params->get($key, $default);
	}

	/**
	 * Returns a copy of the loaded component parameters as an array
	 *
	 * @return  array
	 */
	public function getParams(): array
	{
		return $this->params->toArray();
	}

	/**
	 * Sets the value of multiple component configuration parameters at once
	 *
	 * @param   array  $params  The parameters to set
	 *
	 * @return  void
	 */
	public function setParams(array $params): void
	{
		foreach ($params as $key => $value)
		{
			$this->params->set($key, $value);
		}
	}

	/**
	 * Sets the value of a component configuration parameter
	 *
	 * @param   string  $key    The parameter to set
	 * @param   mixed   $value  The value to set
	 *
	 * @return  void
	 */
	public function set(string $key, $value)
	{
		$this->setParams([$key => $value]);
	}

	/**
	 * Actually Save the params into the db
	 */
	public function save(): void
	{
		$db   = $this->container->db;
		$data = $this->params->toString();

		$sql = $db->getQuery(true)
			->update($db->qn('#__extensions'))
			->set($db->qn('params') . ' = ' . $db->q($data))
			->where($db->qn('element') . ' = ' . $db->q($this->container->componentName))
			->where($db->qn('type') . ' = ' . $db->q('component'));

		$db->setQuery($sql);

		try
		{
			$db->execute();

			// The component parameters are cached. We just changed them. Therefore we MUST reset the system cache which holds them.
			CacheCleaner::clearCacheGroups(['_system'], $this->container->platform->isBackend() ? [0] : [1]);
		}
		catch (\Exception $e)
		{
			// Don't sweat if it fails
		}
	}
}
