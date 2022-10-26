(function($)
{
    "use strict";

    $.fn.fieldsetDuplication = function(settings)
    {
        var defaults = {
            prepend: false,
            buttonAdd: '+',
            buttonRemove: '&times;',
            widgetSelector: '.widget',
        };

        $(this).each(function(i, e)
        {
            var $original = $(this);
            var $fieldsets = null;
            var selector = null;
            var maxRows = null;
            var duplicateIndex = 0;
            var options = $.extend({}, defaults, settings, $original.data('fieldset-duplication-config') || {})

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
            // determine the max rows configuration
            $.each(classList, function(index, item)
            {
                if (item.match(/duplicate-fieldset-maxRows-/))
                {
                    maxRows = item.substring("duplicate-fieldset-maxRows-".length);
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
                if (maxRows != null && $fieldsets.length >= maxRows)
                {
                    // trigger event
                    $(document).trigger('fieldset-clone-rejected', [$fieldset, $fieldsets, maxRows]);
                    return;
                }

                // clone the fieldset
                var $clone = $fieldset.clone();

                duplicateIndex++;

                const nameMap = {};

                // process input fields
                $clone.find('input[name], select[name], textarea[name]').each(function() {
                    var $input = $(this);
                    $input.removeClass('error');

                    var oldId = $input.attr('id');
                    if (typeof oldId !== 'undefined') {
                        var isDuplicate = oldId.indexOf('_duplicate_');
                        if (isDuplicate >= 0) {
                            oldId = oldId.substr(0, isDuplicate);
                        }
                        var newId = oldId + '_duplicate_' + duplicateIndex;

                        $input.closest(options.widgetSelector).find('label[for="'+$input.attr('id')+'"]').each(function() {
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

                        nameMap[oldName] = newName;
                    }

                    var value = $input.attr('value');

                    if ($input.attr('type') !== 'checkbox' && $input.attr('type') !== 'radio' ) {
                        if ($input.val() && $fieldset.hasClass('duplicate-fieldset-donotcopy')) {
                            $input.val(value);
                        }
                    } else {
                        $input.not('[checked]').prop('checked', false);
                    }
                });

                // process cff fieldsets
                $clone.find('fieldset[data-cff-condition]').each((i, e) => {
                    let condition = e.dataset.cffCondition;

                    for (const [key, value] of Object.entries(nameMap)) {
                        condition = condition.replaceAll(key, value);
                    }

                    e.dataset.cffCondition = condition;
                });

                // remove some other stuff
                $clone.find('label').removeClass('error');
                $clone.find('p.error').remove();
                $clone.find(options.widgetSelector).removeClass('error');

                // set as duplicate
                $clone.addClass('duplicate');

                // assign the button actions
                buttonActions($clone);

                // insert after fieldset
                $fieldset.after($clone);

                // update the fieldset list
                updateFieldsets();

                // disable the 'add' button if no additional row is allowed
                if (maxRows != null && $fieldsets.length >= maxRows)
                {
                    $fieldsets.each(function(i, e)
                    {
                        var $fieldset = $(this);
                        $fieldset.find('.duplication-button--add').addClass('disabled');
                        $fieldset.find('.duplication-button--add').attr('disabled', 'disabled');
                    });
                }

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
