<?php
/**
 * @package    CopyMe
 *
 * @author     Carlos Cámara <carlos@hepta.es>
 * @copyright  2019 Hepta Technologies SL
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 * @link       https://www.hepta.es
 */

defined('_JEXEC') or die;

use Joomla\CMS\Language\Text as CMSText;

JHtml::_('script', 'hepta/vendor/clipboard/clipboard.min.js', array('version' => 'auto', 'relative' => true, 'defer' => 'defer'));
JHtml::stylesheet('hepta/copyme.css', ['version' => 'auto', 'relative' => true]);
?>

<div class="copyme-panel">
<div>
    <textarea class="copy-me-content" readonly><?php echo $copyMeContent; ?></textarea>
    <button class="copy-me-button" data-clipboard-action="copy">
        <svg class="feather-icon">
			<use xlink:href="/media/hepta/icons/feathericons.svg#copy"/>
		</svg>
        <?php echo CMSText::_('PLG_CONTENT_COPYME_COPY_BUTTON');?>
    </button>
</div>

<script>
new ClipboardJS('.copy-me-button');
new ClipboardJS('.copy-me-button', {
    target: function(trigger) {
        return trigger.previousElementSibling;
    }
});
</script>
</div>