<?php

declare(strict_types=1);

/*
 * (c) INSPIRED MINDS
 */

namespace InspiredMinds\ContaoFieldsetDuplication\EventListener;

use Contao\Controller;
use Contao\CoreBundle\DataContainer\PaletteManipulator;
use Contao\CoreBundle\ServiceAnnotation\Hook;
use InspiredMinds\ContaoFieldsetDuplication\Helper\FieldHelper;

final class FormFieldDcaListener
{
    public function __construct(private FieldHelper $fieldHelper)
    {
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
