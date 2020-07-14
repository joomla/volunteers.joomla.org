<?php
/**
 * @package   FOF
 * @copyright Copyright (c)2010-2020 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 2, or later
 */

namespace FOF30\Render;

use FOF30\Container\Container;
use FOF30\Form\FieldInterface;
use FOF30\Form\Form;
use FOF30\Form\Header\Ordering as HeaderOrdering;
use FOF30\Form\Field\Ordering as FieldOrdering;
use FOF30\Model\DataModel;
use FOF30\Toolbar\Toolbar;
use FOF30\View\View;
use JHtml;
use JHtmlSidebar;

defined('_JEXEC') or die;

/**
 * Renderer class for use with Akeeba Strapper
 *
 * Renderer options
 * linkbar_style        Style for linkbars: joomla3|classic. Default: joomla3
 *
 * @package FOF30\Render
 */
class AkeebaStrapper extends RenderBase implements RenderInterface
{
	/**
	 * Public constructor. Determines the priority of this class and if it should be enabled
	 */
	public function __construct(Container $container)
	{
		$this->priority	 = 60;
		$this->enabled	 = class_exists('\\AkeebaStrapper30');

		parent::__construct($container);
	}

	/**
	 * Echoes any HTML to show before the view template
	 *
	 * @param   string    $view    The current view
	 * @param   string    $task    The current task
	 *
	 * @return  void
	 */
	public function preRender($view, $task)
	{
		$input = $this->container->input;
		$platform = $this->container->platform;

		$format	 = $input->getCmd('format', 'html');

		if (empty($format))
		{
			$format	 = 'html';
		}

		if ($format != 'html')
		{
			return;
		}

		if ($platform->isCli())
		{
			return;
		}

		JHtml::_('behavior.core');
		JHtml::_('jquery.framework', true);


		// Wrap output in various classes
		$versionParts = explode('.', JVERSION);
		$minorVersion = $versionParts[0] . $versionParts[1];
		$majorVersion = $versionParts[0];

		$classes = array();

		if ($platform->isBackend())
		{
			$area = $platform->isBackend() ? 'admin' : 'site';
			$option = $input->getCmd('option', '');
			$viewForCssClass = $input->getCmd('view', '');
			$layout = $input->getCmd('layout', '');
			$taskForCssClass = $input->getCmd('task', '');

			$classes = array(
				'joomla-version-' . $majorVersion,
				'joomla-version-' . $minorVersion,
				$area,
				$option,
				'view-' . $view,
				'view-' . $viewForCssClass,
				'layout-' . $layout,
				'task-' . $task,
				'task-' . $taskForCssClass,
				// We have a floating sidebar, they said. It looks great, they said. They must've been blind, I say!
				'j-toggle-main',
				'j-toggle-transition',
				'row-fluid',
			);

			$classes = array_unique($classes);
		}

		// Wrap output in divs
		echo '<div id="akeeba-bootstrap" class="' . implode(' ', $classes) . "\">\n";
		echo "<div class=\"akeeba-bootstrap\">\n";
		echo "<div class=\"row-fluid\">\n";

		// Render submenu and toolbar (only if asked to)
		if ($input->getBool('render_toolbar', true))
		{
			$this->renderButtons($view, $task);
			$this->renderLinkbar($view, $task);
		}

		parent::preRender($view, $task);
	}

	/**
	 * Echoes any HTML to show after the view template
	 *
	 * @param   string    $view    The current view
	 * @param   string    $task    The current task
	 *
	 * @return  void
	 */
	public function postRender($view, $task)
	{
		$input = $this->container->input;
		$platform = $this->container->platform;

		$format = $input->getCmd('format', 'html');

		if ($format != 'html' || $platform->isCli())
		{
			return;
		}

		$sidebarEntries = JHtmlSidebar::getEntries();

		if (!empty($sidebarEntries))
		{
			echo '</div>';
		}

		echo "</div>\n";    // Closes row-fluid div
		echo "</div>\n";    // Closes akeeba-bootstrap div
		echo "</div>\n";    // Closes joomla-version div
	}

	/**
	 * Loads the validation script for an edit form
	 *
	 * @param   Form  &$form  The form we are rendering
	 *
	 * @return  void
	 *
	 * @deprecated 3.1  Support for XML forms will be removed in FOF 4
	 */
	protected function loadValidationScript(Form &$form)
	{
		$message = $form->getView()->escape(\JText::_('JGLOBAL_VALIDATION_FORM_FAILED'));

		$js = <<<JS
Joomla.submitbutton = function(task)
{
	if (task == 'cancel' || document.formvalidator.isValid(document.id('adminForm')))
	{
		Joomla.submitform(task, document.getElementById('adminForm'));
	}
	else {
		alert('$message');
	}
};
JS;

		$platform = $this->container->platform;
		$document = $platform->getDocument();

		if ($document instanceof \JDocument)
		{
			$document->addScriptDeclaration($js);
		}
	}

	/**
	 * Renders the submenu (link bar)
	 *
	 * @param   string    $view    The active view name
	 * @param   string    $task    The current task
	 *
	 * @return  void
	 */
	protected function renderLinkbar($view, $task)
	{
		$style = $this->getOption('linkbar_style', 'joomla');

		switch ($style)
		{
			case 'joomla':
				$this->renderLinkbar_joomla($view, $task);
				break;

			case 'classic':
			default:
				$this->renderLinkbar_classic($view, $task);
				break;
		}
	}

