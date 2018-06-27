(function($) {

    $(document).ready(function() {
        var $upload = $('.upload-profile-container');

        // Upload Submit
        $('.upload-profile-submit', $upload).click(function(e) {
            var v = $('input[type="file"]', $upload).val() || $('input[type="text"]', $upload).val();
            
            e.preventDefault();
            
            if (!v) {
                return false;
            }
            
            $('input[name="task"]').val('profiles.import');
            $('form').submit();
        });

        $('input[type="file"]', $upload).change(function() {
            var file = this.value;

            if (file) {
                file = file.replace(/\\/g, '/');
                file = file.substring(file.lastIndexOf('/') + 1);
            }

            $('input[type="text"]', $upload).val(file);
        });

        // Upload clear
        $('.close', $upload).click(function() {
            $('input', '.upload-profile-container').val('');
        });
    });
})(jQuery);