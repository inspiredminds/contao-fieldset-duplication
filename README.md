[![](https://img.shields.io/packagist/v/inspiredminds/contao-fieldset-duplication.svg)](https://packagist.org/packages/inspiredminds/contao-fieldset-duplication)
[![](https://img.shields.io/packagist/dt/inspiredminds/contao-fieldset-duplication.svg)](https://packagist.org/packages/inspiredminds/contao-fieldset-duplication)

Contao Fieldset Duplication
===================

Contao extension to allow the duplication of form fieldsets in the front end by 
the user for additional input fields.

![Example screenshot of the front end](https://raw.githubusercontent.com/inspiredminds/contao-fieldset-duplication/master/example.png)

You need to enable the `j_fieldset_duplication` template in your page layout. 
The following options can be changed:
```html
<script src="bundles/contaofieldsetduplication/jquery.fieldset.duplication.min.js"></script>
<script>
  (function($){
    $('fieldset.allow-duplication').fieldsetDuplication({
      /* when true, prepends the button wrapper within the fieldset, instead of appending */
      prepend: false,
      /* text content of the add button */
      buttonAdd: '+',
      /* text content of the remove button */
      buttonRemove: '&times;',
      /* a custom widget CSS selector */
      widgetSelector: '.form-widget', // defaults to .widget
    });
  })(jQuery);
</script>
```
If you want to store the additional data in your database table (using the form 
generator's ability to store the data in the database), you need to add a column 
called `fieldset_duplicates` to your target table. This column will then contain 
the additionally submitted fields in a JSON encoded object.

Notification tokens
-------------------

If you need your fieldset rendered as notification tokens, you can define notification token formats. Just define the fieldset name, a format name and select a template. The fieldset will be available at token `form_{NAME}_{FORMAT}` (`{NAME}_{FORMAT}` if you don't use the notification center).

The following templates are shipped with this extension:

* *nc_fieldset_duplication_text*: Renders the fieldset data as `label: value` pairs
* *nc_fieldset_duplication_html*: Renders the fieldset data as html table
* *nc_fieldset_duplication_json*: Renders the fieldset data as json string
