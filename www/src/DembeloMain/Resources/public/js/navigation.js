var Navigation = function () {
    var isVisible = false;
    var element = document.getElementById('navigation');

    return {
        show: function () {
            isVisible = true;
            element.style.display = 'block';
        },
        hide: function () {
            isVisible = false;
            element.style.display = 'none';
        },
        toggle: function () {
            if (!isVisible) {
                Navigation.show();
                return;
            }
            Navigation.hide();
        }
    };
}();