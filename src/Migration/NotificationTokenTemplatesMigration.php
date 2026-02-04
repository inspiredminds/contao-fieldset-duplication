<?php

declare(strict_types=1);

/*
 * (c) INSPIRED MINDS
 */

namespace InspiredMinds\ContaoFieldsetDuplication\Migration;

use Contao\CoreBundle\Migration\AbstractMigration;
use Contao\CoreBundle\Migration\MigrationResult;
use Contao\StringUtil;
use Doctrine\DBAL\Connection;

class NotificationTokenTemplatesMigration extends AbstractMigration
{
    public function __construct(private Connection $db)
    {
    }

    public function shouldRun(): bool
    {
        $schemaManager = $this->db->createSchemaManager();

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
                    ++$key;
                }

                $templates[$key] = $template;
            }

            $this->db->update('tl_form_field', ['notificationTokenTemplates' => serialize($templates)], ['id' => $field['id']]);
        }

        return $this->createResult(true);
    }
}
