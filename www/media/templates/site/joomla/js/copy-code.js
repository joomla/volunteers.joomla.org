/**
 * Joomla.org site template
 *
 * Adds a copy button to every <pre class="copy-code"> block in the content.
 *
 * @copyright   Copyright (C) 2005 - 2026 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

window.addEventListener('DOMContentLoaded', () => {
    const copyLabel = Joomla.Text._('TPL_JOOMLA_COPY_CODE', 'Copy');
    const copiedLabel = Joomla.Text._('TPL_JOOMLA_COPY_CODE_COPIED', 'Copied');

    document.querySelectorAll('pre.copy-code').forEach((pre) => {
        const button = document.createElement('button');
        button.type = 'button';
        button.className = 'btn btn-sm btn-secondary copy-code-button';
        button.textContent = copyLabel;

        button.addEventListener('click', async () => {
            try {
                await navigator.clipboard.writeText(pre.textContent.trim());
            } catch (error) {
                // The clipboard is not available, select the code so it can be copied by hand
                window.getSelection().selectAllChildren(pre);

                return;
            }

            button.textContent = copiedLabel;
            setTimeout(() => {
                button.textContent = copyLabel;
            }, 2000);
        });

        const wrapper = document.createElement('div');
        wrapper.className = 'copy-code-wrapper';
        pre.before(wrapper);
        wrapper.append(pre, button);
    });
});
