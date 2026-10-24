document.addEventListener('click', (event) => {
    const button = event.target.closest('.sso-copy-button');

    if (!button) {
        return;
    }

    const text = button.dataset.clipboardText;

    navigator.clipboard.writeText(text).then(() => {
        button.classList.add('sso-copy-button--copied');

        window.setTimeout(() => {
            button.classList.remove('sso-copy-button--copied');
        }, 1500);
    });
});
