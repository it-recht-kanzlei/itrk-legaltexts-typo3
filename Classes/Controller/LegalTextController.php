<?php

declare(strict_types=1);

namespace ItRechtKanzlei\LegalText\Plugin\Typo3\Controller;

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

use ItRechtKanzlei\LegalText\Plugin\Typo3\Domain\Repository\LegalTextRepository;
use ItRechtKanzlei\LegalText\Plugin\Typo3\Service\LegalTextConfigurationService;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;

class LegalTextController extends ActionController
{
    protected LegalTextRepository $legalTextRepository;

    public function __construct(LegalTextRepository $legalTextRepository)
    {
        $this->legalTextRepository = $legalTextRepository;
    }

    public function showAction()
    {
        $legalText = $this->legalTextRepository->findOneBySettings($this->settings);
        LegalTextConfigurationService::addCacheTag($this->settings['type']);

        $this->view->assign('legalText', $legalText);

        if (LegalTextConfigurationService::getTypo3Version() > 10) {
            return $this->htmlResponse();
        }
    }
}
