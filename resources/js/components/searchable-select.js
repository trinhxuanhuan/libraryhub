import TomSelect from 'tom-select/popular';

function initializeSearchableSelects() {
    document.querySelectorAll('select[data-searchable-select]').forEach((element) => {
        if (element.tomselect) {
            return;
        }

        const isMultiple = element.multiple;

        new TomSelect(element, {
            create: false,
            maxItems: isMultiple ? null : 1,
            plugins: isMultiple ? ['remove_button'] : [],
            placeholder: element.dataset.placeholder || 'Tìm kiếm và chọn...',
            searchField: ['text'],
            closeAfterSelect: !isMultiple,
        });
    });
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initializeSearchableSelects);
} else {
    initializeSearchableSelects();
}
