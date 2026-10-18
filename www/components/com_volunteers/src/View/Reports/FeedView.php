<?php

/**
 * @package    Joomla! Volunteers
 * @copyright  Copyright (C) 2016 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Component\Volunteers\Site\View\Reports;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

use Exception;
use Joomla\CMS\Document\Feed\FeedItem;
use Joomla\CMS\Factory;
use Joomla\CMS\HTML\Helpers\StringHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Router\Route;

/**
 * Feed class for a list of reports.
 *
 * @since  4.0.0
 */
class FeedView extends BaseHtmlView
{
    protected $category;

    /**
     * Execute and display a template script.
     *
     * @param   string  $tpl  The name of the template file to parse; automatically searches through the template paths.
     *
     * @return  void  A string if successful, otherwise a Error object.
     *
     * @since 4.0.0
     * @throws Exception
     */
    public function display($tpl = null)
    {
        // Parameters
        $app            = Factory::getApplication();
        $doc            = $app->getDocument();
        $siteEmail      = $app->get('mailfrom');
        $this->category = $this->Category ?? null;

        // Set document data
        $doc->title = ($this->category) ? Text::_('COM_VOLUNTEERS_TITLE_REPORTS') . ': ' . $this->category : Text::_('COM_VOLUNTEERS_TITLE_REPORTS');
        $doc->link  = Route::_('index.php?option=com_volunteers&view=reports');

        // Get some data from the model
        $app->getInput()->set('limit', $app->get('feed_limit'));
        $rows = $this->Items ?? null;

        foreach ($rows as $row) {
            // Load individual item creator class
            $item              = new FeedItem();
            $item->title       = $this->escape($row->title);
            $item->link        = Route::_('index.php?option=com_volunteers&view=report&id=' . $row->id);
            $item->description = StringHelper::truncate($row->description, 1000);
            $item->date        = $row->created;
            $item->category    = $row->department_title;
            $item->author      = $row->volunteer_name;
            $item->authorEmail = ($row->volunteer_email_feed) ? $row->volunteer_email : $siteEmail;

            // Loads item info into rss array
            $doc->addItem($item);
        }
    }
}
