<?php

use ItRechtKanzlei\LegalText\Plugin\Typo3\Domain\Model\LegalText;
use ItRechtKanzlei\LegalText\Plugin\Typo3\Service\LegalTextConfigurationService;

if (!defined('TYPO3')) {
    die('Access denied.');
}

$lll = LegalTextConfigurationService::getBackendLLLPath() . LegalText::TABLE;

$tca = [
    'ctrl' => [
        'title' => $lll,
        'label' => 'type',
        'tstamp' => 'tstamp',
        'crdate' => 'crdate',
        'transOrigPointerField' => 'l10n_parent',
        'transOrigDiffSourceField' => 'l10n_diffsource',
        'languageField' => 'sys_language_uid',
        'translationSource' => 'l10n_source',
        'dividers2tabs' => true,
        'delete' => 'deleted',
        'enablecolumns' => [
            'disabled' => 'hidden',
        ],
        'searchFields' => 'type,text,html,pdf_url,country,language,',
        'iconfile' => 'EXT:' . LegalTextConfigurationService::EXTENSION_KEY . '/Resources/Public/Icons/Paragraph.svg'
    ],
    'types' => [
        '1' => [
            'showitem' => 'user_account_id, type, text, html;;;richtext:rte_transform[mode=ts_links], pdf_url,
            --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:language,--palette--;;language,
            --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:access,hidden
        '
        ],
    ],
    'palettes' => [
        'language' => [
            'showitem' => '
                sys_language_uid,l10n_parent,
            ',
        ],
    ],
    'columns' => [
        'sys_language_uid' => [
            'exclude' => true,
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.language',
            'config' => [
                'type' => 'language',
            ],
        ],
        'l10n_parent' => [
            'displayCond' => 'FIELD:sys_language_uid:>:0',
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.l18n_parent',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => [
                    [
                        'label' => '',
                        'value' => 0,
                    ],
                ],
                'foreign_table' => LegalText::TABLE,
                'foreign_table_where' => sprintf(
                    'AND {#%s}.{#pid}=###CURRENT_PID### AND {#%s}.{#sys_language_uid} IN (-1, 0)',
                    LegalText::TABLE,
                    LegalText::TABLE
                ),
                'default' => 0,
            ],
        ],
        'l10n_source' => [
            'config' => [
                'type' => 'passthrough',
            ],
        ],
        'l10n_diffsource' => [
            'config' => [
                'type' => 'passthrough',
                'default' => '',
            ],
        ],
        'hidden' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.hidden',
            'config' => [
                'type' => 'check',
            ],
        ],
        'type' => [
            'exclude' => 1,
            'label' => $lll . '.type',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'itemsProcFunc' => 'ItRechtKanzlei\LegalText\Plugin\Typo3\FormEngine\ItemsProcFunc\ItemsProcFunc->legalTextTypeSelector',
                'size' => 1,
                'minitems' => 0,
                'maxitems' => 1,
            ],
        ],
        'text' => [
            'exclude' => 1,
            'label' => $lll . '.text',
            'config' => [
                'type' => 'text',
                'cols' => 40,
                'rows' => 15,
                'eval' => 'trim'
            ]
        ],
        'html' => [
            'exclude' => 1,
            'label' => $lll . '.html',
            'config' => [
                'type' => 'text',
                'cols' => 40,
                'rows' => 15,
                'eval' => 'trim',
                'wizards' => [
                    'RTE' => [
                        'icon' => 'wizard_rte2.gif',
                        'notNewRecords' => 1,
                        'RTEonly' => 1,
                        'module' => [
                            'name' => 'wizard_rich_text_editor',
                            'urlParameters' => [
                                'mode' => 'wizard',
                                'act' => 'wizard_rte.php'
                            ]
                        ],
                        'title' => 'LLL:EXT:cms/locallang_ttc.xlf:bodytext.W.RTE',
                        'type' => 'script'
                    ]
                ]
            ],
        ],
        'pdf_url' => [
            'exclude' => 1,
            'label' => $lll . '.pdf_url',
            'config' => [
                'readOnly' => true,
                'type' => 'input',
                'size' => 30,
                'eval' => 'trim'
            ],
        ],
        'language' => [
            'config' => [
                'type' => 'passthrough',
            ],
        ],
        'country' => [
            'config' => [
                'type' => 'passthrough',
            ],
        ],
        'root_page_id' => [
            'config' => [
                'type' => 'passthrough',
            ],
        ],
        'user_account_id' => [
            'exclude' => 1,
            'label' => $lll . '.user_account_id',
            'config' => [
                'type' => 'passthrough',
            ],
        ],
    ],
];

if (LegalTextConfigurationService::getTypo3Version() === 10) {
    $tca['columns']['sys_language_uid'] = [
        'exclude' => 1,
        'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.language',
        'config' => [
            'type' => 'select',
            'renderType' => 'selectSingle',
            'special' => 'languages',
            'items' => [
                ['LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.allLanguages', -1],
                ['LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.default_value', 0]
            ],
            'default' => 0,
        ],
    ];
}

return $tca;
