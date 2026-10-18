<?php

/**
 * @package    Joomla! Volunteers
 * @copyright  Copyright (C) 2017 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */
/** @var \Joomla\Component\Volunteers\Administrator\View\Contact\HtmlView $this */
use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Router\Route;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects


Factory::getApplication()->getDocument()->getWebAssetManager()->useScript('form.validate');

?>

<form action="<?php echo Route::_('index.php?option=com_volunteers&view=contact'); ?>"
      method="post" name="adminForm" id="adminForm" class="form-validate">
    <?php if ($this->recipients) : ?>
    <h3><?php echo count($this->recipients); ?> recipients</h3>
    <table class="table table-striped">
        <?php foreach ($this->recipients as $recipient) : ?>
            <tr>
                <td width="200px"><strong><?php echo $recipient['name']; ?></strong></td>
                <td width="300px"> <?php echo $recipient['email']; ?></td>
                <td width="200px"> <?php echo $recipient['position']; ?></td>
                <td> <?php echo $recipient['team']; ?></td>
            </tr>
        <?php endforeach; ?>
    <?php endif; ?>
    </table>
    <h3>Email</h3>
    <?php echo $this->form->renderFieldset('message'); ?>

    <input type="hidden" name="task" value=""/>
    <?php echo HTMLHelper::_('form.token'); ?>
</form>