	/**
	 * Renders the submenu (link bar) in F0F's classic style, using a Bootstrapped
	 * tab bar.
	 *
	 * @param   string    $view    The active view name
	 * @param   string    $task    The current task
	 *
	 * @return  void
	 */
	protected function renderLinkbar_classic($view, $task)
	{
		$platform = $this->container->platform;

		if ($platform->isCli())
		{
			return;
		}

		$isJoomla4 = version_compare(JVERSION, '3.99999.99999', 'gt');
		$isJoomla3 = !$isJoomla4 && version_compare(JVERSION, '3.0.0', 'ge');

		// Do not render a submenu unless we are in the the admin area
		$toolbar               = $this->container->toolbar;
		$renderFrontendSubmenu = $toolbar->getRenderFrontendSubmenu();

		if (!$platform->isBackend() && !$renderFrontendSubmenu)
		{
			return;
		}

		$links = $toolbar->getLinks();

		if (!empty($links))
		{
			echo "<ul class=\"nav nav-tabs\">\n";

			foreach ($links as $link)
			{
				$dropdown = false;

				if (array_key_exists('dropdown', $link))
				{
					$dropdown = $link['dropdown'];
				}

				if ($dropdown)
				{
					echo "<li";
					$class = 'nav-item dropdown';

					if ($link['active'])
					{
						$class .= ' active';
					}

					echo ' class="' . $class . '">';

					echo '<a class="nav-link dropdown-toggle" data-toggle="dropdown" href="#">';

					if ($link['icon'])
					{
						echo "<i class=\"icon icon-" . $link['icon'] . "\"></i>";
					}

					echo $link['name'];
					echo '<b class="caret"></b>';
					echo '</a>';

					echo "\n<ul class=\"dropdown-menu\">";

					foreach ($link['items'] as $item)
					{
						echo "<li class=\"dropdown-item";

						if ($item['active'])
						{
							echo ' active';
						}

						echo "\">";

						if ($item['icon'])
						{
							echo "<i class=\"icon icon-" . $item['icon'] . "\"></i>";
						}

						if ($item['link'])
						{
							echo "<a href=\"" . $item['link'] . "\">" . $item['name'] . "</a>";
						}
						else
						{
							echo $item['name'];
						}

						echo "</li>";
					}

					echo "</ul>\n";
				}
				else
				{
					echo "<li class=\"nav-item";

					if ($link['active'] && $isJoomla3)
					{
						echo ' active"';
					}

					echo "\">";

					if ($link['icon'])
					{
						echo "<span class=\"icon icon-" . $link['icon'] . "\"></span>";
					}

					if ($isJoomla3)
					{
						if ($link['link'])
						{
							echo "<a href=\"" . $link['link'] . "\">" . $link['name'] . "</a>";
						}
						else
						{
							echo $link['name'];
						}
					}
					else
					{
						$class = $link['active'] ? 'active' : '';

						$href = $link['link'] ? $link['link'] : '#';

						echo "<a href=\"$href\" class=\"nav-link $class\">{$link['name']}</a>";
					}
				}

				echo "</li>\n";
			}

			echo "</ul>\n";
		}
	}

	/**
	 * Renders the submenu (link bar) using Joomla!'s style. On Joomla! 2.5 this
	 * is a list of bar separated links, on Joomla! 3 it's a sidebar at the
	 * left-hand side of the page.
	 *
	 * @param   string    $view    The active view name
	 * @param   string    $task    The current task
	 *
	 * @return  void
	 */
	protected function renderLinkbar_joomla($view, $task)
	{
		$platform = $this->container->platform;

		// On command line don't do anything
		if ($platform->isCli())
		{
			return;
		}

		// Do not render a submenu unless we are in the the admin area
		$toolbar               = $this->container->toolbar;
		$renderFrontendSubmenu = $toolbar->getRenderFrontendSubmenu();

		if (!$platform->isBackend() && !$renderFrontendSubmenu)
		{
			return;
		}

		$this->renderLinkbarItems($toolbar);
	}

	/**
	 * Render the linkbar
	 *
	 * @param   Toolbar  $toolbar  A toolbar object
	 *
	 * @return  void
	 */
	protected function renderLinkbarItems($toolbar)
	{
		$links = $toolbar->getLinks();

		if (!empty($links))
		{
			foreach ($links as $link)
			{
				JHtmlSidebar::addEntry($link['name'], $link['link'], $link['active']);

				$dropdown = false;

				if (array_key_exists('dropdown', $link))
				{
					$dropdown = $link['dropdown'];
				}

				if ($dropdown)
				{
					foreach ($link['items'] as $item)
					{
						JHtmlSidebar::addEntry('– ' . $item['name'], $item['link'], $item['active']);
					}
				}
			}
		}
	}

