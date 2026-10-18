<?php

/**
 * @package    Joomla! Volunteers
 * @copyright  Copyright (C) 2016 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Component\Volunteers\Site\Field;

use Joomla\CMS\Form\Field\ListField;
use Joomla\Component\Volunteers\Site\Helper\VolunteersHelper;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects


/**
 * Countries Field class.
 * @since 4.0.0
 */
class CountriesField extends ListField
{
    /**
     * The form field type.
     *
     * @var        string
     * @since 4.0.0
     */
    protected $type = 'Countries';

    /**
     * Method to get the field options.
     *
     * @return  array  The field option objects.
     * @since 4.0.0
     */
    public function getOptions()
    {
        $options = VolunteersHelper::countries();

        return array_merge(parent::getOptions(), $options);
    }
}
