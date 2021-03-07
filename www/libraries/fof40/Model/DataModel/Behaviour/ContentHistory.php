<?php
/**
 * @package   FOF
 * @copyright Copyright (c)2010-2021 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 2, or later
 */

namespace FOF40\Model\DataModel\Behaviour;

defined('_JEXEC') || die;

use ContenthistoryHelper;
use FOF40\Event\Observer;
use FOF40\Model\DataModel;

/**
 * FOF model behavior class to add Joomla! content history support
 *
 * @since    2.1
 */
class ContentHistory extends Observer
{
	/** @var  ContentHistoryHelper */
	protected $historyHelper;

	/**
	 * The event which runs after storing (saving) data to the database
	 *
	 * @param   DataModel  &$model  The model which calls this event
	 *
	 * @return  boolean  True to allow saving without an error
	 */
	public function onAfterSave(DataModel &$model)
	{
		$model->checkContentType();

		$componentParams = $model->getContainer()->params;

		if ($componentParams->get('save_history', 0))
		{
			if (!$this->historyHelper)
			{
				$this->historyHelper = new ContentHistoryHelper($model->getContentType());
			}

			$this->historyHelper->store($model);
		}

		return true;
	}

	/**
	 * The event which runs before deleting a record
	 *
	 * @param   DataModel &$model  The model which calls this event
	 * @param   integer    $oid    The PK value of the record to delete
	 *
	 * @return  boolean  True to allow the deletion
	 */
	public function onBeforeDelete(DataModel &$model, $oid)
	{
		$componentParams = $model->getContainer()->params;

		if ($componentParams->get('save_history', 0))
		{
			if (!$this->historyHelper)
			{
				$this->historyHelper = new ContentHistoryHelper($model->getContentType());
			}

			$this->historyHelper->deleteHistory($model);
		}

		return true;
	}

	/**
	 * This event runs after publishing a record in a model
	 *
	 * @param   DataModel  &$model  The model which calls this event
	 *
	 * @return  void
	 */
	public function onAfterPublish(DataModel &$model)
	{
		$model->updateUcmContent();
	}

	/**
	 * This event runs after unpublishing a record in a model
	 *
	 * @param   DataModel  &$model  The model which calls this event
	 *
	 * @return  void
	 */
	public function onAfterUnpublish(DataModel &$model)
	{
		$model->updateUcmContent();
	}
}
