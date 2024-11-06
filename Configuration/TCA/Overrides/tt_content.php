<?php

use ItRechtKanzlei\LegalText\Plugin\Typo3\Service\LegalTextConfigurationService;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Extbase\Utility\ExtensionUtility;

if (!defined('TYPO3')) {
    die('Access denied.');
}

$lll = LegalTextConfigurationService::getBackendLLLPath();

ExtensionUtility::registerPlugin(
    'ItrkLegaltextsTypo3',
    'LegalText',
    $lll . 'plugin.legaltexts.label'
);

ExtensionUtility::registerPlugin(
    'ItrkLegaltextsTypo3',
    'Api',
    $lll . 'plugin.api.label'
);

ExtensionManagementUtility::addStaticFile(
    LegalTextConfigurationService::EXTENSION_KEY,
    'Configuration/TypoScript',
    'IT-Recht Kanzlei - Legal Texts'
);

$GLOBALS['TCA']['tx_itrklegaltextstypo3_domain_model_legal_text']['ctrl']['security']['ignorePageTypeRestriction'] = true;

// Include flex forms
$pluginName='LegalText';
$extensionName = 'ItrkLegaltextsTypo3';
$pluginSignature = strtolower($extensionName) . '_' . strtolower($pluginName);
$GLOBALS['TCA']['tt_content']['types']['list']['subtypes_addlist'][$pluginSignature] = 'pi_flexform';
$GLOBALS['TCA']['tt_content']['types']['list']['subtypes_excludelist'][$pluginSignature] = 'recursive,select_key,pages';

if (LegalTextConfigurationService::getTypo3Version() < 12) {
    ExtensionManagementUtility::addPiFlexFormValue($pluginSignature, 'FILE:EXT:' . LegalTextConfigurationService::EXTENSION_KEY . '/Configuration/FlexForms/LegalText_v11.xml');
} else {
    ExtensionManagementUtility::addPiFlexFormValue($pluginSignature, 'FILE:EXT:' . LegalTextConfigurationService::EXTENSION_KEY . '/Configuration/FlexForms/LegalText.xml');
}
