<?php
/**
 * ObjectUploader.php
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the MIT license, which
 * is bundled with this package in the file LICENSE.txt.
 *
 * It is also available on the Internet at the following URL:
 * https://docs.auroraextensions.com/magento/extensions/2.x/googlecloudstorage/LICENSE.txt
 *
 * @package     AuroraExtensions\GoogleCloudStorage\Plugin\Framework\File\Uploader
 * @copyright   Copyright (C) 2021 Aurora Extensions <support@auroraextensions.com>
 * @license     MIT
 */
declare(strict_types=1);

namespace AuroraExtensions\GoogleCloudStorage\Plugin\Framework\File\Uploader;

use Throwable;
use AuroraExtensions\GoogleCloudStorage\Api\StorageObjectManagementInterface;
use AuroraExtensions\GoogleCloudStorage\Component\ModuleConfigTrait;
use AuroraExtensions\GoogleCloudStorage\Component\StorageAdapterTrait;
use AuroraExtensions\GoogleCloudStorage\Model\System\ModuleConfig;
use AuroraExtensions\ModuleComponents\Model\Utils\PathUtils;
use Magento\Framework\File\Uploader as FileUploader;
use Magento\Framework\Filesystem\Driver\File;
use Magento\MediaStorage\Helper\File\Storage\Database as StorageHelper;
use Psr\Log\LoggerInterface;

class ObjectUploader
{
    /**
     * @var ModuleConfig $moduleConfig
     * @method ModuleConfig getConfig()
     * ---
     * @var StorageObjectManagementInterface $storageAdapter
     * @method StorageObjectManagementInterface getStorage()
     */
    use ModuleConfigTrait, StorageAdapterTrait;

    /** @var File $file */
    private $file;

    /** @var LoggerInterface $logger */
    private $logger;

    /** @var PathUtils $pathUtils */
    private $pathUtils;

    /** @var StorageHelper $storageHelper */
    private $storageHelper;

    /**
     * @param File $file
     * @param LoggerInterface $logger
     * @param ModuleConfig $moduleConfig
     * @param PathUtils $pathUtils
     * @param StorageObjectManagementInterface $storageAdapter
     * @param StorageHelper $storageHelper
     * @return void
     */
    public function __construct(
        File $file,
        LoggerInterface $logger,
        ModuleConfig $moduleConfig,
        PathUtils $pathUtils,
        StorageObjectManagementInterface $storageAdapter,
        StorageHelper $storageHelper
    ) {
        $this->file = $file;
        $this->logger = $logger;
        $this->moduleConfig = $moduleConfig;
        $this->pathUtils = $pathUtils;
        $this->storageAdapter = $storageAdapter;
        $this->storageHelper = $storageHelper;
    }

    /**
     * @param FileUploader $subject
     * @param array|bool $result
     * @param string $destinationFolder
     * @param string|null $newFileName
     * @return array|bool
     */
    public function afterSave(
        FileUploader $subject,
        $result,
        $destinationFolder,
        $newFileName = null
    ) {
        if (!empty($result)) {
            /** @var string $basePath */
            $basePath = (string)($result['path'] ?? '');

            /** @var string $baseName */
            $baseName = (string)($result['file'] ?? '');

            if (!empty($basePath) && !empty($baseName)) {
                /** @var string $realPath */
                $realPath = $this->pathUtils->build(
                    $basePath,
                    $baseName
                );
                $this->upload($realPath);
            }
        }

        return $result;
    }

    /**
     * @param string $path
     * @return void
     */
    private function upload(string $path): void
    {
        try {
            /** @var resource $handle */
            $handle = $this->file->fileOpen($path, 'r');

            /** @var string $filePath */
            $filePath = $this->storageHelper
                ->getMediaRelativePath($path);

            /** @var StorageObjectManagementInterface $storage */
            $storage = $this->getStorage();
            $storage->uploadObject(
                $handle,
                [
                    'name' => $storage->getObjectPath($filePath),
                    'predefinedAcl' => $storage->getObjectAclPolicy(),
                ]
            );
        } catch (Throwable $e) {
            $this->logger->error($e->getMessage());
        }
    }
}
