<?php

declare(strict_types=1);

namespace ItRechtKanzlei\LegalText\Plugin\Typo3\Service;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2024 Roman Derlemenko <typo3@coding.ms>
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

use ItRechtKanzlei\LegalText\Plugin\Typo3\Domain\Model\LegalText;
use ItRechtKanzlei\LegalText\Plugin\Typo3\Domain\Repository\LegalTextRepository;
use ITRechtKanzlei\LTIAccountListResult;
use ITRechtKanzlei\LTIError;
use ITRechtKanzlei\LTIHandler;
use ITRechtKanzlei\LTIPushData;
use ITRechtKanzlei\LTIPushResult;
use TYPO3\CMS\Core\Site\Entity\Site;
use TYPO3\CMS\Core\Site\SiteFinder;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class LTIHandlerService extends LTIHandler
{
    protected LegalTextRepository $legalTextRepository;
    protected string $username = '';
    protected string $password = '';

    public function __construct(string $username, string $password)
    {
        $this->legalTextRepository = GeneralUtility::makeInstance(LegalTextRepository::class);
        $this->username = $username;
        $this->password = $password;
    }

    public function validateUserPass(string $username, string $password): bool
    {
        return ((trim($username) === trim($this->username)) && (trim($password) === md5(trim($this->password))));
    }

    public function handleActionGetAccountList(): LTIAccountListResult
    {
        $result = new LTIAccountListResult();

        foreach (LegalTextConfigurationService::getAccounts() as $id => $account) {
            $result->addAccount((string)$id, $account['title'], $account['languages']);
        }
        return $result;
    }

    /**
     * @throws LTIError
     */
    public function handleActionPush(LTIPushData $data): LTIPushResult
    {
        $this->validateUserAccountId($data);

        $legalTextData = $this->initializeLegalTextData($data);
        $site = GeneralUtility::makeInstance(SiteFinder::class)->getSiteByRootPageId($legalTextData['root_page_id']);

        $this->validateDocumentLanguage($data, $site, $legalTextData);

        try {
            $legalTextData['pid'] = (int)$site->getAttribute('itrkLegalTextStorage');
        } catch (\Exception $e) {
            throw new LTIError('Storage page id for legal texts is not configured in site configuration', 129);
        }

        $legalTextData = array_merge($legalTextData, $this->extractLegalTextDataFromPush($legalTextData, $data));

        try {
            $existingLegalText = $this->fetchExistingLegalText($data, $legalTextData);
            $legalTextData['uid'] = $existingLegalText ? $existingLegalText->getRealUid() : 0;

            $this->updateOrInsertLegalText($legalTextData);

            if (!$existingLegalText) {
                // Since we do not know which texts will be pushed first, we need to set the l10n_parent afterwards
                // to ensure that the relationships in TYPO3 are correct.
                $this->assignLanguageParent($legalTextData);
            }
            LegalTextConfigurationService::clearCache($legalTextData['type']);
        } catch (\Exception $exception) {
            throw new LTIError($exception->getMessage(), 127);
        }

        return new LTIPushResult();
    }

    private function initializeLegalTextData(LTIPushData $pushData): array
    {
        $userAccountId = $pushData->getMultiShopId();
        $userAccountIdParts = explode('-', $userAccountId);

        return [
            'root_page_id' => (int)$userAccountIdParts[0],
            'sys_language_uid' => (int)$userAccountIdParts[1],
        ];
    }

    private function extractLegalTextDataFromPush(array $legalTextData, LTIPushData $pushData): array
    {
        return [
            'text' => $pushData->getText(),
            'html' => $pushData->getTextHtml(),
            'type' => $pushData->getType(),
            'country' => $pushData->getCountry(),
            'language' => $pushData->getLanguageIso639_1(),
            'user_account_id' => $pushData->getMultiShopId(),
            'pdf_url' => $this->generateAndSavePdf($pushData, $legalTextData),
        ];
    }

    protected function fetchExistingLegalText(LTIPushData $pushData, array $legalTextData): ?LegalText
    {
        return $this->legalTextRepository->findOneBySettings(
            [
                'type' => $pushData->getType(),
                'pid' => $legalTextData['pid'],
                'languageUid' => $legalTextData['sys_language_uid'],
                'rootPageId' => $legalTextData['root_page_id']
            ]
        );
    }

    protected function updateOrInsertLegalText(array &$legalTextData): void
    {
        if ($legalTextData['uid']) {
            $this->legalTextRepository->updateLegalText($legalTextData);
            return;
        }

        $legalTextData['uid'] = $this->legalTextRepository->insertLegalText($legalTextData);
    }

    private function validateUserAccountId(LTIPushData $pushData): void
    {
        if (!isset(LegalTextConfigurationService::getAccounts()[$pushData->getMultiShopId()])) {
            throw new LTIError('Invalid user account ID', LTIError::INVALID_USER_ACCOUNT_ID);
        }
    }

    private function validateDocumentLanguage(LTIPushData $pushData, Site $site, array $legalTextData): void
    {
        if (LegalTextConfigurationService::getTypo3Version() > 11) {
            $languageCode = $site->getLanguageById($legalTextData['sys_language_uid'])->getLocale()->getLanguageCode();
        } else {
            $languageCode = $site->getLanguageById($legalTextData['sys_language_uid'])->getTwoLetterIsoCode();
        }

        if ($languageCode !== $pushData->getLanguageIso639_1()) {
            throw new LTIError('Invalid document language', LTIError::INVALID_DOCUMENT_LANGUAGE);
        }
    }

    protected function assignLanguageParent(array $legalTextData): void
    {
        if ($legalTextData['sys_language_uid'] > 0) {
            $parent = $this->legalTextRepository->findOneBySettings([
                'type' => $legalTextData['type'],
                'pid' => $legalTextData['pid'],
                'languageUid' => 0,
                'rootPageId' => $legalTextData['root_page_id'],
            ]);

            if ($parent !== null) {
                $this->legalTextRepository->updateL10nParent($legalTextData['uid'], $parent->getRealUid());
            }
            return;
        }

        $this->legalTextRepository->updateL10nParentForAllTranslations($legalTextData);
    }

    public function generateAndSavePdf(LTIPushData $pushData, $legalTextData): string
    {
        if (!$pushData->hasPdf()) {
            return '';
        }

        $uploadStoragePath = LegalTextConfigurationService::getFileStoragePath($legalTextData['root_page_id'],
            $pushData->getLanguageIso639_1());
        $absoluteStoragePath = GeneralUtility::getFileAbsFileName($uploadStoragePath);
        $fileName = $pushData->getLocalizedFileName() ?? $pushData->getFileName() ?? $pushData->getType();
        $fileName = str_ends_with($fileName, '.pdf') ? $fileName : $fileName . '.pdf';

        if ($this->createPathAndSaveFile($absoluteStoragePath, $fileName, $pushData->getPdf())) {
            return $uploadStoragePath . $fileName;
        }

        throw new LTIError('Error while saving file ' . $uploadStoragePath . $fileName, 128);
    }

    public function createPathAndSaveFile(string $path, string $fileName, $fileData): bool
    {
        $path = rtrim($path, '/') . '/';
        if (!is_dir($path)) {
            GeneralUtility::mkdir_deep($path);
        }

        return file_put_contents($path . $fileName, $fileData) !== false;
    }
}
