<?php

declare(strict_types=1);

/*
 * (c) INSPIRED MINDS
 */

use Contao\EasyCodingStandard\Fixer\CommentLengthFixer;
use Contao\EasyCodingStandard\Set\SetList;
use PhpCsFixer\Fixer\Comment\HeaderCommentFixer;
use Symplify\EasyCodingStandard\Config\ECSConfig;

return ECSConfig::configure()
    ->withSets([SetList::CONTAO])
    ->withPaths([
        __DIR__.'/src',
        __DIR__.'/contao',
        __DIR__.'/ecs.php',
        __DIR__.'/rector.php',
    ])
    ->withConfiguredRule(HeaderCommentFixer::class, ['header' => '(c) INSPIRED MINDS'])
    ->withSkip([
        CommentLengthFixer::class,
    ])
    ->withParallel()
    ->withSpacing(lineEnding: "\n")
    ->withCache(sys_get_temp_dir().'/ecs_default_cache')
;