	/**
	 * Renders the toolbar buttons
	 *
	 * @param   string    $view    The active view name
	 * @param   string    $task    The current task
	 *
	 * @return  void
	 */
	protected function renderButtons($view, $task)
	{
		// Prevent phpStorm from complaining
		if ($view) {}
		if ($task) {}

		$platform = $this->container->platform;

		if ($platform->isCli())
		{
			return;
		}

		// Do not render buttons unless we are in the the frontend area and we are asked to do so
		$toolbar				 = $this->container->toolbar;
		$renderFrontendButtons	 = $toolbar->getRenderFrontendButtons();

		// Load main backend language, in order to display toolbar strings
		// (JTOOLBAR_BACK, JTOOLBAR_PUBLISH etc etc)
		$platform->loadTranslations('joomla');

		if ($platform->isBackend() || !$renderFrontendButtons)
		{
			return;
		}

		$bar	 = \JToolBar::getInstance('toolbar');
		$items	 = $bar->getItems();

		$substitutions = array(
			'icon-32-new'		 => 'icon-plus',
			'icon-32-publish'	 => 'icon-eye-open',
			'icon-32-unpublish'	 => 'icon-eye-close',
			'icon-32-delete'	 => 'icon-trash',
			'icon-32-edit'		 => 'icon-edit',
			'icon-32-copy'		 => 'icon-th-large',
			'icon-32-cancel'	 => 'icon-remove',
			'icon-32-back'		 => 'icon-circle-arrow-left',
			'icon-32-apply'		 => 'icon-ok',
			'icon-32-save'		 => 'icon-hdd',
			'icon-32-save-new'	 => 'icon-repeat',
		);

		if (isset(\JFactory::getApplication()->JComponentTitle))
		{
			$title	 = \JFactory::getApplication()->JComponentTitle;
		}
		else
		{
			$title = '';
		}

		$html	 = array();
		$actions = array();

		// We have to use the same id we're using inside other renderers
		$html[]	 = '<div class="well" id="FOFHeaderContainer">';
		$html[]  =      '<div class="titleContainer">'.$title.'</div>';
		$html[]  =      '<div class="buttonsContainer">';

		foreach ($items as $node)
		{
			$type	 = $node[0];
			$button	 = $bar->loadButtonType($type);

			if ($button !== false)
			{
				/**
				if (method_exists($button, 'fetchId'))
				{
					$id = call_user_func_array(array(&$button, 'fetchId'), $node);
				}
				else
				{
					$id = null;
				}
				/**/

				$action	    = call_user_func_array(array(&$button, 'fetchButton'), $node);
				$action	    = str_replace('class="toolbar"', 'class="toolbar btn"', $action);
				$action	    = str_replace('<span ', '<i ', $action);
				$action	    = str_replace('</span>', '</i>', $action);
				$action	    = str_replace(array_keys($substitutions), array_values($substitutions), $action);
				$actions[]	= $action;
			}
		}

		$html   = array_merge($html, $actions);
		$html[] = '</div>';
		$html[] = '</div>';

		echo implode("\n", $html);
	}

