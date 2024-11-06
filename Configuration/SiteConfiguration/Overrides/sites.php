<?php

$lll = \ItRechtKanzlei\LegalText\Plugin\Typo3\Service\LegalTextConfigurationService::getBackendLLLPath() . 'site.';
$GLOBALS['SiteConfiguration']['site']['columns']['itrkLegalTextStorage'] = [
    'label' => $lll . 'itrkLegalTextStorage',
    'config' => [
        'type' => 'input',
        'eval' => 'int,trim',
    ],
];

$GLOBALS['SiteConfiguration']['site']['types']['0']['showitem'] .= ',--div--;' . $lll . 'div.legalTexts, itrkLegalTextStorage';

