<?php

namespace ItRechtKanzlei\LegalText\Plugin\Typo3\Domain\Model;

/***************************************************************
 *
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

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;

class LegalText extends AbstractEntity
{
    public const TABLE = 'tx_itrklegaltextstypo3_domain_model_legal_text';

    protected string $type = '';
    protected string $text = '';
    protected string $html = '';
    protected string $pdfUrl = '';
    protected string $country = '';
    protected string $language = '';
    protected string $userAccountId = '';
    protected int $rootPageId = 0;

    public function getType(): string
    {
        return $this->type;
    }

    public function getText(): string
    {
        return $this->text;
    }

    public function getHtml(): string
    {
        return $this->html;
    }

    public function getPdfUrl()
    {
        return $this->pdfUrl;
    }

    public function getCountry(): string
    {
        return $this->country;
    }

    public function getLanguage(): string
    {
        return $this->language;
    }

    public function getUserAccountId(): string
    {
        return $this->userAccountId;
    }

    public function getRootPageId(): int
    {
        return $this->rootPageId;
    }

    public function getPublicPdfUrl(): ?string
    {
        if (!$this->pdfUrl || !file_exists(GeneralUtility::getFileAbsFileName($this->pdfUrl))) {
            return null;
        }

        return GeneralUtility::getIndpEnv('TYPO3_SITE_URL') . ltrim($this->getPdfUrl(), '/');
    }

    public function getRealUid(): int
    {
        return (int)($this->_localizedUid ?? $this->uid);
    }
}
