<?php

declare(strict_types=1);

/*
 * This file is part of the inspiredminds/contao-fieldset-duplication package.
 *
 * (c) inspiredminds
 *
 * @license LGPL-3.0-or-later
 */

namespace InspiredMinds\ContaoFieldsetDuplication\Migration;

use Contao\CoreBundle\Migration\AbstractMigration;
use Contao\CoreBundle\Migration\MigrationResult;
use Contao\StringUtil;
use Doctrine\DBAL\Connection;

class NotificationTokenTemplatesMigration extends AbstractMigration
{
    private $db;

    public function __construct(Connection $db)
    {
        $this->db = $db;
    }

    public function shouldRun(): bool
    {
        $schemaManager = $this->db->getSchemaManager();

        if (!$schemaManager->tablesExist(['tl_form_field'])) {
            return false;
        }

        $columns = $schemaManager->listTableColumns('tl_form_field');

        if (!isset($columns['notificationtokentemplates'])) {
            return false;
        }

        return (int) $this->db->fetchOne("SELECT COUNT(*) FROM tl_form_field WHERE notificationTokenTemplates LIKE 'a:1:{i:0;%'") > 0;
    }

    public function run(): MigrationResult
    {
        foreach ($this->db->fetchAllAssociative("SELECT * FROM tl_form_field WHERE notificationTokenTemplates LIKE 'a:1:{i:0;%'") as $field) {
            $templates = [];

            foreach (StringUtil::deserialize($field['notificationTokenTemplates'], true) as $key => $template) {
                if (is_numeric($key)) {
                    $key = $key + 1;
                }

                $templates[$key] = $template;
            }

            $this->db->update('tl_form_field', ['notificationTokenTemplates' => serialize($templates)], ['id' => $field['id']]);
        }

        return $this->createResult(true);
    }
}
