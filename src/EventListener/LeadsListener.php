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

use Contao\CoreBundle\ServiceAnnotation\Hook;
use Contao\Database\Result;
use Doctrine\DBAL\Connection;
use Haste\Util\Format;
use InspiredMinds\ContaoFieldsetDuplication\Helper\FieldHelper;

class LeadsListener
{
    private Connection $connection;
    private FieldHelper $fieldHelper;

    public function __construct(Connection $connection, FieldHelper $fieldHelper)
    {
        $this->connection = $connection;
        $this->fieldHelper = $fieldHelper;
    }

    /**
     * @Hook("loadDataContainer")
     */
    public function onLoadDataContainer(string $table): void
    {
        if ('tl_lead_data' === $table) {
            $GLOBALS['TL_DCA']['tl_lead_data']['fields']['fieldset_data'] = [
                'sql' => ['type' => 'blob', 'notnull' => false],
            ];
        }
    }

    /**
     * @Hook("storeLeadsData")
     */
    public function onStoreLeadsData(array $arrPost, array $arrForm, array $arrFiles = null, int $intLead, Result $objFields): void
    {
        $time = time();

        foreach ($this->getDuplicateFields($objFields->fetchAllAssoc()) as $fieldset) {
            $fieldsetFields = [];

            // Collect the fields
            foreach ($fieldset['fields'] as $field) {
                foreach ($arrPost as $name => $value) {
                    if (preg_match('/^('.preg_quote($field['name']).')(_duplicate_(\d+))?$/', $name, $matches)) {
                        $index = (int) ($matches[3] ?? 0) + 1;
                        $fieldsetFields[$index][$field['name']] = [
                            'label' => Format::dcaLabelFromArray($field),
                            'value' => Format::dcaValueFromArray($field, $value),
                            'raw' => $value,
                        ];
                    }
                }
            }

            $label = [];

            // Generate the label
            foreach ($fieldsetFields as $index => $fields) {
                foreach ($fields as $value) {
                    $label[] = sprintf('%d. %s: %s', $index, $value['label'], $value['value']);
                }
            }

            if (\count($fieldsetFields) > 0) {
                $this->connection->insert('tl_lead_data', [
                    'pid' => $intLead,
                    'sorting' => $fieldset['fieldset']['sorting'],
                    'tstamp' => $time,
                    'master_id' => $fieldset['fieldset']['master_id'],
                    'field_id' => $fieldset['fieldset']['id'],
                    'name' => $fieldset['fieldset']['name'],
                    'value' => serialize($label),
                    'label' => implode("\n", $label),
                    'fieldset_data' => serialize($fieldsetFields),
                ]);
            }

            // Remove the original fields that were duplicated
            foreach ($fieldset['fields'] as $field) {
                $this->connection->delete('tl_lead_data', ['pid' => $intLead, 'name' => $field['name']]);
            }
        }
    }

    public function getDuplicateFields(array $allFields): array
    {
        static $duplicateFields = null;

        if (!\is_array($duplicateFields)) {
            $duplicateFields = [];
            $fieldsetGroup = null;

            foreach ($allFields as $field) {
                if ($this->fieldHelper->isFieldsetStart($field) && $field['allowDuplication']) {
                    $fieldsetGroup = $field['name'];

                    $duplicateFields[$fieldsetGroup] = [
                        'fieldset' => $field,
                        'fields' => [],
                    ];

                    continue;
                }

                if ($this->fieldHelper->isFieldsetStop($field)) {
                    $fieldsetGroup = null;
                    continue;
                }

                if (null !== $fieldsetGroup) {
                    $duplicateFields[$fieldsetGroup]['fields'][] = $field;
                }
            }
        }

        return $duplicateFields;
    }
}
