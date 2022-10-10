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

use Contao\Config;
use Contao\Database;
use Contao\Form;
use Contao\FormFieldModel;
use Contao\FrontendTemplate;
use Contao\StringUtil;
use Contao\Widget;
use InspiredMinds\ContaoFieldsetDuplication\Helper\FieldHelper;
use MPFormsFormManager;
use Symfony\Component\HttpFoundation\RequestStack;

class FormHookListener
{
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
            $arrClasses = !empty($widget->class) ? explode(' ', $widget->class) : [];
            $arrClasses[] = 'allow-duplication';
            $arrClasses[] = 'duplicate-fieldset-'.$widget->id;

            if (!empty($widget->maxDuplicationRows)) {
                $arrClasses[] = 'duplicate-fieldset-maxRows-'.$widget->maxDuplicationRows;
            }

            if (!empty($widget->doNotCopyExistingValues)) {
                $arrClasses[] = 'duplicate-fieldset-donotcopy';
            }

            $widget->class = implode(' ', $arrClasses);
        }

        return $widget;
    }

    public function onCompileFormFields(array $fields, $formId, Form $objForm): array
    {
        static $alreadyProcessed = false;

        // Ensure the listener is called only once (e.g. in combination with MPForms)
        if ($alreadyProcessed) {
            return $fields;
        }

        $alreadyProcessed = true;
        $submittedData = [];

        // Get the submitted data from the request
        if (($request = $this->requestStack->getCurrentRequest()) !== null) {
            $submittedData = $request->request->all();
        }

        // Get the submitted data from MPForms
        if (count($submittedData) === 0 && class_exists(\MPFormsFormManager::class)) {
            $manager = new MPFormsFormManager($objForm->id);
            $submittedData = $manager->getDataOfStep($manager->getCurrentStep())['originalPostData'] ?? [];
        }

        // check if form was submitted
        if (($submittedData['FORM_SUBMIT'] ?? null) === $formId) {
            $fieldsetGroups = $this->buildFieldsetGroups($fields);

            $processed = [];
            $fieldsetDuplicates = [];

            // search for duplicates
            foreach (array_keys($submittedData) as $duplicateName) {
                // check if already processed
                if (\in_array($duplicateName, $processed, true)) {
                    continue;
                }

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

                                $duplicatedFields = [];

                                foreach ($fieldsetGroup as $field) {
                                    // set the actual duplicate name
                                    $duplicateName = $field->name.'_duplicate_'.$duplicateNumber;

                                    // clone the field
                                    $clone = clone $field;

                                    // remove allow duplication class
                                    if ($this->fieldHelper->isFieldsetStart($clone)) {
                                        $clone->class = implode(' ', array_diff(explode(' ', $clone->class), ['allow-duplication']));
                                        $clone->class .= ($clone->class ? ' ' : '').'duplicate-fieldset-'.$field->id.' duplicate';
                                    }

                                    // set the id
                                    $clone->id = $field->id.'_duplicate_'.$duplicateNumber;

                                    // set the original id
                                    $clone->originalId = $field->id;

                                    // set the name
                                    $clone->name = $duplicateName;

                                    // set the sorting
                                    $clone->sorting = ++$sorting;

                                    // add the clone
                                    $duplicatedFields[] = $clone;

                                    // add to processed
                                    $processed[] = $duplicateName;
                                }

                                $fieldsetDuplicates[] = $duplicatedFields;

                                break 2;
                            }
                        }
                    }
                }
            }

            // reverse the fieldset duplicates
            $fieldsetDuplicates = array_reverse($fieldsetDuplicates);

            // process $fields
            $fields = array_values($fields);

            // go through the duplicated fieldsets
            foreach ($fieldsetDuplicates as $duplicatedFieldset) {
                // search for the stop field
                $stopId = null;
                foreach ($duplicatedFieldset as $duplicatedField) {
                    if ($this->fieldHelper->isFieldsetStop($duplicatedField)) {
                        $stopId = $duplicatedField->originalId;
                        break;
                    }
                }

                // search for the index position of the original stop field
                if (null !== $stopId) {
                    $stopIdx = null;
                    for ($i = 0; $i < \count($fields); ++$i) {
                        if ($fields[$i]->id === $stopId) {
                            $stopIdx = $i;
                            break;
                        }
                    }

                    // insert fields after original stop field
                    if (null !== $stopIdx) {
                        array_splice($fields, $stopIdx + 1, 0, $duplicatedFieldset);
                    }
                }
            }
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

    public function onPrepareFormData(array &$submittedData, array $labels, array $fields, Form $form): void
    {
        $fieldsetGroups = $this->buildFieldsetGroups($fields);
        $values = $this->groupFieldsetValues($fieldsetGroups, $submittedData);

        // Disable debug mode so that no html comments are rendered in the templates
        $debugMode = Config::get('debugMode');
        Config::set('debugMode', false);

        foreach ($values as $row) {
            if (!$row['config']->allowDuplication) {
                continue;
            }

            $templateFormats = StringUtil::deserialize($row['config']->notificationTokenTemplates, true);
            foreach ($templateFormats as $format) {
                if (!$format['format'] || !$format['template']) {
                    continue;
                }

                $template = new FrontendTemplate($format['template']);
                $template->setData(
                    [
                        'labels' => $labels,
                        'form' => $form,
                        'config' => $row['config'],
                        'values' => $row['data'],
                    ]
                );

                $submittedData[$row['config']->name.'_'.$format['format']] = $template->parse();
            }
        }

        Config::set('debugMode', $debugMode);
    }

    /**
     * @param array|Widget[]|FormFieldModel[] $fields
     */
    private function buildFieldsetGroups(array $fields): array
    {
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

        return $fieldsetGroups;
    }

    private function groupFieldsetValues(array $fieldsetGroups, array $submittedData): array
    {
        $data = [];

        foreach ($fieldsetGroups as $fieldsetId => $fieldsetGroup) {
            $row = [];
            $referenceGroup = $fieldsetGroup[0]->originalId > 0
                ? $fieldsetGroups[$fieldsetGroup[0]->originalId]
                : $fieldsetGroup;

            foreach ($fieldsetGroup as $formFieldIndex => $formFieldModel) {
                if (\array_key_exists($formFieldModel->name, $submittedData)) {
                    $row[$referenceGroup[$formFieldIndex]->name] = $submittedData[$formFieldModel->name];
                }
            }

            $data[$referenceGroup[0]->id]['config'] = $referenceGroup[0];
            $data[$referenceGroup[0]->id]['data'][] = $row;
        }

        return $data;
    }
}
