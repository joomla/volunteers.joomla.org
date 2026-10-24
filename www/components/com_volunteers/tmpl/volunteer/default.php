<?php

/**
 * @package    Joomla! Volunteers
 * @copyright  Copyright (C) 2016 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\Component\Volunteers\Site\Helper\VolunteersHelper;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/** @var \Joomla\Component\Volunteers\Site\View\Volunteer\HtmlView $this */

// Import CSS
try {
    $wa = Factory::getApplication()->getDocument()->getWebAssetManager();
    $wa->useStyle('com_volunteers.frontend');
    // Report a spam profile, the button turns green once the report is sent
    $wa->addInlineScript(<<<'JS'
document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('.js-reportspam').forEach((button) => {
        button.addEventListener('click', (event) => {
            event.preventDefault();

            const data = new URLSearchParams({
                option: 'com_ajax',
                plugin: 'reportspam',
                format: 'json',
                volunteer: button.dataset.volunteer,
                [Joomla.getOptions('csrf.token')]: 1,
            });

            fetch(Joomla.getOptions('system.paths').base + '/index.php', { method: 'POST', body: data })
                .then((response) => response.json())
                .then((result) => {
                    if (result.success && result.data) {
                        button.classList.replace('btn-danger', 'btn-success');
                        button.innerHTML = '<span class="icon-thumbs-up" aria-hidden="true"></span> ' + button.dataset.success;
                    }
                });
        });
    });
});
JS, [], [], ['core']);
    HTMLHelper::_('form.csrf');
} catch (Exception $e) {
    echo $e->getMessage();
    exit();
}
?>
<?php if ($this->item->new) { ?>
    <div class="alert alert-success">
        <h1 class="vol_h1">
            <?php echo Text::_('COM_VOLUNTEERS_PROFILE_NEW_COMPLETED') ?>
        </h1>

        <p class="lead">
            <?php echo Text::_('COM_VOLUNTEERS_WELCOME') ?>
        </p>
    </div>
<?php } ?>

<div class="row profile">
    <div class="col-3 volunteer-image">
        <?php echo VolunteersHelper::image($this->item->image, 'large', false, $this->item->name); ?>

        <?php if ($this->item->certification) { ?>
            <div class="volunteer-certificated">
                <a href="https://certification.joomla.org/certified-user-directory/<?php echo ($this->item->certification) ?>"
                    target="_blank">
                    <img src="https://certification.joomla.org/images/Badges/3x_certified_administrator_badge.png"
                        class="img-fluid" />
                </a>
            </div>
        <?php } ?>
    </div>
    <div class="col-9">
        <div class="filter-bar">
            <?php if (($this->user->id == $this->item->user_id) && $this->item->user_id) { ?>
                <a class="btn btn-light float-end" href="https://identity.joomla.org/profile?layout=edit">
                    <span class="icon-edit" aria-hidden="true"></span>
                    <?php echo Text::_('COM_VOLUNTEERS_TITLE_VOLUNTEERS_EDIT_MY') ?>
                </a>
            <?php } ?>
        </div>
        <div class="border-bottom pb-2 mb-3">
            <h1 class="vol_h1">
                <?php echo $this->item->name; ?>
            </h1>
        </div>

        <?php if ($this->item->{'city-location'} || $this->item->country) { ?>
            <p class="text-muted">
                <span class="icon-location" aria-hidden="true"></span>
                <?php echo VolunteersHelper::location($this->item->country, $this->item->{'city-location'}); ?>
            </p>
        <?php } ?>

        <p class="lead">
            <?php echo ($this->item->intro) ?>
        </p>


        <?php
        $item  = $this->item;
        $links = [
            [$item->joomlaforum, 'https://forum.joomla.org/memberlist.php?mode=viewprofile&u=' . $item->joomlaforum, 'joomlaforum', 'fa-brands fa-joomla', 'COM_VOLUNTEERS_CONNECT_JOOMLAFORUM'],
            [$item->joomladocs, 'https://docs.joomla.org/User:' . $item->joomladocs, 'joomladocs', 'fa-brands fa-joomla', 'COM_VOLUNTEERS_CONNECT_JOOMLADOCS'],
            [$item->github, 'https://github.com/' . $item->github, 'github', 'fa-brands fa-github', 'COM_VOLUNTEERS_CONNECT_GITHUB'],
            [$item->crowdin, 'https://crowdin.com/profile/' . $item->crowdin, 'crowdin', 'fa-solid fa-language', 'COM_VOLUNTEERS_CONNECT_CROWDIN'],
            [$item->stackexchange, 'https://stackexchange.com/users/' . $item->stackexchange, 'stackexchange', 'fa-brands fa-stack-exchange', 'COM_VOLUNTEERS_CONNECT_STACKEXCHANGE'],
            [$item->joomlastackexchange, 'https://joomla.stackexchange.com/users/' . $item->joomlastackexchange, 'joomlastackexchange', 'fa-brands fa-stack-exchange', 'COM_VOLUNTEERS_CONNECT_JOOMLASTACKEXCHANGE'],
            [preg_match('#^https?://.+#i', (string) $item->website), $item->website, 'website', 'fa-solid fa-globe', 'COM_VOLUNTEERS_CONNECT_WEBSITE'],
            [$item->twitter, 'https://twitter.com/' . $item->twitter, 'twitter', 'fa-brands fa-x-twitter', 'COM_VOLUNTEERS_CONNECT_TWITTER'],
            [$item->facebook, 'https://www.facebook.com/' . $item->facebook, 'facebook', 'fa-brands fa-facebook', 'COM_VOLUNTEERS_CONNECT_FACEBOOK'],
            [$item->linkedin, 'https://www.linkedin.com/in/' . $item->linkedin, 'linkedin', 'fa-brands fa-linkedin', 'COM_VOLUNTEERS_CONNECT_LINKEDIN'],
        ];
        ?>
        <div class="volunteer-links d-flex flex-wrap gap-2 mb-3">
            <?php foreach ($links as [$show, $url, $name, $icon, $label]) : ?>
                <?php if ($show) : ?>
                    <a class="btn btn-sm btn-<?php echo $name; ?>" target="_blank" rel="noopener noreferrer" href="<?php echo $this->escape($url); ?>">
                        <span class="<?php echo $icon; ?>" aria-hidden="true"></span>
                        <?php echo Text::_($label); ?>
                    </a>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<br>

