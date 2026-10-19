import $ from 'jquery';

$(function () {
    const $authorCheckboxes = $('input[name="author_ids[]"]');
    const $selectedAuthorsText = $('#selectedAuthorsText');

    if (!$selectedAuthorsText.length) {
        return;
    }

    function updateSelectedAuthors() {
        const count = $authorCheckboxes.filter(':checked').length;

        $selectedAuthorsText.text(
            count > 0
                ? `Đã chọn ${count} tác giả`
                : '-- Chọn tác giả --'
        );
    }

    $authorCheckboxes.on('change', updateSelectedAuthors);

    updateSelectedAuthors();
});
