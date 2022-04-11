<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  com_templates
 *
 * @copyright   (C) 2009 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

/**
 * Template styles list controller class.
 *
 * @since  1.6
 */
class TemplatesControllerStyles extends JControllerAdmin
{
	/**
	 * Method to clone and existing template style.
	 *
	 * @return  void
	 */
	public function duplicate()
	{
		// Check for request forgeries
		$this->checkToken();

		$pks = (array) $this->input->post->get('cid', array(), 'int');

		// Remove zero values resulting from input filter
		$pks = array_filter($pks);

		try
		{
			if (empty($pks))
			{
				throw new Exception(JText::_('COM_TEMPLATES_NO_TEMPLATE_SELECTED'));
			}

			$model = $this->getModel();
			$model->duplicate($pks);
			$this->setMessage(JText::_('COM_TEMPLATES_SUCCESS_DUPLICATED'));
		}
		catch (Exception $e)
		{
			JError::raiseWarning(500, $e->getMessage());
		}

		$this->setRedirect('index.php?option=com_templates&view=styles');
	}

	/**
	 * Proxy for getModel.
	 *
	 * @param   string  $name    The model name. Optional.
	 * @param   string  $prefix  The class prefix. Optional.
	 * @param   array   $config  Configuration array for model. Optional.
	 *
	 * @return  JModelLegacy
	 *
	 * @since   1.6
	 */
	public function getModel($name = 'Style', $prefix = 'TemplatesModel', $config = array())
	{
		return parent::getModel($name, $prefix, array('ignore_request' => true));
	}

	/**
	 * Method to set the home template for a client.
	 *
	 * @return  void
	 *
	 * @since   1.6
	 */
	public function setDefault()
	{
		// Check for request forgeries
		$this->checkToken();

		$pks = (array) $this->input->post->get('cid', array(), 'int');

		// Remove zero values resulting from input filter
		$pks = array_filter($pks);

		try
		{
			if (empty($pks))
			{
				throw new Exception(JText::_('COM_TEMPLATES_NO_TEMPLATE_SELECTED'));
			}

			// Pop off the first element.
			$id = array_shift($pks);
			$model = $this->getModel();
			$model->setHome($id);
			$this->setMessage(JText::_('COM_TEMPLATES_SUCCESS_HOME_SET'));
		}
		catch (Exception $e)
		{
			JError::raiseWarning(500, $e->getMessage());
		}

		$this->setRedirect('index.php?option=com_templates&view=styles');
	}

	/**
	 * Method to unset the default template for a client and for a language
	 *
	 * @return  void
	 *
	 * @since   1.6
	 */
	public function unsetDefault()
	{
		// Check for request forgeries
		$this->checkToken('request');

		$pks = (array) $this->input->get->get('cid', array(), 'int');

		// Remove zero values resulting from input filter
		$pks = array_filter($pks);

		try
		{
			if (empty($pks))
			{
				throw new Exception(JText::_('COM_TEMPLATES_NO_TEMPLATE_SELECTED'));
			}

			// Pop off the first element.
			$id = array_shift($pks);
			$model = $this->getModel();
			$model->unsetHome($id);
			$this->setMessage(JText::_('COM_TEMPLATES_SUCCESS_HOME_UNSET'));
		}
		catch (Exception $e)
		{
			JError::raiseWarning(500, $e->getMessage());
		}

		$this->setRedirect('index.php?option=com_templates&view=styles');
	}
}
