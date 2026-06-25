(function ($) {
    'use strict';

    function toggleMailerConfig() {
        var selected = $('input[name="wmp_mailer"]:checked').val();
        $('.wmp-mailer-config').hide();
        $('.wmp-mailer-config[data-mailer="' + selected + '"]').show();
        $('.wmp-mailer-tile').removeClass('is-active');
        $('input[name="wmp_mailer"]:checked').closest('.wmp-mailer-tile').addClass('is-active');
    }

    $(document).ready(function () {
        $('input[name="wmp_mailer"]').on('change', toggleMailerConfig);
        toggleMailerConfig();

        setTimeout(function () {
            $('.notice.is-dismissible').fadeOut(600);
        }, 5000);
    });

}(jQuery));
