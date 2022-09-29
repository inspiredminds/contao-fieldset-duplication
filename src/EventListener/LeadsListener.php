<?php

namespace InspiredMinds\ContaoFieldsetDuplication\EventListener;

use Contao\Database\Result;
use Doctrine\DBAL\Connection;
use Haste\Util\Format;

class LeadsListener
{
    private Connection $connection;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

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

            if (count($fieldsetFields) > 0) {
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

        if (!is_array($duplicateFields)) {
            $duplicateFields = [];
            $fieldsetGroup = null;

            foreach ($allFields as $field) {
                if ($field['type'] === 'fieldsetStart' && $field['allowDuplication']) {
                    $fieldsetGroup = $field['name'];

                    $duplicateFields[$fieldsetGroup] = [
                        'fieldset' => $field,
                        'fields' => [],
                    ];

                    continue;
                }

                if ($field['type'] === 'fieldsetStop') {
                    $fieldsetGroup = null;
                    continue;
                }

                if ($fieldsetGroup !== null) {
                    $duplicateFields[$fieldsetGroup]['fields'][] = $field;
                }
            }
        }

        return $duplicateFields;
    }
}
