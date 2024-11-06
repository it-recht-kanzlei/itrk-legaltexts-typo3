<?php

if (!defined('TYPO3')) {
    die('Access denied.');
}

\TYPO3\CMS\Extbase\Utility\ExtensionUtility::configurePlugin(
    'ItrkLegaltextsTypo3',
    'LegalText',
    [
        \ItRechtKanzlei\LegalText\Plugin\Typo3\Controller\LegalTextController::class => 'show',
    ]
);

\TYPO3\CMS\Extbase\Utility\ExtensionUtility::configurePlugin(
    'ItrkLegaltextsTypo3',
    'Api',
    [
        \ItRechtKanzlei\LegalText\Plugin\Typo3\Controller\ApiController::class => 'request',
    ],
    [
        \ItRechtKanzlei\LegalText\Plugin\Typo3\Controller\ApiController::class => 'request',
    ]
);

// Only include page.tsconfig if TYPO3 version is below 12 so that it is not imported twice.
if (\ItRechtKanzlei\LegalText\Plugin\Typo3\Service\LegalTextConfigurationService::getTypo3Version() < 12) {
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPageTSConfig('
      @import "EXT:' . \ItRechtKanzlei\LegalText\Plugin\Typo3\Service\LegalTextConfigurationService::EXTENSION_KEY . '/Configuration/page.tsconfig"
   ');
}

if (\ItRechtKanzlei\LegalText\Plugin\Typo3\Service\LegalTextConfigurationService::getTypo3Version() === 10) {
    $iconRegistry = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Core\Imaging\IconRegistry::class);
    $icons = require_once \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath(\ItRechtKanzlei\LegalText\Plugin\Typo3\Service\LegalTextConfigurationService::EXTENSION_KEY) . 'Configuration/Icons.php';
    foreach ($icons as $key => $icon) {
        $iconRegistry->registerIcon(
            $key,
            $icon['provider'],
            ['source' => $icon['source']]
        );
    }
}

$GLOBALS['TYPO3_CONF_VARS']['LOG']['ItRechtKanzlei']['LegalText']['Plugin']['Typo3']['Controller']['writerConfiguration'] = [
    \TYPO3\CMS\Core\Log\LogLevel::INFO => [
        \TYPO3\CMS\Core\Log\Writer\FileWriter::class => [
            'logFileInfix' => 'it_recht_kanzlei',
        ],
    ],
];
