<?php
declare(strict_types=1);
/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */
namespace Sitegeist\EditorWidgets\Widgets;

use Doctrine\DBAL\ArrayParameterType;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Backend\View\BackendViewFactory;
use TYPO3\CMS\Core\Configuration\Exception\ExtensionConfigurationExtensionNotConfiguredException;
use TYPO3\CMS\Core\Configuration\Exception\ExtensionConfigurationPathDoesNotExistException;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Resource\Exception\FileDoesNotExistException;
use TYPO3\CMS\Core\Resource\Exception\InsufficientFolderAccessPermissionsException;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Dashboard\Widgets\AdditionalCssInterface;
use TYPO3\CMS\Dashboard\Widgets\RequestAwareWidgetInterface;
use TYPO3\CMS\Dashboard\Widgets\WidgetConfigurationInterface;
use TYPO3\CMS\Dashboard\Widgets\WidgetInterface;

final class DuplicateFilesWidget implements WidgetInterface, RequestAwareWidgetInterface, AdditionalCssInterface
{
    /**
     * @var array{showThumbnails: bool, thumbnailWidth: string, thumbnailHeight: string, duplicateLimit: int}
     */
    private readonly array $options;
    private ServerRequestInterface $request;
    public function __construct(
        private readonly BackendViewFactory $backendViewFactory,
        private readonly ConnectionPool $connectionPool,
        private readonly ExtensionConfiguration $extensionConfiguration,
        private readonly ResourceFactory $resourceFactory,
        private readonly WidgetConfigurationInterface $configuration,
        array $options = []
    ) {
        $this->options = array_merge([
            'showThumbnails' => true,
            'thumbnailWidth' => '200m',
            'thumbnailHeight' => '70m',
            'duplicateLimit' => 200,
        ], $options);
    }

    public function renderWidgetContent(): string
    {
        $duplicates = $this->getDuplicates($this->getFileUidsFromSha1($this->getDuplicatedSha1()));
        $view = $this->backendViewFactory->create($this->request, ['sitegeist/editor-widgets']);
        $view->assignMultiple([
            'duplicates' => $duplicates,
            'options' => $this->options,
            'configuration' => $this->configuration,
        ]);
        return $view->render('DuplicateFilesWidget');
    }

    public function getDuplicatedSha1(): array
    {
        $queryBuilder = $this->connectionPool->getConnectionForTable('sys_file')->createQueryBuilder();

        $constraints = [
            $queryBuilder->expr()->neq('sha1', $queryBuilder->createNamedParameter('')),
            $queryBuilder->expr()->eq('missing', $queryBuilder->createNamedParameter(0, Connection::PARAM_INT)),
            $queryBuilder->expr()->gt('storage', $queryBuilder->createNamedParameter(0, Connection::PARAM_INT)),
            $queryBuilder->expr()->neq('name', $queryBuilder->createNamedParameter('index.html')),
            $queryBuilder->expr()->notLike('identifier', $queryBuilder->quote('%_recycler_%')),
        ];

        $excludePathsConstraints = $this->getExcludePathsConstraints($queryBuilder);

        return $queryBuilder
            ->select('sha1')
            ->from('sys_file')
            ->where(...$constraints, ...$excludePathsConstraints)
            ->groupBy('sha1', 'size')
            ->having('COUNT(*) > 1')
            ->setMaxResults((int)$this->options['duplicateLimit'])
            ->executeQuery()
            ->fetchFirstColumn();
    }

    private function getExcludePathsConstraints(QueryBuilder $queryBuilder): array
    {
        try {
            $excludePaths = GeneralUtility::trimExplode(',', $this->extensionConfiguration->get('editor_widgets', 'duplicateFilesWidget_excludePaths'), true);
        } catch (ExtensionConfigurationExtensionNotConfiguredException|ExtensionConfigurationPathDoesNotExistException) {
            $excludePaths = [];
        }
        $constraints = [];

        foreach ($excludePaths as $path) {
            [$storage, $identifier] = GeneralUtility::trimExplode(':', $path, false, 2);
            $identifier = str_starts_with($identifier, '/') ? $identifier : '/' . $identifier;

            $constraints[] = $queryBuilder->expr()->or(
                $queryBuilder->expr()->notLike('identifier', $queryBuilder->createNamedParameter($identifier . '%')),
                $queryBuilder->expr()->neq('storage', $queryBuilder->createNamedParameter($storage, Connection::PARAM_INT)),
            );
        }

        return $constraints;
    }

    public function getFileUidsFromSha1(array $duplicatedSha1): array
    {
        $queryBuilder = $this->connectionPool->getConnectionForTable('sys_file')->createQueryBuilder();
        $uids = $queryBuilder
            ->select('uid', 'sha1')
            ->from('sys_file')
            ->where(
                $queryBuilder->expr()->in('sha1', $queryBuilder->createNamedParameter($duplicatedSha1, ArrayParameterType::STRING))
            )
            ->executeQuery()
            ->fetchAllKeyValue();

        $groupedBySha1 = [];
        foreach ($uids as $uid => $sha1) {
            $groupedBySha1[$sha1][] = $uid;
        }

        return $groupedBySha1;
    }

    private function getDuplicates(array $fileUidGroups): array
    {
        $duplicates = [];
        foreach ($fileUidGroups as $fileUidList) {
            $duplicates[] = array_filter(array_map(
                function ($uid) {
                    try {
                        $file = $this->resourceFactory->getFileObject((int)$uid);
                        if (!$file->exists() || $file->isMissing()) {
                            return null;
                        }
                        $file->getParentFolder();
                    } catch (FileDoesNotExistException | InsufficientFolderAccessPermissionsException | \Exception) {
                        return null;
                    }
                    return [
                        'file' => $file,
                        'referenceCount' => BackendUtility::referenceCount('sys_file', $file->getUid()),
                    ];
                },
                $fileUidList
            ));
        }
        return array_filter($duplicates, static function ($files) { return count($files) >= 2; });
    }

    public function getOptions(): array
    {
        return $this->options;
    }

    public function setRequest(ServerRequestInterface $request): void
    {
        $this->request = $request;
    }

    public function getCssFiles(): array
    {
        return [
            'EXT:editor_widgets/Resources/Public/Css/backend.css',
        ];
    }
}