	/**
	 * Renders a Form for a Browse view and returns the corresponding HTML
	 *
	 * @param   Form   &$form  The form to render
	 * @param   DataModel  $model  The model providing our data
	 *
	 * @return  string    The HTML rendering of the form
	 *
	 * @deprecated 3.1  Support for XML forms will be removed in FOF 4
	 */
	public function renderFormBrowse(Form &$form, DataModel $model)
	{
		$html = '';

		JHtml::_('behavior.multiselect');

		JHtml::_('bootstrap.tooltip');
		JHtml::_('dropdown.init');
		$view	 = $form->getView();
		$order	 = $view->escape($view->getLists()->order);

		$html .= <<<HTML
<script type="text/javascript">
	Joomla.orderTable = function() {
		var table = document.getElementById("sortTable");
		var direction = document.getElementById("directionTable");
		var order = table.options[table.selectedIndex].value;
		var dirn = 'asc';

		if (order != '$order')
		{
			dirn = 'asc';
		}
		else {
			dirn = direction.options[direction.selectedIndex].value;
		}

		Joomla.tableOrdering(order, dirn);
	};
</script>

HTML;

		// Getting all header row elements
		$headerFields = $form->getHeaderset();

		// Get form parameters
		$show_header		 = $form->getAttribute('show_header', 1);
		$show_filters		 = $form->getAttribute('show_filters', 1);
		$show_pagination	 = $form->getAttribute('show_pagination', 1);
		$norows_placeholder	 = $form->getAttribute('norows_placeholder', '');

		if ($show_filters)
		{
			JHtmlSidebar::setAction("index.php?option=" .
				$this->container->componentName . "&view=" .
				$this->container->inflector->pluralize($form->getView()->getName())
			);
		}
        else
        {
            // If I don't want to display the sidebar, I have to manually tell Joomla that I I already loaded it
            // otherwise it will create the "empty" space on the left, but no elements will be there. Yuk!
            $js = <<<JS
localStorage.setItem('jsidebar', "true");
JS;
            $document = $this->container->platform->getDocument();

            if ($document instanceof \JDocument)
            {
                $document->addScriptDeclaration($js);
            }
        }

		// Reorder the fields with ordering first
		$tmpFields = array();
		$i = 1;

		foreach ($headerFields as $tmpField)
		{
			if ($tmpField instanceof HeaderOrdering)
			{
				$tmpFields[0] = $tmpField;
			}

			else
			{
				$tmpFields[$i] = $tmpField;
			}

			$i++;
		}

		$headerFields = $tmpFields;
		ksort($headerFields, SORT_NUMERIC);

		// Pre-render the header and filter rows
		$header_html = '';
		$filter_html = '';
		$sortFields	 = array();

		if ($show_header)
		{
			foreach ($headerFields as $headerField)
			{
				$header		 = $headerField->header;
				$filter		 = $headerField->filter;
				$buttons	 = $headerField->buttons;
				$options	 = $headerField->options;
				$sortable	 = $headerField->sortable;
				$tdwidth	 = $headerField->tdwidth;

				// If it's a sortable field, add to the list of sortable fields

				if ($sortable)
				{
					$sortFields[$headerField->name] = \JText::_($headerField->label);
				}

				// Get the table data width, if set

				if (!empty($tdwidth))
				{
					$tdwidth = 'width="' . $tdwidth . '"';
				}
				else
				{
					$tdwidth = '';
				}

				if (!empty($header) && $show_header)
				{
					$header_html .= "\t\t\t\t\t<th $tdwidth>" . "\n";
					$header_html .= "\t\t\t\t\t\t" . $header;
					$header_html .= "\t\t\t\t\t</th>" . "\n";
				}

				if (!empty($filter))
				{
					$filter_html .= '<div class="filter-search btn-group pull-left">' . "\n";
					$filter_html .= "\t" . '<label for="title" class="element-invisible">';
					$filter_html .= \JText::_($headerField->label);
					$filter_html .= "</label>\n";
					$filter_html .= "\t$filter\n";
					$filter_html .= "</div>\n";

					if (!empty($buttons))
					{
						$filter_html .= '<div class="btn-group pull-left hidden-phone">' . "\n";
						$filter_html .= "\t$buttons\n";
						$filter_html .= '</div>' . "\n";
					}
				}
				elseif (!empty($options))
				{
					$label = $headerField->label;

					$filterName = $headerField->filterFieldName;
					$filterSource = $headerField->filterSource;

					JHtmlSidebar::addFilter(
						'- ' . \JText::_($label) . ' -',
						$filterName,
						JHtml::_(
							'select.options',
							$options,
							'value',
							'text',
							$model->getState($filterSource, ''), true
						)
					);
				}
			}
		}

		// Start the form
		$filter_order		 = $form->getView()->getLists()->order;
		$filter_order_Dir	 = $form->getView()->getLists()->order_Dir;
		$actionUrl           = $this->container->platform->isBackend() ? 'index.php' : \JUri::root().'index.php';

		if ($this->container->platform->isFrontend() && ($this->container->input->getCmd('Itemid', 0) != 0))
		{
			$itemid = $this->container->input->getCmd('Itemid', 0);
			$uri = new \JUri($actionUrl);

			if ($itemid)
			{
				$uri->setVar('Itemid', $itemid);
			}

			$actionUrl = \JRoute::_($uri->toString());
		}

		$html .= '<form action="' . $actionUrl . '" method="post" name="adminForm" id="adminForm">' . "\n";

		// Get and output the sidebar, if present
		$sidebar = JHtmlSidebar::render();

		if ($show_filters && !empty($sidebar)
			&& (!$this->container->platform->isFrontend() || $this->container->toolbar->getRenderFrontendSubmenu())
		)
		{
			$html .= '<div id="j-sidebar-container" class="span2">' . "\n";
			$html .= "\t$sidebar\n";
			$html .= "</div>\n";
			$html .= '<div id="j-main-container" class="span10">' . "\n";
		}
		else
		{
			$html .= '<div id="j-main-container">' . "\n";
		}

		// Render header search fields, if the header is enabled
		$pagination = $form->getView()->getPagination();

		if ($show_header)
		{
			$html .= "\t" . '<div id="filter-bar" class="btn-toolbar">' . "\n";
			$html .= "$filter_html\n";

			if ($show_pagination)
			{
				// Render the pagination rows per page selection box, if the pagination is enabled
				$html .= "\t" . '<div class="btn-group pull-right hidden-phone">' . "\n";
				$html .= "\t\t" . '<label for="limit" class="element-invisible">' . \JText::_('JFIELD_PLG_SEARCH_SEARCHLIMIT_DESC') . '</label>' . "\n";
				$html .= "\t\t" . $pagination->getLimitBox() . "\n";
				$html .= "\t" . '</div>' . "\n";
			}

			if (!empty($sortFields))
			{
				// Display the field sort order
				$asc_sel	 = ($form->getView()->getLists()->order_Dir == 'asc') ? 'selected="selected"' : '';
				$desc_sel	 = ($form->getView()->getLists()->order_Dir == 'desc') ? 'selected="selected"' : '';
				$html .= "\t" . '<div class="btn-group pull-right hidden-phone">' . "\n";
				$html .= "\t\t" . '<label for="directionTable" class="element-invisible">' . \JText::_('JFIELD_ORDERING_DESC') . '</label>' . "\n";
				$html .= "\t\t" . '<select name="directionTable" id="directionTable" class="input-medium" onchange="Joomla.orderTable()">' . "\n";
				$html .= "\t\t\t" . '<option value="">' . \JText::_('JFIELD_ORDERING_DESC') . '</option>' . "\n";
				$html .= "\t\t\t" . '<option value="asc" ' . $asc_sel . '>' . \JText::_('JGLOBAL_ORDER_ASCENDING') . '</option>' . "\n";
				$html .= "\t\t\t" . '<option value="desc" ' . $desc_sel . '>' . \JText::_('JGLOBAL_ORDER_DESCENDING') . '</option>' . "\n";
				$html .= "\t\t" . '</select>' . "\n";
				$html .= "\t" . '</div>' . "\n\n";

				// Display the sort fields
				$html .= "\t" . '<div class="btn-group pull-right">' . "\n";
				$html .= "\t\t" . '<label for="sortTable" class="element-invisible">' . \JText::_('JGLOBAL_SORT_BY') . '</label>' . "\n";
				$html .= "\t\t" . '<select name="sortTable" id="sortTable" class="input-medium" onchange="Joomla.orderTable()">' . "\n";
				$html .= "\t\t\t" . '<option value="">' . \JText::_('JGLOBAL_SORT_BY') . '</option>' . "\n";
				$html .= "\t\t\t" . JHtml::_('select.options', $sortFields, 'value', 'text', $form->getView()->getLists()->order) . "\n";
				$html .= "\t\t" . '</select>' . "\n";
				$html .= "\t" . '</div>' . "\n";
			}

			$html .= "\t</div>\n\n";
			$html .= "\t" . '<div class="clearfix"> </div>' . "\n\n";
		}

		// Start the table output
		$html .= "\t\t" . '<table class="table table-striped" id="itemsList">' . "\n";

		// Render the header row, if enabled
		if ($show_header)
		{
			$html .= "\t\t\t<thead>" . "\n";
			$html .= "\t\t\t\t<tr>" . "\n";
			$html .= $header_html;
			$html .= "\t\t\t\t</tr>" . "\n";
			$html .= "\t\t\t</thead>" . "\n";
		}

		// Loop through rows and fields, or show placeholder for no rows
		$html .= "\t\t\t<tbody>" . "\n";

		$items = $model->get();

		if ($count = count($items))
		{
			$m = 1;
			$i = 0;

			foreach ($items as $item)
			{
				$rowHtml = '';

				$form->bind($item);

				$m		 = 1 - $m;
				$rowClass	 = 'row' . $m;

				$fields = $form->getFieldset('items');

				// Reorder the fields to have ordering first
				$tmpFields = array();
				$j = 1;

				foreach ($fields as $tmpField)
				{
					if ($tmpField instanceof FieldOrdering)
					{
						$tmpFields[0] = $tmpField;
					}

					else
					{
						$tmpFields[$j] = $tmpField;
					}

					$j++;
				}

				$fields = $tmpFields;
				ksort($fields, SORT_NUMERIC);

				/** @var FieldInterface $field */
				foreach ($fields as $field)
				{
					$field->rowid	 = $i;
					$field->item	 = $item;
					$labelClass		 = $field->labelclass;
					$class			 = $labelClass ? 'class ="' . $labelClass . '"' : '';

					if (!method_exists($field, 'getRepeatable'))
					{
						throw new \Exception('getRepeatable not found in class ' . get_class($field));
					}

					// Let the fields change the row (tr element) class
					if (method_exists($field, 'getRepeatableRowClass'))
					{
						$rowClass = $field->getRepeatableRowClass($rowClass);
					}

					$rowHtml .= "\t\t\t\t\t<td $class>" . $field->getRepeatable() . '</td>' . "\n";
				}

				$html .= "\t\t\t\t<tr class=\"$rowClass\">\n" . $rowHtml . "\t\t\t\t</tr>\n";

				$i++;
			}
		}
		elseif ($norows_placeholder)
		{
			$fields		 = $form->getFieldset('items');
			$num_columns = count($fields);

			$html .= "\t\t\t\t<tr><td colspan=\"$num_columns\">";
			$html .= \JText::_($norows_placeholder);
			$html .= "</td></tr>\n";
		}

		$html .= "\t\t\t</tbody>" . "\n";

		// End the table output
		$html .= "\t\t" . '</table>' . "\n";

		// Render the pagination bar, if enabled, on J! 3.0+

		$html .= $pagination->getListFooter();

		// Close the wrapper element div on Joomla! 3.0+
		$html .= "</div>\n";

		$html .= "\t" . '<input type="hidden" name="option" value="' . $this->container->componentName . '" />' . "\n";
		$html .= "\t" . '<input type="hidden" name="view" value="' . $this->container->inflector->pluralize($form->getView()->getName()) . '" />' . "\n";
		$html .= "\t" . '<input type="hidden" name="task" value="' . $form->getView()->getTask() . '" />' . "\n";
		$html .= "\t" . '<input type="hidden" name="layout" value="' . $form->getView()->getLayout() . '" />' . "\n";
		$html .= "\t" . '<input type="hidden" name="format" value="' . $this->container->input->getCmd('format', 'html') . '" />' . "\n";

		if ($tmpl = $this->container->input->getCmd('tmpl', ''))
		{
			$html .= "\t" . '<input type="hidden" name="tmpl" value="' . $tmpl . '" />' . "\n";
		}


		// The id field is required in Joomla! 3 front-end to prevent the pagination limit box from screwing it up.
		if ($this->container->platform->isFrontend())
		{
			$html .= "\t" . '<input type="hidden" name="id" value="" />' . "\n";
		}

		$html .= "\t" . '<input type="hidden" name="boxchecked" value="" />' . "\n";
		$html .= "\t" . '<input type="hidden" name="hidemainmenu" value="" />' . "\n";
		$html .= "\t" . '<input type="hidden" name="filter_order" value="' . $filter_order . '" />' . "\n";
		$html .= "\t" . '<input type="hidden" name="filter_order_Dir" value="' . $filter_order_Dir . '" />' . "\n";

		$html .= "\t" . '<input type="hidden" name="' . $this->container->platform->getToken(true) . '" value="1" />' . "\n";

		// End the form
		$html .= '</form>' . "\n";

		return $html;
	}

