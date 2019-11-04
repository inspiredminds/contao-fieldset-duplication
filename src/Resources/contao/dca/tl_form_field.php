<?php

declare(strict_types=1);

/*
 * This file is part of the inspiredminds/contao-fieldset-duplication package.
 *
 * (c) inspiredminds
 *
 * @license LGPL-3.0-or-later
 */

use Contao\CoreBundle\DataContainer\PaletteManipulator;
use Contao\System;
use InspiredMinds\ContaoFieldsetDuplication\EventListener\FormFieldDcaListener;

$GLOBALS['TL_DCA']['tl_form_field']['palettes']['__selector__'][] = 'allowDuplication';
$GLOBALS['TL_DCA']['tl_form_field']['subpalettes']['allowDuplication'] .= 'name,notificationTokenTemplates';

$GLOBALS['TL_DCA']['tl_form_field']['fields']['allowDuplication'] = [
    'label' => &$GLOBALS['TL_LANG']['tl_form_field']['allowDuplication'],
    'exclude' => true,
    'inputType' => 'checkbox',
    'eval' => ['tl_class' => 'w50 m12', 'submitOnChange' => true],
    'sql' => "char(1) NOT NULL default ''",
];

$GLOBALS['TL_DCA']['tl_form_field']['fields']['notificationTokenTemplates'] = [
    'label' => &$GLOBALS['TL_LANG']['tl_form_field']['notificationTokenTemplates'],
    'exclude' => true,
    'inputType' => 'multiColumnWizard',
    'options_callback' => [FormFieldDcaListener::class, 'templateOptions'],
    'eval' => [
        'tl_class' => 'clr',
        'columnFields' => [
            'format' => [
                'label' => &$GLOBALS['TL_LANG']['tl_form_field']['notificationTokenFormat'],
                'inputType' => 'text',
                'eval' => ['style' => 'width: 200px'],
            ],
            'template' => [
                'label' => &$GLOBALS['TL_LANG']['tl_form_field']['notificationTokenFormatTemplate'],
                'inputType' => 'select',
                'options_callback' => [FormFieldDcaListener::class, 'templateOptions'],
                'eval' => ['includeBlankOption' => true, 'chosen' => true, 'style' => 'width:400px'],
            ],
        ],
    ],
    'sql' => 'blob NULL',
];

$fieldsetPalette = System::getContainer()->get('inspiredminds.fieldsetduplication.helper.field')->getFieldsetPalette();

PaletteManipulator::create()
    ->addField('allowDuplication', 'fconfig_legend', PaletteManipulator::POSITION_APPEND)
    ->applyToPalette($fieldsetPalette, 'tl_form_field')
;
