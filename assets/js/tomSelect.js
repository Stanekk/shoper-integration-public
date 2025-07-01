import TomSelect from 'tom-select';


document.addEventListener('DOMContentLoaded', function () {
    const el = document.querySelector('#importer_exclude_product_status');
    if (el && !el.tomselect) {
        const tomSelectInstance = new TomSelect(el, {
            plugins: {
                remove_button:{
                    title:'Remove',
                }
            },
            persist: false,
            placeholder: 'Select a status',
            searchField: false,
            controlInput: null,

        });
        tomSelectInstance.on('item_add', function(value, item) {
            const removeButton = item.querySelector('.remove');
            if (removeButton) {
                removeButton.innerHTML = '<span class="material-symbols-outlined">delete</span>';
            }
        });

        const items = document.querySelectorAll('.ts-control .item .remove');
        items.forEach(function(removeButton) {
            removeButton.innerHTML = '<span class="material-symbols-outlined">delete</span>';
        });
    }
});