/**
 * @package    Joomla! Volunteers
 * @copyright  Copyright (C) 2016 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

/**
 * Toolbar buttons of the frontend edit forms.
 *
 * A button with a data-task attribute submits its form with that task. Every
 * task except cancel validates the form first and when that fails the page
 * scrolls to the top, so the error message is visible when using the buttons
 * at the bottom of the form.
 */
document.addEventListener('click', (event) => {
    const button = event.target.closest('button[data-task]');

    if (!button || !button.form) {
        return;
    }

    const { form } = button;
    const { task } = button.dataset;

    event.preventDefault();

    if (!task.endsWith('.cancel') && !document.formvalidator.isValid(form)) {
        window.scrollTo({ top: 0 });

        return;
    }

    Joomla.submitform(task, form);
});
