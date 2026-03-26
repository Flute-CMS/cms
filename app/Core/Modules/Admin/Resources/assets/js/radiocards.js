document.addEventListener('change', function (e) {
    if (!e.target.classList.contains('radio-cards__input')) return;

    var group = e.target.closest('.radio-cards');
    if (!group) return;

    group.querySelectorAll('.radio-cards__item').forEach(function (item) {
        item.classList.remove('radio-cards__item--active');
    });

    var label = e.target.closest('.radio-cards__item');
    if (label) {
        label.classList.add('radio-cards__item--active');
    }
});

function initRadioCards() {}
