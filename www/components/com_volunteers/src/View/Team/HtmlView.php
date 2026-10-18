<?php

/**
 * @package    Joomla! Volunteers
 * @copyright  Copyright (C) 2016 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Component\Volunteers\Site\View\Team;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

use Joomla\CMS\Application\CMSApplicationInterface;
use Joomla\CMS\HTML\Helpers\StringHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\GenericDataException;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\User\User;
use Joomla\Component\Volunteers\Administrator\Service\AclService;
use Joomla\Component\Volunteers\Site\Model\TeamModel;
use Exception;
use stdClass;

/**
 * View to edit a team.
 *
 * @since 4.0.0
 */
class HtmlView extends BaseHtmlView
{
    protected mixed $state;
    protected mixed $item;
    protected mixed $form;
    protected User|null $user = null;
    protected stdClass $acl;

    /**
     * @var CMSApplicationInterface
     * @since  6.1.0
     */
    protected $app;

    /**
     * Constructor
     *
     * @param   array  $config  A named configuration array for object construction.
     *
     * @since   4.0.0
     */
    public function __construct($config = [])
    {
        parent::__construct($config);

        $this->app = \Joomla\CMS\Factory::getApplication();
    }


    /**
     * Execute and display a template script.
     *
     * @param   string  $tpl  The name of the template file to parse; automatically searches through the template paths.
     *
     * @return  void
     *
     * @since 4.0.0
     * @throws Exception
     */
    public function display($tpl = null)
    {

        /** @var TeamModel $model */

        $model      = $this->getModel();
        $model->setUseExceptions(true);
        $this->item = $model->getItem();

        $this->state          = $model->getState();
        $this->form           = $model->getForm();
        $this->user           = $this->getCurrentUser();
        $this->item->reports  = $model->getTeamReports();
        $this->item->subteams = $model->getTeamSubteams($this->item->id);
        $this->item->members  = $model->getTeamMembers();
        $this->item->roles    = $model->getTeamRoles();

        /** @var AclService $aclService */
        $aclService = $this->app->bootComponent('com_volunteers')->getContainer()->get(AclService::class);
        $this->acl  = $aclService->getAcl('team', (int) $this->item->id);

        // Set team id in session
        $this->app->getSession()->set('team', $this->item->id);

        // Active / inactive
        $this->item->active = (empty($this->item->date_ended) || $this->item->date_ended === '0000-00-00');

        $errors = $model->getErrors();
        if ($errors && count($errors) > 0) {
            throw new GenericDataException(implode("\n", $errors));
        }

        // Manipulate form
        $this->manipulateForm();

        // Prepare document
        $this->prepareDocument();

        parent::display($tpl);
    }

    /**
     * Manipulates the form.
     *
     * @return  void.
     *
     * @since 4.0.0
     * @throws Exception
     */
    protected function manipulateForm()
    {
        // Manipulate frontend edit form
        $teamId = $this->app->getInput()->getInt('id');

        // Clear date ended field if not set
        if (empty($this->item->date_ended) || $this->item->date_ended === '0000-00-00') {
            $this->form->setValue('date_ended', null, null);
        }

        // If editing existing team
        if ($teamId) {
            if (!$this->acl->edit_department) {
                $this->form->setFieldAttribute('department', 'readonly', 'true');
                $this->form->setFieldAttribute('status', 'readonly', 'true');
            }
        } else {
            $departmentId = (int) $this->app->getUserState('com_volunteers.edit.team.departmentid');
            $teamId       = (int) $this->app->getUserState('com_volunteers.edit.team.teamid');
            $this->form->setValue('department', null, $departmentId);
            $this->form->setValue('parent_id', null, $teamId);
            $this->form->setValue('date_started', null, \Joomla\CMS\Factory::getDate());
            $this->form->setFieldAttribute('department', 'readonly', 'true');

            if ($teamId) {
                $this->form->setFieldAttribute('parent_id', 'readonly', 'true');
            }
        }
    }

    /**
     * Prepares the document.
     *
     * @return  void.
     * @since 4.0.0
     * @throws Exception
     */
    protected function prepareDocument()
    {
        $layout = $this->app->input->get('layout');

        if ($layout == 'edit') {
            // Prepare variables
            $title = Text::_('COM_VOLUNTEERS_TITLE_TEAMS_EDIT');

            // Set meta
            $this->getDocument()->
            setTitle($title);

            return;
        }

        // Prepare variables
        $title       = $this->item->title;
        $description = StringHelper::truncate($this->item->description, 160, true, false);
        $image       = 'https://cdn.joomla.org/images/joomla-org-og.jpg';
        $itemURL     = Route::_('index.php?option=com_volunteers&view=team&id=' . $this->item->id);
        $url         = Uri::getInstance()->toString(['scheme', 'host', 'port']) . $itemURL;

        // Set meta
        $this->getDocument()->
        setTitle($title);
        $this->getDocument()->
        setDescription($description);

        // Twitter Card metadata
        $this->getDocument()->
        setMetaData('twitter:title', $title);
        $this->getDocument()->
        setMetaData('twitter:description', $description);
        $this->getDocument()->
        setMetaData('twitter:image', $image);

        // OpenGraph metadata
        $this->getDocument()->
        setMetaData('og:title', $title, 'property');
        $this->getDocument()->
        setMetaData('og:description', $description, 'property');
        $this->getDocument()->
        setMetaData('og:image', $image, 'property');
        $this->getDocument()->
        setMetaData('og:type', 'article', 'property');
        $this->getDocument()->
        setMetaData('og:url', $url, 'property');

        // Add to pathway
        $pathway = $this->app->getPathway();
        $pathway->addItem($this->item->title, $itemURL);

        // Add the RSS link.
        $props = ['type' => 'application/rss+xml', 'title' => 'RSS 2.0'];
        $route = Route::_('index.php?option=com_volunteers&view=reports&filter_category=t.' . $this->item->id . '&format=feed&type=rss', false);
        $this->getDocument()->addHeadLink($route, 'alternate', 'rel', $props);

        // Add the ATOM link.
        $props = ['type' => 'application/atom+xml', 'title' => 'Atom 1.0'];
        $route = Route::_('index.php?option=com_volunteers&view=reports&filter_category=t.' . $this->item->id . '&format=feed&type=atom', false);
        $this->getDocument()->addHeadLink($route, 'alternate', 'rel', $props);
    }
}
