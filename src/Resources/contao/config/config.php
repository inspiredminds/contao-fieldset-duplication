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

if (!\is_array($GLOBALS['TL_HOOKS']['storeFormData'])) {
    $GLOBALS['TL_HOOKS']['storeFormData'] = [];
}
array_unshift($GLOBALS['TL_HOOKS']['storeFormData'], ['inspiredminds.fieldsetduplication.listener.formhook', 'onStoreFormData']);
