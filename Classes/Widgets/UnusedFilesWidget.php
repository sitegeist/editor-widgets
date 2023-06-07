<?php

namespace Sitegeist\WidgetMirror\Widgets;

use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Core\Page\JavaScriptModuleInstruction;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Resource\StorageRepository;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Dashboard\Widgets\WidgetConfigurationInterface;
use TYPO3\CMS\Dashboard\Widgets\WidgetInterface;
use TYPO3\CMS\Fluid\View\StandaloneView;

class UnusedFilesWidget implements WidgetInterface
{
    /**
     * @var WidgetConfigurationInterface
     */
    private $configuration;

    /**
     * @var StandaloneView
     */
    private $view;

    /**
     * @var LanguageServiceFactory
     */
    private $languageServiceFactory;

    /**
     * @var ConnectionPool
     */
    private $connectionPool;

    public function __construct(
        WidgetConfigurationInterface $configuration,
        StandaloneView               $view,
        LanguageServiceFactory       $languageServiceFactory,
        ConnectionPool               $connectionPool
    )
    {
        $this->configuration = $configuration;
        $this->view = $view;
        $this->languageServiceFactory = $languageServiceFactory;
        $this->connectionPool = $connectionPool;
    }

    public function renderWidgetContent(): string
    {
        $resourceFactory = GeneralUtility::makeInstance(ResourceFactory::class);

        $queryBuilder = $this->connectionPool->getConnectionForTable('sys_file')->createQueryBuilder();
        $queryBuilder->getRestrictions()->removeAll();

        $files = $queryBuilder
            ->select('sys_file.uid', 'sys_file.identifier', 'sys_file.size')
            ->from('sys_file')
            ->leftJoin('sys_file', 'sys_refindex', 'sr', 'sr.ref_uid = sys_file.uid AND sr.tablename != "sys_file_metadata" AND sr.ref_table = "sys_file"')
            ->addOrderBy('sys_file.size', 'desc')
            ->where('sr.hash IS NULL')
            ->setMaxResults(10)
            ->execute()
            ->fetchAllAssociative();

        $this->view->setTemplate('Widget/UnusedFilesWidget');
        $this->view->assignMultiple([
            'files' => $files,
            'configuration' => $this->configuration
        ]);

        return $this->view->render();
    }
}
