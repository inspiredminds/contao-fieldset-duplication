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

use Codefog\HasteBundle\Formatter;
use Contao\CoreBundle\ServiceAnnotation\Hook;
use Doctrine\DBAL\Connection;
use Haste\Util\Format;
use InspiredMinds\ContaoFieldsetDuplication\Helper\FieldHelper;
use Psr\Container\ContainerInterface;
use Symfony\Contracts\Service\ServiceSubscriberInterface;
use Terminal42\LeadsBundle\Terminal42LeadsBundle;

class LeadsListener implements ServiceSubscriberInterface
{
    private Connection         $connection;
    private FieldHelper        $fieldHelper;
    private ContainerInterface $container;

    public function __construct(ContainerInterface $container, Connection $connection, FieldHelper $fieldHelper)
    {
        $this->connection = $connection;
        $this->fieldHelper = $fieldHelper;
        $this->container = $container;
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
     * @Hook("processFormData")
     */
    public function onProcessFormData(array $postData, array $formConfig, $files): void
    {
        if (!class_exists(Terminal42LeadsBundle::class) || !$formConfig['leadEnabled']) {
            return;
        }

        $result = $this->connection->createQueryBuilder()->select('id')->from('tl_lead')
            ->where('form_id=:form_id')
            ->andWhere('tstamp>:tstamp')
            ->andWhere('post_data=:post_data')
            ->setParameter('form_id', $formConfig['id'])
            ->setParameter('tstamp', time() - 60)
            ->setParameter('post_data', serialize($postData))
            ->executeQuery()->fetchOne();

        if (false !== $result) {
            $this->storeDublicateFields($formConfig, $postData, $result, 3);
        }
    }

    /**
     * @Hook("storeLeadsData")
     */
    public function onStoreLeadsData(array $arrPost, array $form, array $arrFiles = null, int $intLead): void
    {
        $this->storeDublicateFields($form, $arrPost, $intLead);
    }

    private function storeDublicateFields(array $form, array $postData, int $leadId, int $leadsVersion = 1): void
    {
        $mainIdFieldName = 'master_id';
        $mainFieldName = 'leadMaster';

        if ($leadsVersion === 3) {
            $mainIdFieldName = 'main_id';
            $mainFieldName = 'leadMain';
        }

        // Fetch master form fields
        if ($form[$mainFieldName] > 0) {
            $leadFields = $this->connection->fetchAllAssociative(
                "SELECT f2.*, f1.id AS ".$mainIdFieldName.", f1.name AS postName FROM tl_form_field f1 LEFT JOIN tl_form_field f2 ON f1.leadStore=f2.id WHERE f1.pid=? AND f1.leadStore>0 AND (f2.leadStore=? OR f2.type=? OR f2.type=?) AND f1.invisible=? ORDER BY f2.sorting",
                [$form['id'], 1, 'fieldsetStart', 'fieldsetStop', '']
            );
        } else {
            $leadFields = $this->connection->fetchAllAssociative(
                "SELECT *, id AS ".$mainIdFieldName.", name AS postName FROM tl_form_field WHERE pid=? AND (leadStore=? OR type=? OR type=?) AND invisible=? ORDER BY sorting",
                [$form['id'], 1, 'fieldsetStart', 'fieldsetStop', '']
            );
        }


        $time = time();

        foreach ($this->getDuplicateFields($leadFields) as $fieldset) {
            $fieldsetFields = [];

            // Collect the fields
            foreach ($fieldset['fields'] as $field) {
                foreach ($postData as $name => $value) {
                    if (preg_match('/^(' . preg_quote($field['name']) . ')(_duplicate_(\d+))?$/', $name, $matches)) {
                        $index = (int)($matches[3] ?? 0) + 1;
                        if (class_exists(Formatter::class) && $this->container->has(Formatter::class)) {
                            $fieldLabel = $this->container->get(Formatter::class)->dcaLabelFromArray($field);
                            $fieldValue = $this->container->get(Formatter::class)->dcaValueFromArray($field, $value);
                        } elseif (class_exists(Format::class)) {
                            $fieldLabel = Format::dcaLabelFromArray($field);
                            $fieldValue = Format::dcaValueFromArray($field, $value);
                        } else {
                            continue;
                        }

                        $fieldsetFields[$index][$field['name']] = [
                            'label' => $fieldLabel,
                            'value' => $fieldValue,
                            'raw'   => $value,
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
                    'pid'           => $leadId,
                    'sorting'       => $fieldset['fieldset']['sorting'],
                    'tstamp'        => $time,
                    $mainIdFieldName       => $fieldset['fieldset'][$mainIdFieldName],
                    'field_id'      => $fieldset['fieldset']['id'],
                    'name'          => $fieldset['fieldset']['name'],
                    'value'         => serialize($label),
                    'label'         => implode("\n", $label),
                    'fieldset_data' => serialize($fieldsetFields),
                ]);
            }

            // Remove the original fields that were duplicated
            foreach ($fieldset['fields'] as $field) {
                $this->connection->delete('tl_lead_data', ['pid' => $leadId, 'name' => $field['name']]);
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
                if ($this->fieldHelper->isFieldsetStart((object) $field) && $field['allowDuplication'] && $field['leadStore']) {
                    $fieldsetGroup = $field['name'];

                    $duplicateFields[$fieldsetGroup] = [
                        'fieldset' => $field,
                        'fields' => [],
                    ];

                    continue;
                }

                if ($this->fieldHelper->isFieldsetStop((object) $field)) {
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

    public static function getSubscribedServices()
    {
        $services = [];

        if (class_exists(Formatter::class)) {
            $services[Formatter::class] = '?'.Formatter::class;
        }

        return $services;
    }
}