<div class="row">
    <div class="col-12">
        <?php
        echo HTMLHelper::_('uitab.startTabSet', 'volunteerTab', ['active' => 'viewteamsinvolved', 'recall' => true, 'breakpoint' => 768]);
        if ($this->item->teams->active) {
            echo HTMLHelper::_('uitab.addTab', 'volunteerTab', 'viewteamsinvolved', Text::_('COM_VOLUNTEERS_TAB_TEAMSINVOLVED'));
            ?>
            <table class="table table-striped table-hover table-vertical-align">
                <thead>
                    <th width="30%">
                        <?php echo Text::_('COM_VOLUNTEERS_FIELD_TEAM') ?>
                    </th>
                    <th width="20%">
                        <?php echo Text::_('COM_VOLUNTEERS_FIELD_POSITION') ?>
                    </th>
                    <th>
                        <?php echo Text::_('COM_VOLUNTEERS_FIELD_ROLE') ?>
                    </th>
                    <th width="12%">
                        <?php echo Text::_('COM_VOLUNTEERS_FIELD_DATE_STARTED') ?>
                    </th>
                </thead>
                <tbody>
                    <?php foreach ($this->item->teams->active as $team) { ?>
                        <tr>
                            <td>
                                <a href="<?php echo $team->link; ?>"><?php echo $team->name; ?></a>
                            </td>
                            <td>
                                <?php echo ($team->position_title) ?>
                            </td>
                            <td>
                                <?php echo ($team->role_title) ?>
                            </td>
                            <td>
                                <?php echo VolunteersHelper::date($team->date_started, 'M Y'); ?>
                            </td>
                        </tr>
                    <?php } ?>
                </tbody>
            </table>
            <?php
            echo HTMLHelper::_('uitab.endTab');
        }
        if ($this->item->teams->honorroll) {
            echo HTMLHelper::_('uitab.addTab', 'volunteerTab', 'viewhonorroll', Text::_('COM_VOLUNTEERS_TAB_HONORROLL'));
            ?>
            <table class="table table-striped table-hover table-vertical-align">
                <thead>
                    <th width="30%">
                        <?php echo Text::_('COM_VOLUNTEERS_FIELD_TEAM') ?>
                    </th>
                    <th width="20%">
                        <?php echo Text::_('COM_VOLUNTEERS_FIELD_POSITION') ?>
                    </th>
                    <th>
                        <?php echo Text::_('COM_VOLUNTEERS_FIELD_ROLE') ?>
                    </th>
                    <th width="12%">
                        <?php echo Text::_('COM_VOLUNTEERS_FIELD_DATE_STARTED') ?>
                    </th>
                    <th width="12%">
                        <?php echo Text::_('COM_VOLUNTEERS_FIELD_DATE_ENDED') ?>
                    </th>
                </thead>
                <tbody>
                    <?php foreach ($this->item->teams->honorroll as $team) { ?>
                        <tr>
                            <td>
                                <a href="<?php echo $team->link; ?>"><?php echo $team->name; ?></a>
                            </td>
                            <td>
                                <?php echo ($team->position_title) ?>
                            </td>
                            <td>
                                <?php echo ($team->role_title) ?>
                            </td>
                            <td>
                                <?php echo VolunteersHelper::date($team->date_started, 'M Y'); ?>
                            </td>
                            <td>
                                <?php echo VolunteersHelper::date($team->date_ended, 'M Y'); ?>
                            </td>
                        </tr>
                    <?php } ?>
                </tbody>
            </table>
            <?php
            echo HTMLHelper::_('uitab.endTab');
        }
        if ($this->item->joomlastory) {
            echo HTMLHelper::_('uitab.addTab', 'volunteerTab', 'viewjoomlastory', Text::_('COM_VOLUNTEERS_TAB_JOOMLASTORY'));
            echo (nl2br((string) $this->item->joomlastory));
            echo HTMLHelper::_('uitab.endTab');
        }

        if ($this->user->id != $this->item->user_id) {
            echo HTMLHelper::_('uitab.addTab', 'volunteerTab', 'viewcontact', Text::_('COM_VOLUNTEERS_TAB_CONTACT'));

            if ($this->user->guest) { ?>
                <p class="alert alert-info">
                    <?php echo Text::_('COM_VOLUNTEERS_NOTE_LOGIN_CONTACT_VOLUNTEER') ?>
                </p>
                <?php
            } else { ?>
                <form class="form" name="sendmail" action="<?php echo Route::_('index.php') ?>" method="post"
                    enctype="multipart/form-data">
                    <div class="mb-3">
                        <label class="form-label" for="to_name">
                            <?php echo Text::_('COM_VOLUNTEERS_MESSAGE_TO') ?>
                        </label>
                        <div>
                            <input type="text" name="to_name" id="to_name"
                                value="<?php echo $this->escape($this->item->name); ?>" class="form-control"
                                disabled="disabled" />
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="from_name">
                            <?php echo Text::_('COM_VOLUNTEERS_MESSAGE_FROM') ?>
                        </label>
                        <div>
                            <input type="text" name="from_name" id="from_name"
                                value="<?php echo $this->escape($this->user->name); ?> <<?php echo $this->escape($this->user->email); ?>>"
                                class="form-control" disabled="disabled" />
                        </div>
                    </div>
                    <div class="mb-3">
                        <div>
                            <input type="text" name="subject" id="subject" class="form-control"
                                placeholder="<?php echo Text::_('COM_VOLUNTEERS_MESSAGE_SUBJECT') ?>" required />
                        </div>
                    </div>
                    <div class="mb-3">
                        <textarea rows="10" name="message" id="message" class="form-control"
                            placeholder="<?php echo Text::sprintf('COM_VOLUNTEERS_MESSAGE_BODY', $this->escape($this->item->name)) ?>"
                            required></textarea>
                    </div>
                    <div class="alert alert-info">
                        <?php echo Text::sprintf('COM_VOLUNTEERS_MESSAGE_NOTICE', $this->escape($this->item->name)) ?>
                    </div>
                    <div class="mb-3">
                        <input type="submit" value="<?php echo Text::_('COM_VOLUNTEERS_MESSAGE_SUBMIT') ?>" name="submit"
                            id="submitButton" class="btn btn-success float-end" />
                    </div>

                    <input type="hidden" name="option" value="com_volunteers" />
                    <input type="hidden" name="task" value="volunteer.sendmail" />
                    <?php echo HtmlHelper::_('form.token'); ?>
                </form>
                <a class="btn btn-danger js-reportspam" data-volunteer="<?php echo $this->item->id; ?>"
                    data-success="<?php echo Text::_('COM_VOLUNTEERS_SPAM_REPORT_SUCCESS') ?>">
                    <span class="icon-warning" aria-hidden="true"></span>
                    <?php echo Text::_('COM_VOLUNTEERS_SPAM_REPORT') ?>
                </a>
            <?php }

            echo HTMLHelper::_('uitab.endTab');
        } ?>

        <?php echo HTMLHelper::_('uitab.endTabSet'); ?>
    </div>
</div>