	/**
	 * Renders a Form for a Read view and returns the corresponding HTML
	 *
	 * @param   Form   &$form  The form to render
	 * @param   DataModel  $model  The model providing our data
	 *
	 * @return  string    The HTML rendering of the form
	 *
	 * @deprecated 3.1  Support for XML forms will be removed in FOF 4
	 */
	public function renderFormRead(Form &$form, DataModel $model)
	{
		$html = $this->renderFormRaw($form, $model, 'read');

		return $html;
	}

	/**
	 * Renders a Form for an Edit view and returns the corresponding HTML
	 *
	 * @param   Form   &$form  The form to render
	 * @param   DataModel  $model  The model providing our data
	 *
	 * @return  string    The HTML rendering of the form
	 *
	 * @deprecated 3.1  Support for XML forms will be removed in FOF 4
	 */
	public function renderFormEdit(Form &$form, DataModel $model)
	{
		// Get the key for this model's table
		$key		 = $model->getKeyName();
		$keyValue	 = $model->getId();

		$html = '';

		$validate	 = strtolower($form->getAttribute('validate'));

		if (in_array($validate, array('true', 'yes', '1', 'on')))
		{
			JHtml::_('behavior.formvalidation');
			$class = ' form-validate';
			$this->loadValidationScript($form);
		}
		else
		{
			$class = '';
		}

		// Check form enctype. Use enctype="multipart/form-data" to upload binary files in your form.
		$template_form_enctype = $form->getAttribute('enctype');

		if (!empty($template_form_enctype))
		{
			$enctype = ' enctype="' . $form->getAttribute('enctype') . '" ';
		}
		else
		{
			$enctype = '';
		}

		// Check form name. Use name="yourformname" to modify the name of your form.
		$formname = $form->getAttribute('name');

		if (empty($formname))
		{
			$formname = 'adminForm';
		}

		// Check form ID. Use id="yourformname" to modify the id of your form.
		$formid = $form->getAttribute('id');

		if (empty($formid))
		{
			$formid = $formname;
		}

		// Check if we have a custom task
		$customTask = $form->getAttribute('customTask');

		if (empty($customTask))
		{
			$customTask = '';
		}

		// Get the form action URL
		$platform = $this->container->platform;
		$actionUrl = $platform->isBackend() ? 'index.php' : \JUri::root().'index.php';

		$itemid = $this->container->input->getCmd('Itemid', 0);
		if ($platform->isFrontend() && ($itemid != 0))
		{
			$uri = new \JUri($actionUrl);

			if ($itemid)
			{
				$uri->setVar('Itemid', $itemid);
			}

			$actionUrl = \JRoute::_($uri->toString());
		}

		$html .= '<form action="'.$actionUrl.'" method="post" name="' . $formname .
		         '" id="' . $formid . '"' . $enctype . ' class="form-horizontal' .
		         $class . '">' . "\n";
		$html .= "\t" . '<input type="hidden" name="option" value="' . $this->container->componentName . '" />' . "\n";
		$html .= "\t" . '<input type="hidden" name="view" value="' . $form->getView()->getName() . '" />' . "\n";
		$html .= "\t" . '<input type="hidden" name="task" value="' . $customTask . '" />' . "\n";
		$html .= "\t" . '<input type="hidden" name="' . $key . '" value="' . $keyValue . '" />' . "\n";
		$html .= "\t" . '<input type="hidden" name="format" value="' . $this->container->input->getCmd('format', 'html') . '" />' . "\n";

		if ($tmpl = $this->container->input->getCmd('tmpl', ''))
		{
			$html .= "\t" . '<input type="hidden" name="tmpl" value="' . $tmpl . '" />' . "\n";
		}

		$html .= "\t" . '<input type="hidden" name="' . $this->container->platform->getToken(true) . '" value="1" />' . "\n";

		$html .= $this->renderFormRaw($form, $model, 'edit');
		$html .= '</form>';

		return $html;
	}

