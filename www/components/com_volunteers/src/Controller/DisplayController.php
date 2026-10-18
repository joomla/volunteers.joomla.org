<?php

/**
 * @version    4.0.0
 * @package    Com_Volunteers
 * @author     The Joomla Project <secretary@opensourcematters.org>
 * @copyright  2023 The Joomla Project
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Component\Volunteers\Site\Controller;

// phpcs:disable PSR1.Files.SideEffects
use Exception;
use Joomla\CMS\MVC\Controller\BaseController;

\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Display Component Controller
 *
 * @since  4.0.0
 */
class DisplayController extends BaseController
{
    /**
     * Method to display a view.
     *
     * @param   boolean  $cachable   If true, the view output will be cached
     * @param   array    $urlparams  An array of safe url parameters and their variable types, for valid values see {@link InputFilter::clean()}.
     *
     * @return  BaseController  This object to support chaining.
     *
     * @since   4.0.0
     * @throws Exception
     */
    public function display($cachable = false, $urlparams = [])
    {
        $view = $this->input->getCmd('view', 'home');
        $view = $view == "featured" ? 'home' : $view;
        $this->input->set('view', $view);

        return parent::display($cachable, $urlparams);
    }
}
