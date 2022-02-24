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

use Contao\Controller;
use Contao\CoreBundle\DataContainer\PaletteManipulator;
use Contao\CoreBundle\ServiceAnnotation\Hook;
use InspiredMinds\ContaoFieldsetDuplication\Helper\FieldHelper;

final class FormFieldDcaListener
{
    private $fieldHelper;

    public function __construct(FieldHelper $fieldHelper)
    {
        $this->fieldHelper = $fieldHelper;
    }

    /**
     * @Hook("loadDataContainer")
     */
    public function onLoadDataContainer(string $table): void
    {
        if ('tl_form_field' !== $table) {
            return;
        }

        $fieldsetPalette = $this->fieldHelper->getFieldsetPalette();

        PaletteManipulator::create()
            ->addField('allowDuplication', 'fconfig_legend', PaletteManipulator::POSITION_APPEND)
            ->applyToPalette($fieldsetPalette, 'tl_form_field')
        ;
    }

    public function templateOptions(): array
    {
        return Controller::getTemplateGroup('nc_fieldset_duplication_');
    }
}
