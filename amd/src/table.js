define(["jquery"], function($) {
    return {
        init: function() {
            // Adapted to JQuery from mod/assign/module.js
            var selectall = $('th.c0 input');
            var checkboxes = $('td.c0 input[type="checkbox"]');
            if (selectall) {
                selectall.change(function() {
                    if (this.checked) {
                        checkboxes.each(function() {
                            $(this).prop('checked', true);
                            $(this).trigger("change");
                        });
                    } else {
                        checkboxes.each(function() {
                            $(this).prop('checked', false);
                            $(this).trigger("change");
                        });
                    }
                });
            }
            var hiddeninput = $('.action_form-selected');
            if (hiddeninput) {
                checkboxes.change(function() {
                    var currentvalues = hiddeninput.val();
                    currentvalues = currentvalues.split(',');
                    if (this.checked && !currentvalues.includes(this.value)) {
                        currentvalues.push(this.value);
                    } else {
                        var indextoremove = currentvalues.indexOf(this.value);
                        currentvalues.splice(indextoremove, 1);
                    }
                    // eslint-disable-next-line no-console
                    console.log(currentvalues);
                    currentvalues = currentvalues.join(',');
                    hiddeninput.val(currentvalues);
                    // eslint-disable-next-line no-console
                    console.log(currentvalues);
                });
            }
        }
    };
});
