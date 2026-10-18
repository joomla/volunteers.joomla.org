const jed = (function () {
    const jed = {};

    jed.searchForm = function () {
        window.addEventListener('DOMContentLoaded', () => {
            let button = document.getElementsByClassName("js-extensionsForm-button-reset")[0];

            button.addEventListener("click", () =>
            {
                const form = document.getElementById("extensionForm");

                [...form.elements].forEach((input) => {
                    if (input.type !== "button") {
                        input.value = '';
                    }
                });
            });
        });
    };

    // Return the public parts
    return jed;
}());