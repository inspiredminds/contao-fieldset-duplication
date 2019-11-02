<?php

declare(strict_types=1);

/*
 * This file is part of the inspiredminds/contao-fieldset-duplication package.
 *
 * (c) inspiredminds
 *
 * @license LGPL-3.0-or-later
 */

namespace InspiredMinds\ContaoFieldsetDuplication\Helper;

use Composer\Semver\Semver;
use Jean85\PrettyVersions;

class FieldHelper
{
    public function getFieldsetPalette(): string
    {
        $contaoVersion = PrettyVersions::getVersion('contao/core-bundle');

        return Semver::satisfies($contaoVersion->getShortVersion(), '>=4.5') ? 'fieldsetStart' : 'fieldsetfsStart';
    }

    public function isFieldsetStart($field): bool
    {
        return 'start' === $this->getFieldsetType($field);
    }

    public function isFieldsetStop($field): bool
    {
        return 'stop' === $this->getFieldsetType($field);
    }

    public function getFieldsetType($field): ?string
    {
        if (!is_string($field->type) || false === strpos($field->type, 'fieldset')) {
            return null;
        }

        $contaoVersion = PrettyVersions::getVersion('contao/core-bundle');

        if (Semver::satisfies($contaoVersion->getShortVersion(), '>=4.5')) {
            return strtolower(substr($field->type, 8));
        }

        return strtolower(substr($field->fsType, 2));
    }

    public function isFieldset($field): bool
    {
        return false !== strpos($field->type, 'fieldset');
    }
}
