<?php

/**
 * @package    Com_Volunteers
 * @version    4.0.0
 * @author     The Joomla Project <secretary@opensourcematters.org>
 * @copyright  2023 The Joomla Project
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Component\Volunteers\Site\Model;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

use Exception;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Form\Form;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Mail\MailerFactoryInterface;
use Joomla\CMS\MVC\Factory\MVCFactoryInterface;
use Joomla\CMS\MVC\Model\FormModel;
use Joomla\CMS\Uri\Uri;
use Joomla\Component\Volunteers\Administrator\Model\VolunteerModel;
use stdClass;

/**
 * Registration model.
 *
 * @since  4.0.0
 */
class RegistrationModel extends FormModel
{
    /**
     * @var    object  The user registration data.
     * @since 4.0.0
     */
    protected object $data;

    /**
     * @var \Joomla\CMS\Application\CMSApplicationInterface
     * @since  6.1.0
     */
    protected $app;

    /**
     * Method to get the registration form.
     *
     * The base form is loaded from XML and then an event is fired
     * for users plugins to extend the form with extra fields.
     *
     * @param   array    $data      An optional array of data for the form to interogate.
     * @param   boolean  $loadData  True if the form is to load its own data (default case), false if not.
     *
     * @return  mixed  A Form object on success, false on failure
     *
     * @since 4.0.0
     * @throws Exception
     */
    public function getForm($data = [], $loadData = true)
    {
        // Get the form.
        $form = $this->loadForm('com_volunteers.registration', 'registration', ['control' => 'jform', 'load_data' => $loadData]);

        if (empty($form)) {
            return false;
        }

        return $form;
    }

    /**
     * Method to get the data that should be injected in the form.
     *
     * @return  mixed  The data for the form.
     *
     * @since 4.0.0
     * @throws Exception
     */
    protected function loadFormData()
    {
        $data = $this->getData();

        $this->preprocessData('com_volunteers.registration', $data);

        return (array)$data;
    }

    /**
     * Method to get the registration form data.
     *
     * The base form data is loaded and then an event is fired
     * for users plugins to extend the data.
     *
     * @return  mixed  Data object on success, false on failure.
     *
     * @since 4.0.0
     * @throws Exception
     */
    public function getData()
    {
        if ($this->data === null) {
            $this->data = new stdClass();
            $params     = ComponentHelper::getParams('com_volunteers');

            // Override the base user data with any data in the session.
            $temp = (array) $this->app->getUserState('com_volunteers.registration.data', []);

            foreach ($temp as $k => $v) {
                $this->data->$k = $v;
            }

            // Get the groups the user should be added to after registration.
            $this->data->groups = [];

            // Get the default new user group, Registered if not specified.
            $system = $params->get('new_usertype', 2);

            $this->data->groups[] = $system;

            // Unset the passwords.
            unset($this->data->password1);
            unset($this->data->password2);

            // Get the dispatcher and load the users plugins.
            /*  PluginHelper::importPlugin('user');

                //$event = GenericEvent::create('onContentPrepareData',array('com_volunteers.registration', $this->data));
                //$this->getDispatcher()->dispatch($event->getName(),$event)
                $dispatcher = EventDispatcher::getInstance();
                //JPluginHelper::importPlugin('user');

                // Trigger the data preparation event.
                $dispatcher = $this->getDispatcher();
                //$results = $dispatcher->trigger('onContentPrepareData', array('com_volunteers.registration', $this->data));

                // Check for errors encountered while preparing the data.
                if (count($results) && in_array(false, $results, true))
                {
                    $this->setError($dispatcher->getError());
                    $this->data = false;
                }*/
        }

        return $this->data;
    }

    /**
     * Method to save the form data.
     *
     * @param   array  $temp  The form data.
     *
     * @return  bool|VolunteerModel  The user id on success, false on failure.
     *
     * @since 4.0.0
     * @throws \PHPMailer\PHPMailer\Exception
     * @throws Exception
     */
    public function register(array $temp)
    {
        $data = (array) $this->getData();

        // Merge in the registration data.
        foreach ($temp as $k => $v) {
            $data[$k] = $v;
        }

        // Volunteer Model
        $volunteer = new VolunteerModel();

        if (!$volunteer->save($data)) {
            //Change to enqeue message MF
            $this->app->enqueueMessage(Text::sprintf('COM_VOLUNTEERS_REGISTRATION_SAVE_FAILED'));

            return false;
        }

        // Global config
        $config = $this->app->getConfig();

        // Compile the notification mail values.
        $data['fromname'] = $config->get('fromname');
        $data['mailfrom'] = $config->get('mailfrom');
        $data['sitename'] = $config->get('sitename');
        $data['siteurl']  = Uri::root();

        $emailSubject = Text::sprintf(
            'COM_USERS_EMAIL_ACCOUNT_DETAILS',
            $data['name'],
            $data['sitename']
        );

        $emailBody = Text::sprintf(
            'COM_USERS_EMAIL_REGISTERED_BODY_NOPW',
            $data['name'],
            $data['sitename'],
            $data['siteurl']
        );

        // Send the registration email.
        $mailer = Factory::getContainer()->get(MailerFactoryInterface::class)->createMailer();
        $mailer->sendMail($data['mailfrom'], $data['fromname'], $data['email'], $emailSubject, $emailBody);

        return $volunteer;
    }

    /**
     * Constructor
     *
     * @param   array  $config  An array of configuration options (name, state, dbo, table_path, ignore_request).
     *
     * @since 4.0.0
     * @throws  Exception
     */
    public function __construct($config = [], MVCFactoryInterface $factory = null)
    {
        $config     = array_merge(
            [
                'events_map' => ['validate' => 'user'],
            ],
            $config
        );
        $this->data = new stdClass();
        parent::__construct($config, $factory);

        $this->app = Factory::getApplication();
    }
}