	/**
	 * Renders a raw Form and returns the corresponding HTML
	 *
	 * @param   Form   &$form     The form to render
	 * @param   DataModel  $model     The model providing our data
	 * @param   string    $formType  The form type e.g. 'edit' or 'read'
	 *
	 * @return  string    The HTML rendering of the form
	 *
	 * @deprecated 3.1  Support for XML forms will be removed in FOF 4
	 */
	public function renderFormRaw(Form &$form, DataModel $model, $formType = null)
	{
		$html = '';
		$tabHtml = array();

		// Do we have a tabbed form?
		$isTabbed = $form->getAttribute('tabbed', '0');
		$isTabbed = in_array($isTabbed, array('true', 'yes', 'on', '1'));

		foreach ($form->getFieldsets() as $fieldset)
		{
			if ($isTabbed && $this->isTabFieldset($fieldset))
			{
				continue;
			}
			elseif ($isTabbed && isset($fieldset->innertab))
			{
				$inTab = $fieldset->innertab;
			}
			else
			{
				$inTab = '__outer';
			}

			$tabHtml[$inTab][] = $this->renderFieldset($fieldset, $form, $model, $formType, false);
		}

		// If the form is tabbed, render the tabs bars
		if ($isTabbed)
		{
			$html .= '<ul class="nav nav-tabs">' . "\n";

			foreach ($form->getFieldsets() as $fieldset)
			{
				// Only create tabs for tab fieldsets
				$isTabbedFieldset = $this->isTabFieldset($fieldset);
				if (!$isTabbedFieldset)
				{
					continue;
				}

				// Only create tabs if we do have a label
				if (!isset($fieldset->label) || empty($fieldset->label))
				{
					continue;
				}

				$label = \JText::_($fieldset->label);
				$name = $fieldset->name;
				$liClass = ($isTabbedFieldset == 2) ? 'class="active"' : '';

				$html .= "<li $liClass><a href=\"#$name\" data-toggle=\"tab\">$label</a></li>" . "\n";
			}

			$html .= '</ul>' . "\n\n<div class=\"tab-content\">" . "\n";

			foreach ($form->getFieldsets() as $fieldset)
			{
				if (!$this->isTabFieldset($fieldset))
				{
					continue;
				}

				$html .= $this->renderFieldset($fieldset, $form, $model, $formType, false, $tabHtml);
			}

			$html .= "</div>\n";
		}

		if (isset($tabHtml['__outer']))
		{
			$html .= implode('', $tabHtml['__outer']);
		}

		return $html;
	}

