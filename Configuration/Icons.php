<?php

declare(strict_types=1);

use TYPO3\CMS\Core\Imaging\IconProvider\SvgIconProvider;

return [
    'icon-content-plugin-it-recht-kanzlei-legaltext' => [
        'provider' => SvgIconProvider::class,
        'source' => 'EXT:' . \ItRechtKanzlei\LegalText\Plugin\Typo3\Service\LegalTextConfigurationService::EXTENSION_KEY . '/Resources/Public/Icons/Extension.svg',
    ],
];
