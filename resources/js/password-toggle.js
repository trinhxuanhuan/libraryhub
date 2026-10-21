import $ from 'jquery';

$(function () {
    $('[data-password-toggle]').on('click', function () {
        const inputId = $(this).attr('data-password-toggle');
        const $input = $('#' + inputId);

        const showPassword = $input.attr('type') === 'password';

        $input.attr('type', showPassword ? 'text' : 'password');

        $(this).find('[data-eye-slash]').toggleClass('d-none', showPassword);

        $(this).attr('aria-label', showPassword ? 'Ẩn mật khẩu' : 'Hiện mật khẩu');
        $(this).attr('aria-pressed', showPassword ? 'true' : 'false');
    });
});
