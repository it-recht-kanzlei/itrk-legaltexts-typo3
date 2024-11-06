<?php

declare(strict_types=1);

namespace ItRechtKanzlei\LegalText\Plugin\Typo3\Service;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2024 IT-Recht Kanzlei <info@it-recht-kanzlei.de>
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
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

use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Site\SiteFinder;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class LegalTextConfigurationService implements SingletonInterface
{
    public const EXTENSION_KEY = 'itrk_legaltexts_typo3';

    public static function getAccounts(): array
    {
        $accounts = [];

        foreach (GeneralUtility::makeInstance(SiteFinder::class)->getAllSites() as $site) {
            foreach ($site->getLanguages() as $siteLanguage) {
                if (self::getTypo3Version() > 11) {
                    $locale = $siteLanguage->getLocale()->__toString();
                } else {
                    $locale = $siteLanguage->getTwoLetterIsoCode();
                }
                $accounts[$site->getRootPageId() . '-' . $siteLanguage->getLanguageId()] = [
                    'title' => $site->getIdentifier() . ' - ' . $siteLanguage->getTitle(),
                    'languages' => [$locale]
                ];
            }
        }

        return $accounts;
    }

    public static function getExtensionConfiguration(string $key = '')
    {
        return GeneralUtility::makeInstance(ExtensionConfiguration::class)->get(self::EXTENSION_KEY, $key) ?? [];
    }

    public static function getFileStoragePath(int $rootPageId, string $languagePath): string
    {
        $path = [
            rtrim(self::getExtensionConfiguration('fileStoragePath') ?? 'uploads/tx_' . self::EXTENSION_KEY, '/'),
            $rootPageId,
            $languagePath
        ];

        return implode('/', $path) . '/';
    }

    public static function getBackendLLLPath(): string
    {
        return 'LLL:EXT:' . self::EXTENSION_KEY . '/Resources/Private/Language/locallang_be.xlf:';
    }

    public static function addCacheTag(string $type): void
    {
        if (!empty($GLOBALS['TSFE']) && is_object($GLOBALS['TSFE'])) {
            $GLOBALS['TSFE']->addCacheTags(['legaltext_' . $type]);
        }
    }

    public static function clearCache(string $type): void
    {
        /** @var CacheManager $cacheManager */
        $cacheManager = GeneralUtility::makeInstance(CacheManager::class);
        $cacheManager->flushCachesInGroupByTag('pages', 'legaltext_' . $type);
    }

    public static function getTypo3Version(): int
    {
        $versionInformation = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Core\Information\Typo3Version::class);

        return $versionInformation->getMajorVersion();
    }
}
