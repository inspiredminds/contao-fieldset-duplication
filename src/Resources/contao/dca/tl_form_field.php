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

$GLOBALS['TL_DCA']['tl_form_field']['fields']['allowDuplication'] = [
    'label' => &$GLOBALS['TL_LANG']['tl_form_field']['allowDuplication'],
    'exclude' => true,
    'inputType' => 'checkbox',
    'eval' => ['tl_class' => 'w50 m12'],
    'sql' => "char(1) NOT NULL default ''",
];

$fieldsetPalette = System::getContainer()->get('inspiredminds.fieldsetduplication.helper.field')->getFieldsetPalette();

PaletteManipulator::create()
    ->addField('allowDuplication', 'fconfig_legend', PaletteManipulator::POSITION_APPEND)
    ->applyToPalette($fieldsetPalette, 'tl_form_field')
;
