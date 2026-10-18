<?php

/**
 * @package    Joomla! Volunteers
 * @copyright  Copyright (C) 2016 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Component\Volunteers\Site\View\Roles;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

use Joomla\CMS\Application\CMSApplicationInterface;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\GenericDataException;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;

use Joomla\CMS\Pagination\Pagination;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Uri\Uri;
use Joomla\Component\Volunteers\Site\Model\RolesModel;

/**
 * View class for a list of roles.
 *
 * @since  4.0.0
 */
class HtmlView extends BaseHtmlView
{
    protected array $items;
    protected Pagination $pagination;
    protected mixed $state;

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
     * @since 4.0.0
     * @throws Exception
     *
     */
    public function display($tpl = null)
    {
        /** @var RolesModel $model */

        $model       = $this->getModel();
        $model->setUseExceptions(true);
        $this->items = $model->getOpenRoles();

        $errors = $model->getErrors();
        if ($errors && count($errors) > 0) {
            throw new GenericDataException(implode("\n", $errors));
        }

        $this->prepareDocument();

        parent::display($tpl);
    }

    /**
     * Prepares the document.
     *
     * @return  void.
     *
     * @since 4.0.0
     */
    protected function prepareDocument()
    {
        // Prepare variables
        $title   = Text::_('COM_VOLUNTEERS_TITLE_ROLESOPEN');
        $itemURL = Route::_('index.php?option=com_volunteers&view=roles');
        $url     = Uri::getInstance()->toString(['scheme', 'host', 'port']) . $itemURL;

        // Set meta
        $this->getDocument()->
        setTitle($title);

        // Twitter Card metadata
        $this->getDocument()->
        setMetaData('twitter:title', $title);
        $this->getDocument()->
        setMetaData('twitter:image', Uri::getInstance()->toString(['scheme', 'host', 'port']) . '/images/volunteers-help-wanted-twitter.jpg');

        // OpenGraph metadata
        $this->getDocument()->
        setMetaData('og:title', $title, 'property');
        $this->getDocument()->
        setMetaData('og:image', Uri::getInstance()->toString(['scheme', 'host', 'port']) . '/images/volunteers-help-wanted.jpg', 'property');
        $this->getDocument()->
        setMetaData('og:type', 'article', 'property');
        $this->getDocument()->
        setMetaData('og:url', $url, 'property');
    }
}
