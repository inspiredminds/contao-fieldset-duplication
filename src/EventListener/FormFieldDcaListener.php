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
use Contao\CoreBundle\Framework\ContaoFrameworkInterface;

final class FormFieldDcaListener
{
    /** @var ContaoFrameworkInterface */
    private $framework;

    public function __construct(ContaoFrameworkInterface $framework)
    {
        $this->framework = $framework;
    }

    public function templateOptions(): array
    {
        $adapter = $this->framework->getAdapter(Controller::class);

        return $adapter->getTemplateGroup('nc_fieldset_duplication_');
    }
}
