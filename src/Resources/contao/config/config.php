<?php

declare(strict_types=1);

/*
 * This file is part of the inspiredminds/contao-fieldset-duplication package.
 *
 * (c) inspiredminds
 *
 * @license LGPL-3.0-or-later
 */

$GLOBALS['TL_HOOKS']['loadFormField'][] = ['inspiredminds.fieldsetduplication.listener.formhook', 'onLoadFormField'];
$GLOBALS['TL_HOOKS']['compileFormFields'][] = ['inspiredminds.fieldsetduplication.listener.formhook', 'onCompileFormFields'];
$GLOBALS['TL_HOOKS']['prepareFormData'][] = ['inspiredminds.fieldsetduplication.listener.formhook', 'onPrepareFormData'];

if (!isset($GLOBALS['TL_HOOKS']['storeFormData'])) {
    $GLOBALS['TL_HOOKS']['storeFormData'] = [];
}
array_unshift($GLOBALS['TL_HOOKS']['storeFormData'], ['inspiredminds.fieldsetduplication.listener.formhook', 'onStoreFormData']);

// Add Leads support
if (class_exists(\Leads\Leads::class)) {
    $GLOBALS['TL_HOOKS']['storeLeadsData'][] = [\InspiredMinds\ContaoFieldsetDuplication\EventListener\LeadsListener::class, 'onStoreLeadsData'];
}
