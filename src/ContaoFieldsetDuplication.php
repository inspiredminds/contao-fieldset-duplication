<?php

declare(strict_types=1);

/*
 * (c) INSPIRED MINDS
 */

namespace InspiredMinds\ContaoFieldsetDuplication;

use Symfony\Component\HttpKernel\Bundle\Bundle;

class ContaoFieldsetDuplication extends Bundle
{
    public function getPath(): string
    {
        return \dirname(__DIR__);
    }
}
