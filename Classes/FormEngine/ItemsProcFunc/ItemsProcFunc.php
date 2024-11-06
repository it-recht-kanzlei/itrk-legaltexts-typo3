<?php

declare(strict_types=1);

namespace ItRechtKanzlei\LegalText\Plugin\Typo3\FormEngine\ItemsProcFunc;

/***************************************************************
 *
 *  Copyright notice
 *
 *  (c) 2024 Thomas Deuling <typo3@coding.ms>, coding.ms
 *  (c) 2024 IT-Recht Kanzlei <info@it-recht-kanzlei.de>
 *
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

use ItRechtKanzlei\LegalText\Plugin\Typo3\Service\LegalTextConfigurationService;
use ITRechtKanzlei\LTIPushData;
use TYPO3\CMS\Core\Context\LanguageAspect;
use TYPO3\CMS\Core\Site\SiteFinder;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;

class ItemsProcFunc
{
    public function legalTextTypeSelector(array &$config): void
    {
        if ($this->getPid($config) <= 0) {
            return;
        }

        if (!class_exists(LTIPushData::class)) {
            require_once \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath(LegalTextConfigurationService::EXTENSION_KEY) .  '/Resources/Private/Contrib/PHP/itrk-plugin-sdk-main/sdk/require_all.php';
        }

        $types = LTIPushData::ALLOWED_DOCUMENT_TYPES;

        foreach ($types as $type) {
            $config['items'][] = [
                LocalizationUtility::translate(LegalTextConfigurationService::getBackendLLLPath() . 'type.' . $type) ?? $type,
                $type,
                'tcarecords-tx_itrklegaltextstypo3_domain_model_legal_text-default'
            ];
        }

        usort($config['items'], function ($a, $b) {
            return strcmp($a[0], $b[0]);
        });
    }

    public function legalTextLanguageSelector(array &$config): void
    {
        if (($pid = $this->getPid($config)) > 0) {
            $config['items'][] = [' - ', '', 'install-manage-language'];
            $site = GeneralUtility::makeInstance(SiteFinder::class)->getSiteByPageId($pid);
            if ($site) {
                /** @var LanguageAspect $language */
                foreach ($site->getLanguages() as $language) {
                    $config['items'][] = [$language->getTitle(), $language->getLanguageId(), 'install-manage-language'];
                }
            }
            array_multisort(array_column($config['items'], '0'), SORT_ASC, $config['items']);
        }
    }

    protected function getPid(array $config): int
    {
        if ($config['effectivePid'] > 0) {
            return (int)$config['effectivePid'];
        }
        if (intval($config['row']['pid'] ?? 0) > 0) {
            return (int)$config['row']['pid'];
        }
        if (intval($config['flexParentDatabaseRow']['pid'] ?? 0) > 0) {
            return (int)$config['flexParentDatabaseRow']['pid'];
        }
        return 0;
    }
}
