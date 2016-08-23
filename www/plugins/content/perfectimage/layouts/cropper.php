<?php
/*
 * @package		Perfect Image Form Field
 * @copyright	Copyright (c) 2016 Perfect Web Team / perfectwebteam.nl
 * @license		GNU General Public License version 3 or later
 */

// No direct access.
defined('_JEXEC') or die;

// Input
$width          = $displayData['width'];
$ratio          = $displayData['ratio'];
$id             = $displayData['id'];
$maxSize        = $displayData['max_size'];
$maxSizeMessage = $displayData['max_size_message'];
$maxDimension   = $displayData['max_dimension'];
?>

<div class="btn-toolbar perfect-image-toolbar">

	<div class="btn-group">
		<label class="btn btn-primary btn-upload perfect-image-upload" for="<?php echo $id; ?>_select">
			<input type="file" id="<?php echo $id; ?>_select" name="file" accept="image/*">
			<?php echo JText::_('COM_VOLUNTEERS_IMAGECROPPER_SELECT_FILE'); ?>
		</label>
	</div>

	<div class="btn-group">
		<button type="button" class="btn" data-method="rotate" data-option="-90"><?php echo JText::_('COM_VOLUNTEERS_IMAGECROPPER_ROTATE_LEFT'); ?>-90°</button>
		<button type="button" class="btn" data-method="rotate" data-option="-45">-45°</button>
		<button type="button" class="btn" data-method="rotate" data-option="-30">-30°</button>
		<button type="button" class="btn" data-method="rotate" data-option="-15">-15°</button>
	</div>

	<div class="btn-group">
		<button type="button" class="btn" data-method="rotate" data-option="90"><?php echo JText::_('COM_VOLUNTEERS_IMAGECROPPER_ROTATE_RIGHT'); ?> 90°</button>
		<button type="button" class="btn" data-method="rotate" data-option="45">45°</button>
		<button type="button" class="btn" data-method="rotate" data-option="30">30°</button>
		<button type="button" class="btn" data-method="rotate" data-option="15">15°</button>
	</div>

	<div class="btn-group">
		<button type="button" class="btn" data-method="scaleX" data-option="-1" title="Flip Horizontal"><?php echo JText::_('COM_VOLUNTEERS_IMAGECROPPER_FLIP_HORIZONTAL'); ?></button>
		<button type="button" class="btn" data-method="scaleY" data-option="-1" title="Flip Vertical"><?php echo JText::_('COM_VOLUNTEERS_IMAGECROPPER_FLIP_VERTICAL'); ?></button>
	</div>

</div>

<div class="cropper-container cropper-bg">
	<img class="perfect-image-image">
</div>

<img id="temp-image-holder" style="display: none;"/>

<input type="hidden" class="perfect-image-data" name="perfect-image-data">
<input type="hidden" class="perfect-image-width" name="perfect-image-width" value="<?php echo $width; ?>">
<input type="hidden" class="perfect-image-ratio" name="perfect-image-ratio" value="<?php echo $ratio; ?>">
<input type="hidden" class="perfect-image-maxsize" name="perfect-image-maxsize" value="<?php echo $maxSize; ?>">
<input type="hidden" class="perfect-image-maxsize-message" name="perfect-image-maxsize-message" value="<?php echo $maxSizeMessage; ?>">
<input type="hidden" class="perfect-image-dimensionsize" name="perfect-image-dimensionsize" value="<?php echo $maxDimension; ?>">


