<?php

declare(strict_types=1);

/*
 * This file is part of the inspiredminds/contao-fieldset-duplication package.
 *
 * (c) inspiredminds
 *
 * @license LGPL-3.0-or-later
 */

use InspiredMinds\ContaoFieldsetDuplication\EventListener\FormFieldDcaListener;

$GLOBALS['TL_DCA']['tl_form_field']['palettes']['__selector__'][] = 'allowDuplication';
$GLOBALS['TL_DCA']['tl_form_field']['subpalettes']['allowDuplication'] = 'name,maxDuplicationRows,labelButtonAdd,labelButtonRemove,doNotCopyExistingValues,notificationTokenTemplates,leadStore';

$GLOBALS['TL_DCA']['tl_form_field']['fields']['allowDuplication'] = [
    'exclude' => true,
    'inputType' => 'checkbox',
    'eval' => ['tl_class' => 'clr w50', 'submitOnChange' => true],
    'sql' => "char(1) NOT NULL default ''",
];

$GLOBALS['TL_DCA']['tl_form_field']['fields']['maxDuplicationRows'] = [
    'exclude' => true,
    'inputType' => 'text',
    'eval' => ['rgxp' => 'digit', 'tl_class' => 'w50'],
    'sql' => "varchar(10) NOT NULL default ''",
];

$GLOBALS['TL_DCA']['tl_form_field']['fields']['labelButtonAdd'] = [
    'exclude' => true,
    'inputType' => 'text',
    'eval' => ['maxlength' => 64, 'tl_class' => 'w50'],
    'sql' => "varchar(64) NOT NULL default ''",
];

$GLOBALS['TL_DCA']['tl_form_field']['fields']['labelButtonRemove'] = [
    'exclude' => true,
    'inputType' => 'text',
    'eval' => ['maxlength' => 64, 'tl_class' => 'w50'],
    'sql' => "varchar(64) NOT NULL default ''",
];

$GLOBALS['TL_DCA']['tl_form_field']['fields']['doNotCopyExistingValues'] = [
    'exclude' => true,
    'inputType' => 'checkbox',
    'eval' => ['tl_class' => 'w50'],
    'sql' => ['type' => 'boolean', 'default' => false],
];

$GLOBALS['TL_DCA']['tl_form_field']['fields']['notificationTokenTemplates'] = [
    'label' => &$GLOBALS['TL_LANG']['tl_form_field']['notificationTokenTemplates'],
    'exclude' => true,
    'inputType' => 'group',
    'palette' => ['format', 'template'],
    'eval' => ['tl_class' => 'clr'],
    'fields' => [
        'format' => [
            'label' => &$GLOBALS['TL_LANG']['tl_form_field']['notificationTokenFormat'],
            'inputType' => 'text',
            'eval' => ['tl_class' => 'w50'],
        ],
        'template' => [
            'label' => &$GLOBALS['TL_LANG']['tl_form_field']['notificationTokenFormatTemplate'],
            'inputType' => 'select',
            'options_callback' => [FormFieldDcaListener::class, 'templateOptions'],
            'eval' => ['includeBlankOption' => true, 'chosen' => true, 'tl_class' => 'w50'],
        ],
    ],
    'sql' => ['type' => 'blob', 'length' => 65535, 'notnull' => false],
];
