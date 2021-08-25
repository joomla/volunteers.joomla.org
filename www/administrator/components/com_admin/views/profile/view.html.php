<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  com_admin
 *
 * @copyright   (C) 2010 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

JLoader::register('UsersHelper', JPATH_ADMINISTRATOR . '/components/com_users/helpers/users.php');

/**
 * View class to allow users edit their own profile.
 *
 * @since  1.6
 */
class AdminViewProfile extends JViewLegacy
{
	/**
	 * The JForm object
	 *
	 * @var    JForm
	 * @since  1.6
	 */
	protected $form;

	/**
	 * The item being viewed
	 *
	 * @var    object
	 * @since  1.6
	 */
	protected $item;

	/**
	 * The model state
	 *
	 * @var    object
	 * @since  1.6
	 */
	protected $state;

	/**
	 * Execute and display a template script.
	 *
	 * @param   string  $tpl  The name of the template file to parse; automatically searches through the template paths.
	 *
	 * @return  mixed  A string if successful, otherwise an Error object.
	 *
	 * @since   1.6
	 */
	public function display($tpl = null)
	{
		$this->form             = $this->get('Form');
		$this->item             = $this->get('Item');
		$this->state            = $this->get('State');
		$this->twofactorform    = $this->get('Twofactorform');
		$this->twofactormethods = UsersHelper::getTwoFactorMethods();
		$this->otpConfig        = $this->get('OtpConfig');

		// Load the language strings for the 2FA
		JFactory::getLanguage()->load('com_users', JPATH_ADMINISTRATOR);

		// Check for errors.
		if (count($errors = $this->get('Errors')))
		{
			throw new Exception(implode("\n", $errors), 500);
		}

		$this->form->setValue('password',	null);
		$this->form->setValue('password2',	null);

		$this->addToolbar();

		return parent::display($tpl);
	}

	/**
	 * Add the page title and toolbar.
	 *
	 * @return  void
	 *
	 * @since   1.6
	 */
	protected function addToolbar()
	{
		JFactory::getApplication()->input->set('hidemainmenu', 1);

		JToolbarHelper::title(JText::_('COM_ADMIN_VIEW_PROFILE_TITLE'), 'user user-profile');
		JToolbarHelper::apply('profile.apply');
		JToolbarHelper::save('profile.save');
		JToolbarHelper::cancel('profile.cancel', 'JTOOLBAR_CLOSE');
		JToolbarHelper::divider();
		JToolbarHelper::help('JHELP_ADMIN_USER_PROFILE_EDIT');
	}
}
