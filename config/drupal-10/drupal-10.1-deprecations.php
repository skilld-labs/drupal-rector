<?php

declare(strict_types=1);

use DrupalRector\Rector\Deprecation\FunctionToStaticRector;
use DrupalRector\Drupal10\Rector\Deprecation\WatchdogExceptionRector;
use DrupalRector\Rector\ValueObject\DrupalIntroducedVersionConfiguration;
use DrupalRector\Rector\ValueObject\FunctionToStaticConfiguration;
use Rector\Config\RectorConfig;
use Rector\Symfony\Set\SymfonyLevelSetList;

return static function (RectorConfig $rectorConfig): void {
    $rectorConfig->sets([
        SymfonyLevelSetList::UP_TO_SYMFONY_63,
    ]);

    // https://www.drupal.org/node/3244583
    $rectorConfig->ruleWithConfiguration(FunctionToStaticRector::class, [
        new FunctionToStaticConfiguration('10.1.0', 'drupal_rewrite_settings', 'Drupal\Core\Site\SettingsEditor', 'rewrite', [0 => 1, 1 => 0]),
    ]);

    // https://www.drupal.org/node/2932520
    $rectorConfig->ruleWithConfiguration(WatchdogExceptionRector::class, [
        new DrupalIntroducedVersionConfiguration('10.1.0'),
    ]);
};
