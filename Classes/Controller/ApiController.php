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
use ItRechtKanzlei\LegalText\Plugin\Typo3\Service\LTIHandlerService;
use ITRechtKanzlei\LTI;
use Psr\Log\LoggerInterface;
use TYPO3\CMS\Core\Information\Typo3Version;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;

class ApiController extends ActionController
{
    protected LoggerInterface $logger;
    protected LegalTextRepository $legalTextRepository;

    public function __construct(
        LegalTextRepository $legalTextRepository
    ) {
        $this->logger = GeneralUtility::makeInstance(\TYPO3\CMS\Core\Log\LogManager::class)->getLogger(__CLASS__);
        $this->legalTextRepository = $legalTextRepository;

        if (!class_exists(LTI::class)) {
            require_once \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath(LegalTextConfigurationService::EXTENSION_KEY) .  '/Resources/Private/Contrib/PHP/itrk-plugin-sdk-main/sdk/require_all.php';
        }
    }

    protected function getItrkSDK(): LTI
    {
        $extensionConfiguration = LegalTextConfigurationService::getExtensionConfiguration();

        /** @var LTIHandlerService $ltiHandlerService */
        $ltiHandlerService = GeneralUtility::makeInstance(
            LTIHandlerService::class,
            strval($extensionConfiguration['api']['username'] ?? ''),
            strval($extensionConfiguration['api']['password'] ?? ''),
        );
        /** @var LTI $lti */
        $lti = GeneralUtility::makeInstance(
            LTI::class,
            $ltiHandlerService,
            GeneralUtility::makeInstance(Typo3Version::class)->getVersion(),
            ExtensionManagementUtility::getExtensionVersion(LegalTextConfigurationService::EXTENSION_KEY)
        );

        return $lti;
    }

    /**
     * @return \Psr\Http\Message\MessageInterface|\Psr\Http\Message\ResponseInterface
     */
    public function requestAction()
    {
        if (LegalTextConfigurationService::getTypo3Version() === 10) {
            $request = strval($_POST['xml'] ?? '');
        } else {
            $request = strval($this->request->getParsedBody()['xml'] ?? '');
        }

        $response = (string)$this->getItrkSDK()->handleRequest($request);
        $this->logger->info('Response', ['data' => $response]);

        if (LegalTextConfigurationService::getTypo3Version() === 10) {
            return $response;
        }

        return $this->createXmlResponse($response);
    }

    /**
     * @return \Psr\Http\Message\MessageInterface|\Psr\Http\Message\ResponseInterface
     */
    protected function createXmlResponse(string $xml, int $status = 200)
    {
        $response = $this->responseFactory->createResponse()
            ->withHeader('Content-Type', 'application/xml')
            ->withStatus($status);
        $response->getBody()->write($xml);

        return $response;
    }
}
