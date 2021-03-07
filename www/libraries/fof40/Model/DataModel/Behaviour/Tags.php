<?php
/**
 * @package   FOF
 * @copyright Copyright (c)2010-2021 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 2, or later
 */

namespace FOF40\Model\DataModel\Behaviour;

defined('_JEXEC') || die;

use FOF40\Event\Observable;
use FOF40\Event\Observer;
use FOF40\Model\DataModel;
use Joomla\CMS\Helper\TagsHelper;

/**
 * FOF model behavior class to add Joomla! Tags support
 *
 * @since    2.1
 */
class Tags extends Observer
{
	/** @var TagsHelper */
	protected $tagsHelper;

	public function __construct(Observable &$subject)
	{
		parent::__construct($subject);

		$this->tagsHelper = new TagsHelper();
	}

	/**
	 * This event runs after unpublishing a record in a model
	 *
	 * @param   DataModel  &$model       The model which calls this event
	 * @param   \stdClass  &$dataObject  The data to bind to the form
	 *
	 * @return  void
	 */
	public function onBeforeCreate(DataModel &$model, &$dataObject)
	{
		$tagField = $model->getBehaviorParam('tagFieldName', 'tags');

		unset($dataObject->$tagField);
	}

	/**
	 * This event runs after unpublishing a record in a model
	 *
	 * @param   DataModel  &$model       The model which calls this event
	 * @param   \stdClass  &$dataObject  The data to bind to the form
	 *
	 * @return  void
	 */
	public function onBeforeUpdate(DataModel &$model, &$dataObject)
	{
		$tagField = $model->getBehaviorParam('tagFieldName', 'tags');

		unset($dataObject->$tagField);
	}

	/**
	 * The event which runs after binding data to the table
	 *
	 * @param   DataModel    &$model  The model which calls this event
	 *
	 * @return  void
	 *
	 * @throws  \Exception  Error message if failed to store tags
	 */
	public function onAfterSave(DataModel &$model)
	{
		$tagField = $model->getBehaviorParam('tagFieldName', 'tags');

		// Avoid to update on other method (e.g. publish, ...)
		if (!in_array($model->getContainer()->input->getCmd('task'), ['apply', 'save', 'savenew']))
		{
			return;
		}

		$oldTags = $this->tagsHelper->getTagIds($model->getId(), $model->getContentType());
		$newTags = $model->$tagField ? implode(',', $model->$tagField) : null;

		// If no changes, we stop here
		if ($oldTags == $newTags)
		{
			return;
		}

		// Check if the content type exists, and create it if it does not
		$model->checkContentType();

		$this->tagsHelper->typeAlias = $model->getContentType();

		if (!$this->tagsHelper->postStoreProcess($model, $model->$tagField))
		{
			throw new \Exception('Error storing tags');
		}
	}

	/**
	 * The event which runs after deleting a record
	 *
	 * @param   DataModel &$model  The model which calls this event
	 * @param   integer    $oid    The PK value of the record which was deleted
	 *
	 * @return  void
	 *
	 * @throws  \Exception  Error message if failed to detele tags
	 */
	public function onAfterDelete(DataModel &$model, $oid)
	{
		$this->tagsHelper->typeAlias = $model->getContentType();

		if (!$this->tagsHelper->deleteTagData($model, $oid))
		{
			throw new \Exception('Error deleting Tags');
		}
	}

	/**
	 * This event runs after unpublishing a record in a model
	 *
	 * @param   DataModel  &$model  The model which calls this event
	 * @param   mixed       $data   An associative array or object to bind to the DataModel instance.
	 *
	 * @return  void
	 * @noinspection PhpUnusedParameterInspection
	 */
	public function onAfterBind(DataModel &$model, &$data)
	{
		$tagField = $model->getBehaviorParam('tagFieldName', 'tags');

		if ($model->$tagField)
		{
			return;
		}

		$type = $model->getContentType();

		$model->addKnownField($tagField);
		$model->$tagField = $this->tagsHelper->getTagIds($model->getId(), $type);
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
