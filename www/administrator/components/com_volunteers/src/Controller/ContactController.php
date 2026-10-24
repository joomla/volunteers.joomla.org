<?php

/**
 * @version    4.0.0
 * @package    Com_Volunteers
 * @author     The Joomla Project <secretary@opensourcematters.org>
 * @copyright  2023 The Joomla Project
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Component\Volunteers\Administrator\Controller;

use Exception;
use Joomla\CMS\Factory;
use Joomla\CMS\Helper\ContentHelper;
use Joomla\CMS\Language\LanguageFactoryInterface;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Mail\MailerFactoryInterface;
use Joomla\CMS\Mail\MailTemplate;
use Joomla\CMS\MVC\Controller\FormController;
use Joomla\CMS\Router\Route;
use Joomla\Database\DatabaseInterface;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Contact controller class.
 *
 * @since 4.0.0
 */
class ContactController extends FormController
{
    protected $view_list = 'contacts';
    /**
     * Send an email to all active volunteers
     *
     * @return  void
     * @since 4.0.0
     * @throws  Exception
     */
    public function send()
    {
        // Check for request forgeries.
        $this->checkToken();

        $session = $this->app->getSession();
        $user    = $this->app->getIdentity();
        $canDo   = ContentHelper::getActions('com_volunteers');

        // Super users access only
        if (!$canDo->get('core.manage')) {
            throw new Exception('No access to mail sending', 403);
        }

        $mailData   = $this->app->input->get('jform', [], 'Array');
        $subject    = trim($mailData['subject'] ?? '');
        $body       = trim($mailData['message'] ?? '');
        $recipients = $session->get('volunteers.recipients', []);

        // The subject and the message may be empty when the mail template has a fixed text
        if (!$recipients) {
            $this->setRedirect(Route::_('index.php?option=com_volunteers&view=contact', false), Text::_('COM_VOLUNTEERS_MESSAGE_CONTACT_INCOMPLETE'), 'warning');

            return;
        }

        // Collect the emails for the recipients
        $emails = [];

        foreach ($recipients as $recipient) {
            if ($recipient['email'] !== $user->email) {
                $emails[] = $recipient['email'];
            }
        }

        $emails = array_unique($emails);

        // Send one mail to the sender with the members in BCC, through the mail template so the site mail settings apply
        $container = Factory::getContainer();
        $mailer    = new MailTemplate(
            'com_volunteers.members.mail',
            $this->app->getLanguage()->getTag(),
            $container->get(MailerFactoryInterface::class)->createMailer(),
            $container->get(LanguageFactoryInterface::class),
            $container->get(DatabaseInterface::class)
        );

        // The message is HTML from the editor, a plain text mail gets it as text
        $mailer->addTemplateData(['subject' => $subject, 'body' => $body]);
        $mailer->addTemplateData(['subject' => $subject, 'body' => $this->htmlToText($body)], true);
        $mailer->addRecipient($user->email, $user->name);
        $mailer->setReplyTo($user->email, $user->name);

        foreach ($emails as $email) {
            $mailer->addRecipient($email, null, 'bcc');
        }

        // An empty subject and message fail when the mail template has no fixed text either
        $error = '';

        try {
            $success = $mailer->send();
        } catch (Exception $exception) {
            $success = false;
            $error   = $exception->getMessage();
        }

        // Keep the message and the recipients when sending fails, so it can be sent again
        if (!$success) {
            $this->app->setUserState('com_volunteers.edit.contact.data', $mailData);
            $this->setRedirect(
                Route::_('index.php?option=com_volunteers&view=contact', false),
                Text::sprintf('COM_VOLUNTEERS_MESSAGE_SENDING_FAILED', $error ?: Text::_('COM_VOLUNTEERS_MESSAGE_SENDING_FAILED_TEMPLATE')),
                'error'
            );

            return;
        }

        // Clear recipients
        $session->remove('volunteers.recipients');
        $this->app->setUserState('com_volunteers.edit.contact.data', null);

        $this->setRedirect(Route::_('index.php?option=com_volunteers&view=members', false), Text::_('COM_VOLUNTEERS_MESSAGE_SEND_SUCCESS'));
    }

    /**
     * Method for closing the contact form.
     *
     * @param   string  $key  The name of the primary key of the URL variable.
     *
     * @return  boolean  True if access level checks pass, false otherwise.
     * @since 1.0.0
     */
    public function cancel($key = null)
    {
        $this->app->getSession()->remove('volunteers.recipients');
        $this->app->setUserState('com_volunteers.edit.contact.data', null);
        $this->setRedirect(Route::_('index.php?option=com_volunteers&view=members', false));

        return true;
    }

    /**
     * Convert the HTML of the editor to the text of a plain text mail
     *
     * @param   string  $html  The HTML message
     *
     * @return  string  The text message
     *
     * @since   4.0.0
     */
    private function htmlToText(string $html): string
    {
        // Keep the line breaks of paragraphs, lines and list items
        $text = preg_replace('#<br\s*/?>#i', "\n", $html);
        $text = preg_replace('#</(p|div|h[1-6]|li|tr)>#i', "\n", $text);
        $text = preg_replace('#<li[^>]*>#i', '- ', $text);
        $text = html_entity_decode(strip_tags($text), ENT_QUOTES | ENT_HTML5, 'UTF-8');

        // No more than one empty line in a row
        return trim(preg_replace("#\n\s*\n\s*\n+#", "\n\n", $text));
    }
}
