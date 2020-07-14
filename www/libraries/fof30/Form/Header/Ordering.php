<?php
/**
 * @package   FOF
 * @copyright Copyright (c)2010-2020 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 2, or later
 */

namespace FOF30\Form\Header;

use JHtml;
use JText;

defined('_JEXEC') or die;

/**
 * Ordering field header
 *
 * @deprecated 3.1  Support for XML forms will be removed in FOF 4
 */
class Ordering extends Field
{
	/**
	 * Get the header
	 *
	 * @return  string  The header HTML
	 */
	protected function getHeader()
	{
		$sortable = ($this->element['sortable'] != 'false');

		$dnd = isset($this->element['dragndrop']) ? (string) $this->element['dragndrop'] : 'notbroken';

		if (strtolower($dnd) == 'notbroken')
		{
			$dnd = !version_compare(JVERSION, '3.5.0', 'ge');
		}
		else
		{
			$dnd = in_array(strtolower($dnd), array('1', 'true', 'yes', 'on', 'enabled'), true);
		}

		if (!$sortable)
		{
			// Non sortable?! I'm not sure why you'd want that, but if you insist...
			return JText::_('JGRID_HEADING_ORDERING');
		}

		$iconClass = isset($this->element['iconClass']) ? (string) $this->element['iconClass'] : 'icon-menu-2';
		$class     = isset($this->element['class']) ? (string) $this->element['class'] : 'btn btn-micro pull-right';

		$view  = $this->form->getView();
		$model = $this->form->getModel();

		// Drag'n'drop ordering support WITH a save order button
		$html = JHtml::_(
			'grid.sort',
			'<i class="' . $iconClass . '"></i>',
			'ordering',
			$view->getLists()->order_Dir,
			$view->getLists()->order,
			null,
			'asc',
			'JGRID_HEADING_ORDERING'
		);

		$ordering = $view->getLists()->order == 'ordering';

		/**
		 * Joomla! 3.5 and later: drag and drop reordering is broken when the ordering field is not hidden
		 * because some random bloke submitted that code and some supposedly responsible adult with commit
		 * rights committed it. I tried to file a PR to fix it and got the reply "can't test, won't test".
		 * OK, then. You blindly accepted code which did the EXACT OPPOSITE of what it promised and broke
		 * b/c. However, you won't accept the fix to your mess from someone who knows how Joomla! works and
		 * wasted 2 hours of his time to track down your mistake, fix it and explain why your actions
		 * resulted in a b/c break. You have to be kidding me!
		 */
		$joomla35IsBroken = version_compare(JVERSION, '3.5.0', 'ge');

		if ($ordering && (!$joomla35IsBroken || !$dnd))
		{
			$html .= '<a href="javascript:saveorder(' . (count($model->get()) - 1) . ', \'saveorder\')" ' .
				'rel="tooltip" class="save-order ' . $class . '" title="' . JText::_('JLIB_HTML_SAVE_ORDER') . '">'
				. '<span class="icon-ok"></span></a>';
		}

		return $html;
	}
}
