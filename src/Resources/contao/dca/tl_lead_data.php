<?php

if (class_exists(\Leads\Leads::class)) {
    // Fields
    $GLOBALS['TL_DCA']['tl_lead_data']['fields']['fieldset_data'] = [
        'sql' => ['type' => 'blob', 'notnull' => false],
    ];
}
