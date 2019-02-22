(function($)
{
    "use strict";

    $.fn.fieldsetDuplication = function(settings)
    {
        var defaults = { 
            prepend: false,
            buttonAdd: '+',
            buttonRemove: '&times;'
        };
        var options  = $.extend({}, defaults, settings);

        $(this).each(function(i, e)
        {
            var $original = $(this);
            var $fieldsets = null;
            var selector = null;

            // determine the fieldset group selector
            var classList = $original.attr('class').split(/\s+/);
            $.each(classList, function(index, item)
            {
                if (item.match(/duplicate-fieldset-/))
                {
                    selector = '.' + item;
                    return false;
                }
            });

            var updateFieldsets = function()
            {
                $fieldsets = $(selector);
                $fieldsets.each(function(i, e)
                {
                    var $fieldset = $(this);
                    $fieldset.removeClass('last');
                    $fieldset.removeClass('first');
                    if (i == 0) $fieldset.addClass('first');
                    if (i == $fieldsets.length - 1) $fieldset.addClass('last');
                    addButtons($fieldset);
                    if ($fieldset.hasClass('duplicate'))
                        $fieldset.find('.duplication-buttons button.duplication-button--remove').show();
                    else
                        $fieldset.find('.duplication-buttons button.duplication-button--remove').hide();
                });
            };

            var cloneFieldset = function($fieldset)
            {
                if ($fieldsets.length >= 50)
                {
                    return;
                }

                // clone the fieldset
                var $clone = $fieldset.clone();

                // process input fields
                $clone.find('input[name], select[name], textarea[name]').each(function() {
                    var $input = $(this);
                    $input.removeClass('error');
                    var duplicateIndex = $fieldsets.length + 1;

                    var oldId = $input.attr('id');
                    if (typeof oldId !== 'undefined') {
                        var isDuplicate = oldId.indexOf('_duplicate_');
                        if (isDuplicate >= 0) {
                            oldId = oldId.substr(0, isDuplicate);
                        }
                        var newId = oldId + '_duplicate_' + duplicateIndex;

                        $input.closest('.widget').find('label[for="'+$input.attr('id')+'"]').each(function() {
                            var $label = $(this);
                            $label.attr('for', newId);

                            if (typeof $label.attr('id') !== 'undefined') {
                                $label.attr('id', $label.attr('id') + '_duplicate_' + duplicateIndex);
                            }
                        });

                        $input.attr('id', newId);
                    }

                    var oldName = $input.attr('name');
                    if (typeof oldName !== 'undefined') {
                        var isDuplicate = oldName.indexOf('_duplicate_');
                        if (isDuplicate >= 0) {
                            oldName = oldName.substr(0, isDuplicate);
                        }
                        var newName = oldName + '_duplicate_' + duplicateIndex;
                        $input.attr('name', newName);
                    }

                    if ($input.val() && $input.val() !== $input.attr('value')) {
                        $input.val('');
                    }

                    $input.not('[checked]').prop('checked', false);
                });

                // remove some other stuff
                $clone.find('label').removeClass('error');
                $clone.find('p.error').remove();
                $clone.find('.widget').removeClass('error');

                // set as duplicate
                $clone.addClass('duplicate');

                // assign the button actions
                buttonActions($clone);

                // insert after fieldset
                $fieldset.after($clone);

                // update the fieldset list
                updateFieldsets();

                // trigger event
                $(document).trigger('fieldset-cloned', [$clone]);
            };

            var removeFieldset = function($fieldset)
            {
                if ($fieldsets.length > 1 && $fieldset.hasClass('duplicate'))
                {
                    $fieldset.remove();
                    updateFieldsets();
                }
            }

            var buttonActions = function($fieldset)
            {
                $fieldset.find('.duplication-buttons button.duplication-button--add').off('click').on('click', function(e)
                {
                    e.preventDefault();
                    cloneFieldset($(this).closest('fieldset'));
                    return false;
                });

                if ($fieldset.hasClass('duplicate'))
                {
                    $fieldset.find('.duplication-buttons button.duplication-button--remove').off('click').on('click', function(e)
                    {
                        e.preventDefault();
                        removeFieldset($(this).closest('fieldset'));
                        return false;
                    });
                }
            };

            var addButtons = function($fieldset)
            {
                $fieldset.find('.duplication-buttons').remove();

                // generate the button container
                var $buttons = $('<div class="duplication-buttons"></div>');

                // generate the add button
                var $add = $('<button type="button" class="duplication-button duplication-button--add">' + options.buttonAdd + '</button>');
                $buttons.append($add);

                // generate the remove button
                var $remove = $('<button type="button" class="duplication-button duplication-button--remove">' + options.buttonRemove + '</button>');
                $buttons.append($remove);

                // append or prepend the buttons
                if (options.prepend)
                {
                    $fieldset.prepend($buttons);
                }
                else
                {
                    $fieldset.append($buttons);
                }

                // set the button actions
                buttonActions($fieldset);
            }

            // update the fieldset list
            updateFieldsets();
        });

        return this;
    };

})(jQuery);
