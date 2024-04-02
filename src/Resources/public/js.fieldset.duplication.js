(function() {
    "use strict";

    function fieldsetDuplication(element, settings) {
        var defaults = {
            prepend: false,
            buttonAdd: '+',
            buttonRemove: '\u00D7',
            widgetSelector: '.widget',
        };

        var options = Object.assign({}, defaults, settings, JSON.parse(element.dataset.fieldsetDuplicationConfig || '{}'));
    
        var original = element;
        var fieldsets = null;
        var selector = null;
        var maxRows = null;
        var duplicateIndex = 0;

        // Determine the fieldset group selector
        var classList = original.className.split(/\s+/);
        classList.some(function(item) {
            if (item.match(/duplicate-fieldset-/)) {
                selector = '.' + item;
                return true;
            }
        });

        // Determine the max rows configuration
        classList.some(function(item) {
            if (item.match(/duplicate-fieldset-maxRows-/)) {
                maxRows = item.substring("duplicate-fieldset-maxRows-".length);
                return true;
            }
        });

        // Determine the current duplicate index
        var lastDuplicateField = document.querySelector(selector + ' [name*="_duplicate_"]:last-child');

        if (lastDuplicateField) {
            duplicateIndex = parseInt(lastDuplicateField.getAttribute('name').slice(-1), 10);
        }

        function updateFieldsets() {
            fieldsets = document.querySelectorAll(selector);
            fieldsets.forEach(function(fieldset, i) {
                fieldset.classList.remove('last', 'first');
                if (i === 0) fieldset.classList.add('first');
                if (i === fieldsets.length - 1) fieldset.classList.add('last');
                addButtons(fieldset);
                if (fieldset.classList.contains('duplicate')) {
                    fieldset.querySelector('.duplication-buttons button.duplication-button--remove').style.display = 'block';
                } else {
                    fieldset.querySelector('.duplication-buttons button.duplication-button--remove').style.display = 'none';
                }
            });
        }

        function cloneFieldset(fieldset) {
            if (maxRows !== null && fieldsets.length >= maxRows) {
                // Trigger event
                document.dispatchEvent(new Event('fieldset-clone-rejected'));
                return;
            }

            var clone = fieldset.cloneNode(true);

            duplicateIndex++;

            var nameMap = {};

            // Process input fields
            clone.querySelectorAll('input[name], select[name], textarea[name]').forEach(function(input) {
                input.classList.remove('error');

                var oldId = input.id;
                if (typeof oldId !== 'undefined') {
                    var isDuplicate = oldId.indexOf('_duplicate_');
                    if (isDuplicate >= 0) {
                        oldId = oldId.substr(0, isDuplicate);
                    }
                    var newId = oldId + '_duplicate_' + duplicateIndex;

                    // Search for the widget parent
                    var closestFieldset = input.closest(options.widgetSelector);

                    // Search for all labels within the widget
                    var labels = closestFieldset.querySelectorAll('label[for="'+ input.id +'"]');

                    // Iterate over each label
                    labels.forEach(function(label) {
                        // Set the `for` attribute to the new ID
                        label.setAttribute('for', newId);

                        // Check if the label has an ID attribute and update it
                        if (typeof label.id !== 'undefined') {
                            label.id = label.id + '_duplicate_' + duplicateIndex;
                        }
                    });

                    input.id = newId;
                }

                var oldName = input.name;
                if (typeof oldName !== 'undefined') {
                    var isArray = oldName.endsWith('[]');
                    if (isArray) {
                        oldName = oldName.substring(0, oldName.length - 2);
                    }
                    var isDuplicate = oldName.indexOf('_duplicate_');
                    if (isDuplicate >= 0) {
                        oldName = oldName.substr(0, isDuplicate);
                    }
                    var newName = oldName + '_duplicate_' + duplicateIndex;
                    if (isArray) {
                        newName += '[]';
                    }
                    input.name = newName;

                    nameMap[oldName] = newName;
                }

                var value = input.getAttribute('value');

                if (input.type !== 'checkbox' && input.type !== 'radio') {
                    if (input.value && fieldset.classList.contains('duplicate-fieldset-donotcopy')) {
                        input.value = value;
                    }
                } else {
                    input.checked = false;
                }
            });

            // Process cff fieldsets
            clone.querySelectorAll('fieldset[data-cff-condition]').forEach(function(e) {
                let condition = e.dataset.cffCondition;

                for (const [key, value] of Object.entries(nameMap)) {
                    condition = condition.replaceAll(key, value);
                }

                e.dataset.cffCondition = condition;
            });

            // Remove some other stuff
            clone.querySelectorAll('label').forEach(function(label) {
                label.classList.remove('error');
            });
            clone.querySelectorAll('p.error').forEach(function(p) {
                p.remove();
            });
            clone.querySelectorAll(options.widgetSelector).forEach(function(widget) {
                widget.classList.remove('error');
            });

            // Set as duplicate
            clone.classList.add('duplicate');

            // Assign the button actions
            buttonActions(clone);

            // Insert after fieldset
            fieldset.after(clone);

            // Update the fieldset list
            updateFieldsets();

            // Disable the 'add' button if no additional row is allowed
            if (maxRows !== null && fieldsets.length >= maxRows) {
                fieldsets.forEach(function(fieldset) {
                    fieldset.querySelector('.duplication-button--add').classList.add('disabled');
                    fieldset.querySelector('.duplication-button--add').setAttribute('disabled', 'disabled');
                });
            }

            // Trigger event
            document.dispatchEvent(new Event('fieldset-cloned'));
        }

        function removeFieldset(fieldset) {
            if (fieldsets.length > 1 && fieldset.classList.contains('duplicate')) {
                fieldset.remove();
                updateFieldsets();
            }
        }

        function buttonActions(fieldset) {
            fieldset.querySelector('.duplication-buttons button.duplication-button--add').addEventListener('click', function(e) {
                e.preventDefault();
                cloneFieldset(fieldset);
                return false;
            });

            if (fieldset.classList.contains('duplicate')) {
                fieldset.querySelector('.duplication-buttons button.duplication-button--remove').addEventListener('click', function(e) {
                    e.preventDefault();
                    removeFieldset(fieldset);
                    return false;
                });
            }
        }

        function addButtons(fieldset) {
            var buttonsContainer = fieldset.querySelector('.duplication-buttons');
            if (buttonsContainer) {
                buttonsContainer.remove();
            }

            // Generate the button container
            var buttonsContainer = document.createElement('div');
            buttonsContainer.classList.add('duplication-buttons');

            // Generate the add button
            var addButton = document.createElement('button');
            addButton.type = 'button';
            addButton.classList.add('duplication-button', 'duplication-button--add');
            addButton.textContent = options.buttonAdd;
            buttonsContainer.appendChild(addButton);

            // Generate the remove button
            var removeButton = document.createElement('button');
            removeButton.type = 'button';
            removeButton.classList.add('duplication-button', 'duplication-button--remove');
            removeButton.textContent = options.buttonRemove;
            buttonsContainer.appendChild(removeButton);

            // Append or prepend the buttons
            if (options.prepend) {
                fieldset.insertBefore(buttonsContainer, fieldset.firstChild);
            } else {
                fieldset.appendChild(buttonsContainer);
            }

            // Set the button actions
            buttonActions(fieldset);
        }

        // Update the fieldset list
        updateFieldsets();
    }

    window.fieldsetDuplication = fieldsetDuplication;
})();