	/**
	 * Renders a raw fieldset of a F0FForm and returns the corresponding HTML
	 *
	 * @param   \stdClass  &$fieldset   The fieldset to render
	 * @param   Form       &$form       The form to render
	 * @param   DataModel  $model       The model providing our data
	 * @param   string     $formType    The form type e.g. 'edit' or 'read'
	 * @param   boolean    $showHeader  Should I render the fieldset's header?
	 * @param   string     $innerHtml   Render inner tab if set
	 *
	 * @return  string    The HTML rendering of the fieldset
	 *
	 * @deprecated 3.1  Support for XML forms will be removed in FOF 4
	 */
	public function renderFieldset(\stdClass &$fieldset, Form &$form, DataModel $model, $formType, $showHeader = true, &$innerHtml = null)
	{
		$html = '';

		$fields = $form->getFieldset($fieldset->name);

		if (isset($fieldset->class))
		{
			$class = 'class="' . $fieldset->class . '"';
		}
		else
		{
			$class = '';
		}

		if (isset($innerHtml[$fieldset->name]))
		{
			$innerclass = isset($fieldset->innerclass) ? ' class="' . $fieldset->innerclass . '"' : '';

			$html .= "\t" . '<div id="' . $fieldset->name . '" ' . $class . '>' . "\n";
			$html .= "\t\t" . '<div' . $innerclass . '>' . "\n";
		}
		else
		{
			$html .= "\t" . '<div id="' . $fieldset->name . '" ' . $class . '>' . "\n";
		}

		$isTabbedFieldset = $this->isTabFieldset($fieldset);

		if (isset($fieldset->label) && !empty($fieldset->label) && !$isTabbedFieldset)
		{
			$html .= "\t\t" . '<h3>' . \JText::_($fieldset->label) . '</h3>' . "\n";
		}

		// Add an external view template, if specified
		$sourceTemplate = isset($fieldset->source) ? $fieldset->source : null;
		$sourceView = isset($fieldset->source_view) ? $fieldset->source_view : null;
		$sourceViewType = isset($fieldset->source_view_type) ? $fieldset->source_view_type : 'html';
		$sourceComponent = isset($fieldset->source_component) ? $fieldset->source_component : null;

		if (!empty($sourceTemplate))
		{
			$sourceContainer = empty($sourceComponent) ? $this->container : Container::getInstance($sourceComponent);

			if (empty($sourceView))
			{
				$viewObject = new View($sourceContainer, array(
					'name' => 'FAKE_FORM_VIEW'
				));
			}
			else
			{
				$viewObject = $sourceContainer->factory->view($sourceView, $sourceViewType);
			}

			$viewObject->populateFromModel($model);

			$html .= $viewObject->loadAnyTemplate($sourceTemplate, array(
				'model' => $model,
				'form' => $form,
				'fieldset' => $fieldset,
				'formType' => $formType,
				'innerHtml' => $innerHtml
			));
		}

		// Add the fieldset fields
		if (!empty($fields)) foreach ($fields as $field)
		{
			// TODO see \JFormField::renderField

			$groupClass	 = $form->getFieldAttribute($field->fieldname, 'groupclass', '', $field->group);

			// Auto-generate label and description if needed
			// Field label
			$title 		     = $form->getFieldAttribute($field->fieldname, 'label', '', $field->group);
			$emptylabel      = $form->getFieldAttribute($field->fieldname, 'emptylabel', false, $field->group);
			$label_placement = $form->getFieldAttribute($field->fieldname, 'label_placement', null, $field->group);

			if (empty($title) && !$emptylabel)
			{
				$model->getName();
				$title = strtoupper($this->container->componentName . '_' . $model->getName() . '_' . $field->id . '_LABEL');
			}

			if (empty($label_placement))
			{
				$label_placement = !empty($title) ? 'left' : 'none';
			}

			// Field description
			$description = $form->getFieldAttribute($field->fieldname, 'description', '', $field->group);

			$prependText = $form->getFieldAttribute($field->fieldname, 'prepend_text', '', $field->group);
			$appendText = $form->getFieldAttribute($field->fieldname, 'append_text', '', $field->group);

			if (!empty($prependText))
			{
				$prependText = \JText::_($prependText);
			}

			if (!empty($appendText))
			{
				$appendText = \JText::_($appendText);
			}

			$inputField = '';

			if ($formType == 'read')
			{
				$inputField = $field->static;
			}

			if ($formType == 'edit')
			{
				$inputField = $field->input;
			}

			if ($prependText || $appendText)
			{
				$wrapperClass = $prependText ? 'input-prepend' : '';
				$wrapperClass .= $appendText ? 'input-append' : '';
			}

			$renderedLabel = !empty($title) ? $this->renderFieldsetLabel($field, $form, $title) : '';
			$renderedLabel = ($label_placement == 'empty') ? '' : $renderedLabel;

			switch ($label_placement)
			{
				case 'left':
				case 'empty':
					$html .= "\t\t\t" . '<div class="control-group ' . $groupClass . '">' . "\n";
					$html .= "\t\t\t" . $renderedLabel;
					$html .= "\t\t\t\t" . '<div class="controls">' . "\n";
					break;

				case 'top':
					$html .= "\t\t\t" . '<div class="' . $groupClass . '">' . "\n";
					$html .= "\t\t\t" . $renderedLabel . "<br/>\n";
					break;
			}

			if ($prependText || $appendText)
			{
				$html .= "\t\t\t\t<div class=\"$wrapperClass\">\n";
			}

			if ($prependText)
			{
				$html .= "\t\t\t\t\t<span class=\"add-on\">$prependText</span>\n";
			}

			$html .= "\t\t\t\t\t" . $inputField . "\n";

			if ($appendText)
			{
				$html .= "\t\t\t\t\t<span class=\"add-on\">$appendText</span>\n";
			}

			if ($prependText || $appendText)
			{
				$html .= "\t\t\t\t</div>\n";
			}

			if (!empty($description))
			{
				$html .= "\t\t\t\t" . '<span class="help-block">';
				$html .= \JText::_($description) . '</span>' . "\n";
			}

			switch ($label_placement)
			{
				case 'left':
				case 'empty':
					$html .= "\t\t\t\t" . '</div>' . "\n";
					$html .= "\t\t\t" . '</div>' . "\n";
					break;

				case 'top':
					$html .= "\t\t\t" . '</div>' . "\n";
					break;

				case 'bottom':
					$html .= "\t\t\t" . '<br/>' . "\n";
					$html .= "\t\t\t" . $renderedLabel . "\n";
					$html .= "\t\t\t" . '</div>' . "\n";
			}
		}

		if (isset($innerHtml[$fieldset->name]))
		{
			$html .= "\t\t" . '</div>' . "\n";
			$html .= implode('', $innerHtml[$fieldset->name]) . "\n";
			$html .= "\t" . '</div>' . "\n";
		}
		else
		{
			$html .= "\t" . '</div>' . "\n";
		}

		return $html;
	}

