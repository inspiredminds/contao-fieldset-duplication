<?php

declare(strict_types=1);

/*
 * This file is part of the inspiredminds/contao-fieldset-duplication package.
 *
 * (c) inspiredminds
 *
 * @license LGPL-3.0-or-later
 */

namespace InspiredMinds\ContaoFieldsetDuplication\EventListener;

use Contao\CoreBundle\Framework\FrameworkAwareInterface;
use Contao\CoreBundle\Framework\FrameworkAwareTrait;
use Contao\Database;
use Contao\Form;
use Contao\Widget;
use InspiredMinds\ContaoFieldsetDuplication\Helper\FieldHelper;
use Symfony\Component\HttpFoundation\RequestStack;

class FormHookListener implements FrameworkAwareInterface
{
    use FrameworkAwareTrait;

    public const TABLE_FIELD = 'fieldset_duplicates';

    protected $requestStack;
    protected $fieldHelper;

    public function __construct(RequestStack $requestStack, FieldHelper $fieldHelper)
    {
        $this->requestStack = $requestStack;
        $this->fieldHelper = $fieldHelper;
    }

    public function onLoadFormField(Widget $widget, string $formId, array $data, Form $form): Widget
    {
        if ($this->fieldHelper->isFieldsetStart($widget) && $widget->allowDuplication && false === strpos($widget->name, '_duplicate_')) {
            $widget->class .= ($widget->class ? ' ' : '').'allow-duplication duplicate-fieldset-'.$widget->id;
        }

        return $widget;
    }

    public function onCompileFormFields(array $fields, $formId, Form $objForm): array
    {
        // get the current request
        $request = $this->requestStack->getCurrentRequest();

        // check if form was submitted
        if ($request->request->get('FORM_SUBMIT') === $formId) {
            // field set groups
            $fieldsetGroups = [];

            // field set group
            $fieldsetGroup = [];

            // go through each field
            foreach ($fields as $field) {
                // check if we can process duplicates
                if ($this->fieldHelper->isFieldsetStart($field)) {
                    $fieldsetGroup[] = $field;
                } elseif ($this->fieldHelper->isFieldsetStop($field)) {
                    $fieldsetGroup[] = $field;
                    $fieldsetGroups[$fieldsetGroup[0]->id] = $fieldsetGroup;
                    $fieldsetGroup = [];
                } elseif (!empty($fieldsetGroup)) {
                    $fieldsetGroup[] = $field;
                }
            }

            // search for duplicates
            foreach (array_keys($request->request->all()) as $duplicateName) {
                // check if it is a duplicate
                if (false !== ($intPos = strpos($duplicateName, '_duplicate_'))) {
                    // get the non duplicate name
                    $originalName = substr($duplicateName, 0, $intPos);

                    // get the duplicate number
                    $duplicateNumber = (int) (substr($duplicateName, -1));

                    // clone the fieldset
                    foreach ($fieldsetGroups as $fieldsetGroup) {
                        foreach ($fieldsetGroup as $field) {
                            if ($field->name === $originalName) {
                                // new sorting base number
                                $sorting = $fieldsetGroup[\count($fieldsetGroup) - 1]->sorting;

                                foreach ($fieldsetGroup as $field) {
                                    // set the actual duplicate name
                                    $duplicateName = $field->name.'_duplicate_'.$duplicateNumber;

                                    // clone the field
                                    $clone = clone $field;

                                    // remove allow duplication class
                                    if ($this->fieldHelper->isFieldsetStart($clone)) {
                                        $clone->class = implode(' ', array_diff(explode(' ', $clone->class), ['allow-duplication']));
                                        $clone->class .= 'duplicate-fieldset-'.$field->id.' duplicate';
                                    }

                                    // set the id
                                    $clone->id = $field->id.'_duplicate_'.$duplicateNumber;

                                    // set the name
                                    $clone->name = $duplicateName;

                                    // set the label
                                    $clone->label = $clone->label.' '.$duplicateNumber;

                                    // set the sorting
                                    $clone->sorting = ++$sorting;

                                    // add the clone
                                    $fields[$field->name ? $duplicateName : $sorting] = $clone;
                                }

                                break 3;
                            }
                        }
                    }
                }
            }

            // re-sort
            uasort($fields, function ($a, $b) {
                if ($a->sorting === $b->sorting) {
                    return 0;
                }

                return ($a->sorting < $b->sorting) ? -1 : 1;
            });
        }

        // return the fields
        return $fields;
    }

    public function onStoreFormData(array $set, Form $form): array
    {
        $newSet = [];
        $duplicateFieldsData = [];

        foreach ($set as $name => $value) {
            if (false !== strpos($name, '_duplicate_')) {
                $duplicateFieldsData[$name] = $value;
                continue;
            }

            $newSet[$name] = $value;
        }

        if (!empty($duplicateFieldsData) && Database::getInstance()->fieldExists(self::TABLE_FIELD, $form->targetTable)) {
            $newSet['fieldset_duplicates'] = json_encode($duplicateFieldsData);
        }

        return $newSet;
    }
}
