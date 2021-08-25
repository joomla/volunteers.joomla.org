<?php
/**
 * @package     JCE
 * @subpackage  Layout
 *
 * @copyright   Copyright (C) 2005 - 2019 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('JPATH_BASE') or die;

$data = $displayData;

?>
<textarea
	spellcheck="false"
	autocomplete="off"
	name="<?php echo $data->name; ?>"
	id="<?php echo $data->id; ?>"
	cols="<?php echo $data->cols; ?>"
	rows="<?php echo $data->rows; ?>"
	style="width: <?php echo $data->width; ?>; height: <?php echo $data->height; ?>;"
	class="<?php echo empty($data->class) ? 'mce_editable' : $data->class; ?>"
><?php echo $data->content; ?></textarea>