	/**
	 * Renders a label for a fieldset.
	 *
	 * @param   object  	$field  	The field of the label to render
	 * @param   Form   	&$form      The form to render
	 * @param 	string		$title		The title of the label
	 *
	 * @return 	string		The rendered label
	 *
	 * @deprecated 3.1  Support for XML forms will be removed in FOF 4
	 */
	public function renderFieldsetLabel($field, Form &$form, $title)
	{
		$html = '';

		$labelClass	 = $field->labelClass ? $field->labelClass : $field->labelclass; // Joomla! 2.5/3.x use different case for the same name
		$required	 = $field->required;

		$tooltip = $form->getFieldAttribute($field->fieldname, 'tooltip', '', $field->group);

		if (!empty($tooltip))
		{
			static $loadedTooltipScript = false;

			if (!$loadedTooltipScript)
			{
				$js = <<<JS
(function($)
{
	$(document).ready(function()
	{
		$('.fof-tooltip').tooltip({placement: 'top'});
	});
})(akeeba.jQuery);
JS;
				$document = $this->container->platform->getDocument();

				if ($document instanceof \JDocument)
				{
					$document->addScriptDeclaration($js);
				}

				$loadedTooltipScript = true;
			}

			$tooltipText = '<strong>' . \JText::_($title) . '</strong><br />' . \JText::_($tooltip);

			$html .= "\t\t\t\t" . '<label class="control-label fof-tooltip ' . $labelClass . '" for="' . $field->id . '" title="' . $tooltipText . '" data-toggle="fof-tooltip">';
		}
		else
		{
			$html .= "\t\t\t\t" . '<label class="control-label ' . $labelClass . '" for="' . $field->id . '">';
		}

		$html .= \JText::_($title);

		if ($required)
		{
			$html .= ' *';
		}

		$html .= '</label>' . "\n";

		return $html;
	}

	/**
	 * Renders a Form and returns the corresponding HTML
	 *
	 * @param   Form      &$form         The form to render
	 * @param   DataModel $model         The model providing our data
	 * @param   string    $formType      The form type: edit, browse or read
	 * @param   boolean   $raw           If true, the raw form fields rendering (without the surrounding form tag) is
	 *                                   returned.
	 *
	 * @return  string    The HTML rendering of the form
	 *
	 * @deprecated 3.1  Support for XML forms will be removed in FOF 4
	 */
	function renderForm(Form &$form, DataModel $model, $formType = null, $raw = false)
	{
		$useChosen = $form->getAttribute('chosen', 'select');
		$useChosen = in_array($useChosen, array('false', 'no', 'off', '0')) ? '' : $useChosen;

		if ($useChosen)
		{
			JHtml::_('formbehavior.chosen', $useChosen);
		}

		if (is_null($formType))
		{
			$formType = $form->getAttribute('type', 'edit');
		}
		else
		{
			$formType = strtolower($formType);
		}

		switch ($formType)
		{
			case 'browse':
				return $this->renderFormBrowse($form, $model);
				break;

			case 'read':
				if ($raw)
				{
					return $this->renderFormRaw($form, $model, 'read');
				}
				else
				{
					return $this->renderFormRead($form, $model);
				}

				break;

			default:
				if ($raw)
				{
					return $this->renderFormRaw($form, $model, 'edit');
				}
				else
				{
					return $this->renderFormEdit($form, $model);
				}
				break;
		}
	}
}
