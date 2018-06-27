<?php

defined('JPATH_PLATFORM') or die;

JFormHelper::loadFieldClass('list');

class JFormFieldFilesystem extends JFormField
{

    /**
     * The form field type.
     *
     * @var    string
     *
     * @since  11.1
     */
    protected $type = 'Filesystem';

    /**
     * Method to attach a JForm object to the field.
     *
     * @param   SimpleXMLElement  $element  The SimpleXMLElement object representing the <field /> tag for the form field object.
     * @param   mixed             $value    The form field value to validate.
     * @param   string            $group    The field name group control value. This acts as as an array container for the field.
     *                                      For example if the field has name="foo" and the group value is set to "bar" then the
     *                                      full field name would end up being "bar[foo]".
     *
     * @return  boolean  True on success.
     *
     * @since   11.1
     */
    public function setup(SimpleXMLElement $element, $value, $group = null)
    {
        $return = parent::setup($element, $value, $group);

        return $return;
    }

    /**
     * Method to get the field input markup.
     *
     * @return  string  The field input markup.
     *
     * @since   11.1
     */
    protected function getInput()
    {
        $value = $this->value;

        // decode json string
        if (!empty($value) && is_string($value)) {
            $value = json_decode($value, true);
        }

        // default
        if (empty($value)) {
            $type = $this->default;
            $path = '';

            $value = array(
                array('type' => $type, 'path' => $path, 'label' => ''),
            );
        }

        $plugins = $this->getPlugins();
        $options = $this->getOptions();
        
        $html = "";
        $x = 0;

        $html .= '<div class="wf-repeatable span5">';

        foreach ($value as $item) {
            $item = (object) $item;

            $html .= '<div class="wf-repeatable-item well clearfix">';
            //$html .= '<a class="wf-repeatable-collapse"><span class="icon-bar"></span><span class="icon-bar"></span><span class="icon-bar"></span></a>';
            $html .= '<a class="close wf-repeatable-clone" href="#"><i class="icon-plus"></i></a>';

            $html .= '<div class="wf-repeatable-item-container">';

            $html .= '<div class="control-group">';
            $html .= '<div class="control-label"><label class="hasTooltip" title="' . JText::_('COM_JCE_PROFILE_FILESYSTEM_TYPE_DESC') . '">' . JText::_('COM_JCE_PROFILE_FILESYSTEM_TYPE') . '</label></div>';
            $html .= '<div class="controls controls-row">';
            $html .= JHtml::_('select.genericlist', $options, null, 'data-name="type" data-toggle="filesystem-options"', 'value', 'text', $item->type, $this->id . '_type_' . $x);
            $html .= '</div>';
            $html .= '</div>';

            $html .= '<div class="filesystem-options">';

            foreach($plugins as $plugin) {
                $name = (string) str_replace('filesystem-', '', $plugin->element);

                $form = JForm::getInstance('plg_jce_' . $plugin->element, $plugin->manifest, array('control' => $this->name . '[' . $name . ']'), true, '//extension');

                if ($form) {
                    $html .= '<div data-toggle-target="filesystem-options-' . $name . '">';

                    $fields = $form->getFieldset('filesystem.' . $name);

                    foreach ($fields as $field) {
                        $key = $field->getAttribute('name');

                        // set repeatable
                        $field->repeat = true;

                        $field->name  = '';

                        if ($name === $item->type) {
                            $field->value = isset($item->$key) ? $item->$key : '';
                        }

                        $string = $field->renderField();

                        $html .= str_replace(' name=""', ' data-name="' . $key . '"', $string);
                    }

                    $html .= '</div>';
                }
            }

            $html .= '</div>';
            $html .= '</div>';

            $html .= '<a class="close wf-repeatable-remove" href="#"><i class="icon-trash"></i></a>';
            $html .= '</div>';

            $x++;
        }

        $html .= '<input type="hidden" name="' . $this->name . '" value="" />';
        $html .= '</div>';

        return $html;
    }

    /**
     * Method to get the field options.
     *
     * @return  array  The field option objects.
     *
     * @since   11.1
     */
    protected function getPlugins()
    {
        static $plugins;

        if (!isset($plugins)) {

            $language = JFactory::getLanguage();

            $db = JFactory::getDbo();
            $query = $db->getQuery(true)
                ->select('name, element')
                ->from('#__extensions')
                ->where('enabled = 1')
                ->where('type =' . $db->quote('plugin'))
                ->where('state IN (0,1)')
                ->where('folder = ' . $db->quote('jce'))
                ->where('element LIKE ' . $db->quote('filesystem-%'))
                ->order('ordering');

            $plugins = $db->setQuery($query)->loadObjectList();

            foreach($plugins as $plugin) {
                $name = str_replace('filesystem-', '', $plugin->element);
                
                // load language file                
                $language->load('plg_jce_filesystem_' . $name, JPATH_ADMINISTRATOR);
                // create manifest path
                $plugin->manifest = JPATH_PLUGINS . '/jce/' . $plugin->element . '/' . $plugin->element . '.xml';
            }
        }

        return $plugins;
    }

    /**
     * Method to get the field options.
     *
     * @return  array  The field option objects.
     *
     * @since   11.1
     */
    protected function getOptions()
    {
        $fieldname = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $this->fieldname);

        $options = array();

        $options[] = array(
            'value' => '',
            'text' => JText::_('WF_OPTION_NOT_SET'),
        );

        $plugins = $this->getPlugins();

        foreach ($plugins as $plugin) {
            $value  = (string) str_replace('filesystem-', '', $plugin->element);
            $text   = (string) $plugin->name;

            $tmp = array(
                'value' => $value,
                'text' => JText::alt($text, $fieldname),
                'disable' => false,
                'class' => '',
                'selected' => false,
            );

            // Add the option object to the result set.
            $options[] = (object) $tmp;
        }

        reset($options);

        return $options;
    }
}
