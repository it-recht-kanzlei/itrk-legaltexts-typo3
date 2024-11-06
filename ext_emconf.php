<?php

$EM_CONF['itrk_legaltexts_typo3'] = [
    'title' => 'IT-Recht Kanzlei - Legal Texts',
    'description' => 'A TYPO3 extension that integrates legally compliant documents, such as terms and conditions or privacy policies, directly into your TYPO3 site from the IT-Recht Kanzlei service. This extension requires an active IT-Recht Kanzlei subscription to receive the latest legal texts automatically pushed and updated within TYPO3.',
    'category' => 'plugin',
    'author_company' => 'IT-Recht Kanzlei',
    'author_email' => 'info@it-recht-kanzlei.de',
    'state' => 'stable',
    'internal' => '',
    'uploadfolder' => '1',
    'createDirs' => '',
    'clearCacheOnLoad' => 0,
    'version' => '3.0.1',
    'constraints' => [
        'depends' => [
            'php' => '7.4.0-8.4.99',
            'typo3' => '10.4.0-13.4.99',
        ],
        'conflicts' => [
        ],
        'suggests' => [
        ],
    ],
];
