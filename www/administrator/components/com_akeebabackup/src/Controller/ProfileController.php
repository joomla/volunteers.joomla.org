<?php
/**
 * @package   akeebabackup
 * @copyright Copyright 2006-2026 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Component\AkeebaBackup\Administrator\Controller;

defined('_JEXEC') || die;

use Akeeba\Component\AkeebaBackup\Administrator\Mixin\ControllerCustomACLTrait;
use Akeeba\Component\AkeebaBackup\Administrator\Mixin\ControllerEventsTrait;
use Akeeba\Component\AkeebaBackup\Administrator\Model\ProfileModel;
use Akeeba\Engine\Factory;
use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\Document\JsonDocument;
use Joomla\CMS\Form\FormFactoryInterface;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\FormController;
use Joomla\CMS\MVC\Factory\MVCFactoryInterface;
use Joomla\Input\Input;
use RuntimeException;

class ProfileController extends FormController
{
	use ControllerEventsTrait;
	use ControllerCustomACLTrait;

	protected $text_prefix = 'COM_AKEEBABACKUP_PROFILE';

	public function __construct($config = [], ?MVCFactoryInterface $factory = null, ?CMSApplication $app = null, ?Input $input = null, ?FormFactoryInterface $formFactory = null)
	{
		parent::__construct($config, $factory, $app, $input, $formFactory);

		$this->registerTask('export', 'export');
	}

	public function export($cachable = false, $urlparams = [])
	{
		$this->checkToken('request');

		if (!$this->app->getIdentity()->authorise('akeebabackup.configure', 'com_akeebabackup'))
		{
			throw new RuntimeException(Text::_('JERROR_ALERTNOAUTHOR'), 403);
		}

		/** @var ProfileModel $model */
		$model = $this->getModel('Profile', 'Administrator');
		$id    = $this->input->getInt('id');

		if (empty($id))
		{
			throw new RuntimeException(Text::_('JERROR_ALERTNOAUTHOR'), 403);
		}

		$item = $model->getItem($id);

		if ($item === false)
		{
			throw new RuntimeException(Text::_('JERROR_ALERTNOAUTHOR'), 403);
		}

		if (substr($item->configuration, 0, 12) == '###AES128###')
		{
			// Load the server key file if necessary
			if (!defined('AKEEBA_SERVERKEY'))
			{
				$filename = JPATH_ADMINISTRATOR . '/components/com_akeebabackup/serverkey.php';

				include_once $filename;
			}

			$key = Factory::getSecureSettings()->getKey();

			$item->configuration = Factory::getSecureSettings()->decryptSettings($item->configuration, $key);
		}

		$this->triggerEvent('onBeforeExport', [$id]);

		$data = [
			'description'   => $item->description,
			'configuration' => $item->configuration,
			'filters'       => $item->filters,
			'quickicon'     => $item->quickicon,
		];

		$defaultName = $this->input->get('view', 'joomla', 'cmd');
		$filename    = $this->input->get('basename', $defaultName, 'cmd');

		/** @var JsonDocument $document */
		$document = $this->app->getDocument();
		$document->setName($filename);
		$document->setMimeEncoding('application/json');
		$this->app->setHeader('Content-Disposition', 'attachment; filename="profile.json"');

		echo json_encode($data, JSON_PRETTY_PRINT);
	}
}