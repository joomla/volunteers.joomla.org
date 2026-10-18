<?php

/**
 * @package    Com_Volunteers
 * @version    4.0.0
 * @author     The Joomla Project <secretary@opensourcematters.org>
 * @copyright  2023 The Joomla Project
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Component\Volunteers\Site\View\Board;

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
use Joomla\Component\Volunteers\Site\Model\BoardModel;
use Exception;
use stdClass;

/**
 * View class Volunteer Board Display.
 *
 * @since  4.0.0
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
        /** @var BoardModel $model */

        $model      = $this->getModel();
        $model->setUseExceptions(true);
        $this->item = $model->getBoardItem();

        $this->state         = $model->getState();
        $this->form          = $model->getForm();
        $this->user          = $this->getCurrentUser();
        $this->item->reports = $model->getDepartmentReports(58);
        $this->item->members = $model->getDepartmentMembers(58);

        /** @var AclService $aclService */
        $aclService = $this->app->bootComponent('com_volunteers')->getContainer()->get(AclService::class);
        $this->acl  = $aclService->getAcl('department', (int) $this->item->id);

        // Set department id in session
        $this->app->getSession()->set('department', $this->item->id);

        $errors = $model->getErrors();
        if ($errors && count($errors) > 0) {
            throw new GenericDataException(implode("\n", $errors));
        }


        if ($this->_layout == 'edit') {
            $authorised = $this->user->authorise('core.create', 'com_volunteers');

            if ($authorised !== true) {
                throw new Exception(Text::_('JERROR_ALERTNOAUTHOR'));
            }
        }

        $this->prepareDocument();

        parent::display($tpl);
    }

    /**
     * Prepares the document
     *
     * @return void
     *
     * @since 4.0.0
     * @throws Exception
     *
     */
    protected function prepareDocument()
    {

        $title       = $this->item->title;
        $description = StringHelper::truncate($this->item->description, 160, true, false);
        $image       = 'https://cdn.joomla.org/images/joomla-org-og.jpg';
        $itemURL     = Route::_('index.php?option=com_volunteers&view=board&id=' . $this->item->id);
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
        $route = Route::_('index.php?option=com_volunteers&view=reports&filter_category=d.' . $this->item->id . '&format=feed&type=rss', false);
        $this->getDocument()->addHeadLink($route, 'alternate', 'rel', $props);

        // Add the ATOM link.
        $props = ['type' => 'application/atom+xml', 'title' => 'Atom 1.0'];
        $route = Route::_('index.php?option=com_volunteers&view=reports&filter_category=d.' . $this->item->id . '&format=feed&type=atom', false);
        $this->getDocument()->addHeadLink($route, 'alternate', 'rel', $props);
    }
}
