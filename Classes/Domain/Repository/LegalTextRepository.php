<?php

declare(strict_types=1);

namespace ItRechtKanzlei\LegalText\Plugin\Typo3\Domain\Repository;

use ItRechtKanzlei\LegalText\Plugin\Typo3\Domain\Model\LegalText;
use ItRechtKanzlei\LegalText\Plugin\Typo3\Service\LegalTextConfigurationService;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Persistence\QueryInterface;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2016 Thomas Deuling <typo3@coding.ms>, coding.ms
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
class LegalTextRepository extends \TYPO3\CMS\Extbase\Persistence\Repository
{
    public function findOneBySettings(array $settings): ?LegalText
    {
        $query = $this->createQuery();

        $constraints = [];
        $constraints[] = $query->equals('type', $settings['type']);

        if (isset($settings['pid'])) {
            $query->getQuerySettings()->setRespectStoragePage(false);
            $constraints[] = $query->equals('pid', $settings['pid']);
        }
        if (isset($settings['languageUid']) && !empty($settings['languageUid'])) {
            $query->getQuerySettings()->setRespectSysLanguage(false);
            $constraints[] = $query->equals('sys_language_uid', $settings['languageUid']);
        }
        if (isset($settings['rootPageId'])) {
            $constraints[] = $query->equals('root_page_id', $settings['rootPageId']);
        }

        $query->matching(
            $query->logicalAnd(...$constraints)
        );

        $query->setOrderings(['crdate' => QueryInterface::ORDER_DESCENDING]);
        $query->setLimit(1);

        return $query->execute()->getFirst();
    }

    public function updateL10nParentForAllTranslations(array $legalText): void
    {
        $queryBuilder = $this->getQueryBuilder();

        $queryBuilder->update(LegalText::TABLE)
            ->set('l10n_parent', $legalText['uid'])
            ->where(
                $queryBuilder->expr()->eq('pid', $queryBuilder->createNamedParameter($legalText['pid'])),
                $queryBuilder->expr()->eq('type', $queryBuilder->createNamedParameter($legalText['type'])),
                $queryBuilder->expr()->gt('sys_language_uid',
                    $queryBuilder->createNamedParameter(0, Connection::PARAM_INT))
            );

        if (LegalTextConfigurationService::getTypo3Version() === 10) {
            $queryBuilder->execute();
            return;
        }

        $queryBuilder->executeStatement();
    }

    public function updateL10nParent(int $uid, int $l10nParent): void
    {
        $queryBuilder = $this->getQueryBuilder();

        $queryBuilder->update(LegalText::TABLE)
            ->set('l10n_parent', $l10nParent)
            ->where(
                $queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter($uid, Connection::PARAM_INT))
            );

        if (LegalTextConfigurationService::getTypo3Version() === 10) {
            $queryBuilder->execute();
            return;
        }

        $queryBuilder->executeStatement();
    }

    public function updateLegalText(array $legalTextData): void
    {
        $uid = $legalTextData['uid'];
        unset($legalTextData['uid']);
        $legalTextData['tstamp'] = time();

        $queryBuilder = $this->getQueryBuilder();

        $queryBuilder->update(LegalText::TABLE)
            ->where($queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter($uid)));

        foreach ($legalTextData as $column => $value) {
            $queryBuilder->set($column, $value);
        }

        if (LegalTextConfigurationService::getTypo3Version() === 10) {
            $queryBuilder->execute();
            return;
        }

        $queryBuilder->executeStatement();
    }

    public function insertLegalText(array $legalTextData): int
    {
        $time = time();
        $legalTextData['tstamp'] = $time;
        $legalTextData['crdate'] = $time;

        $queryBuilder = $this->getQueryBuilder();
        $queryBuilder->insert(LegalText::TABLE)
            ->values($legalTextData);

        if (LegalTextConfigurationService::getTypo3Version() === 10) {
            $queryBuilder->execute();
        } else {
            $queryBuilder->executeStatement();
        }

        return (int)$queryBuilder->getConnection()->lastInsertId();
    }

    protected function getQueryBuilder(): QueryBuilder
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable(LegalText::TABLE);
        if (LegalTextConfigurationService::getTypo3Version() === 10) {
            $queryBuilder->getRestrictions()->removeAll();
        }

        return $queryBuilder;
    }
}
