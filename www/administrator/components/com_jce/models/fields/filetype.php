<?php

defined('JPATH_PLATFORM') or die;

JFormHelper::loadFieldClass('list');

class JFormFieldFiletype extends JFormField
{

    /**
     * The form field type.
     *
     * @var    string
     *
     * @since  11.1
     */
    protected $type = 'Filetype';

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

    private static function array_flatten($array, $return)
    {
        foreach ($array as $key => $value) {
            if (is_array($value)) {
                $return = self::array_flatten($value, $return);
            } else {
                $return[] = $value;
            }
        }

        return $return;
    }

    private function mapValue($value)
    {
        $data = array();

        // no grouping
        if (strpos($value, '=') === false) {
            return array(explode(',', $value));
        }

        foreach (explode(';', $value) as $group) {
            $items = explode('=', $group);
            $name = $items[0];
            $values = explode(',', $items[1]);

            array_walk($values, function (&$item, $name) {
                if ($name{0} === '-') {
                    $item = '-' . $item;
                }
            }, $name);

            $data[$name] = $values;
        }

        return $data;
    }

    private function cleanValue($value)
    {
        $data = $this->mapValue($value);
        // get array values only
        $values = self::array_flatten($data, array());
        // convert to string
        $string = implode(',', $values);
        // return single array
        return explode(',', $string);
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
        // cleanup string
        $value = str_replace('&#34;', '"', $this->value);
        $value = htmlspecialchars_decode($value, ENT_QUOTES);

        $class = ((string) $this->getAttribute('class') ? 'class="' . (string) $this->getAttribute('class') . '"' : '');

        // default extensions list
        $default = (string) $this->getAttribute('default');
        
        // create default array
        $default = $this->mapValue($default);

        if ($value && $value{0} === '=') {
            $value = substr($value, 1);
        }

        if (!empty($value)) {
            $data = $this->mapValue($value);
        }

        $output = array();

        $output[] = '<div class="filetype input-append">';
        $output[] = '<input type="text" name="' . $this->name . '" id="' . $this->id . '" value="' . $value . '" ' . $class . ' /><button class="btn btn-link filetype-edit"><i class="icon-edit icon-apply"></i></button>';

        foreach ($data as $group => $items) {
            $custom = array();

            $output[] = '<dl>';

            if (is_string($group)) {
                $checked = '';

                $is_default = isset($default[$group]);

                if (empty($value) || $is_default || (!$is_default && $group{0} !== '-')) {
                    $checked = ' checked="checked"';
                }

                // clear minus sign
                $group = str_replace('-', '', $group);

                $output[] = '<dt data-filetype-group="' . $group . '"><label><input type="checkbox" value="' . $group . '"' . $checked . ' />' . $group . '</label></dt>';
            }

            foreach ($items as $item) {
                $checked = '';

                $item = strtolower($item);

                // clear minus sign
                $mod = str_replace('-', '', $item);

                $is_default = !empty($default[$group]) && in_array($item, $default[$group]);

                if (empty($value) || $is_default || (!$is_default && $mod === $item)) {
                    $checked = ' checked="checked"';
                }

                $output[] = '<dd><label><input type="checkbox" value="' . $mod . '"' . $checked . ' /><span class="file ' . $mod . '"></span>&nbsp;' . $mod . '</label>';

                if (!$is_default) {
                    $output[] = '<button class="btn btn-link filetype-remove"><span class="icon-trash"></span></button>';
                }

                $output[] = '</dd>';
            }

            $output[] = '<dd class="filetype-custom"><span class="file"></span><input type="text" value="" placeholder="' . JText::_('COM_JCE_FILETYPE_MAPPER_TYPE_NEW') . '" /><button class="btn btn-link filetype-add"><span class="icon-plus"></span></button><button class="btn btn-link filetype-remove"><span class="icon-trash"></span></button></dd>';

            $output[] = '</dl>';
        }

        $output[] = '</div>';

        return implode("\n", $output);
    }
}
