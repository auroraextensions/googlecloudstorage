<?php
/**
 * ObjectUploader.php
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the MIT License, which
 * is bundled with this package in the file LICENSE.txt.
 *
 * It is also available on the Internet at the following URL:
 * https://docs.auroraextensions.com/magento/extensions/2.x/googlecloudstorage/LICENSE.txt
 *
 * @package       AuroraExtensions_GoogleCloudStorage
 * @copyright     Copyright (C) 2019 Aurora Extensions <support@auroraextensions.com>
 * @license       MIT License
 */
declare(strict_types=1);

namespace AuroraExtensions\GoogleCloudStorage\Plugin\Framework\File\Uploader;

use Exception;
use AuroraExtensions\GoogleCloudStorage\{
    Api\StorageObjectManagementInterface,
    Component\ModuleConfigTrait,
    Component\StorageAdapterTrait,
    Model\Adapter\Storage,
    Model\System\ModuleConfig
};
use Magento\Framework\{
    File\Uploader,
    Filesystem\Driver\File as FilesystemDriver
};
use Magento\MediaStorage\Helper\File\Storage\Database as StorageHelper;
use Psr\Log\LoggerInterface;

class ObjectUploader
{
    /** @trait ModuleConfigTrait */
    /** @trait StorageAdapterTrait */
    use ModuleConfigTrait, StorageAdapterTrait;

    /** @property FilesystemDriver $filesystemDriver */
    protected $filesystemDriver;

    /** @property LoggerInterface $logger */
    protected $logger;

    /** @property ModuleConfig $moduleConfig */
    protected $moduleConfig;

    /** @property StorageObjectManagementInterface $storageAdapter */
    protected $storageAdapter;

    /** @property StorageHelper $storageHelper */
    protected $storageHelper;

    /**
     * @param FilesystemDriver $filesystemDriver
     * @param LoggerInterface $logger
     * @param ModuleConfig $moduleConfig
     * @param StorageObjectManagementInterface $storageAdapter
     * @param StorageHelper $storageHelper
     * @return void
     */
    public function __construct(
        FilesystemDriver $filesystemDriver,
        LoggerInterface $logger,
        ModuleConfig $moduleConfig,
        StorageObjectManagementInterface $storageAdapter,
        StorageHelper $storageHelper
    ) {
        $this->filesystemDriver = $filesystemDriver;
        $this->logger = $logger;
        $this->moduleConfig = $moduleConfig;
        $this->storageAdapter = $storageAdapter;
        $this->storageHelper = $storageHelper;
    }

    /**
     * @param Uploader $subject
     * @param array|bool $result
     * @param string $destinationFolder
     * @param string|null $newFileName
     * @return array
     */
    public function afterSave(
        Uploader $subject,
        $result,
        $destinationFolder,
        $newFileName = null
    ) {
        if (!is_array($result)) {
            return $result;
        }

        /** @var StorageObjectManagementInterface $storage */
        $storage = $this->getStorage();

        try {
            /** @var string $realPath */
            $realPath = $result['path'] . $result['file'];

            /** @var string $filePath */
            $filePath = $this->storageHelper
                ->getMediaRelativePath($realPath);

            /** @var string $objectPath */
            $objectPath = $storage->hasPrefix()
                ? $storage->getPrefixedFilePath($filePath)
                : $filePath;

            /** @var string $aclPolicy */
            $aclPolicy = $this->getConfig()
                ->getBucketAclPolicy();

            /** @var array $options */
            $options = [
                'name' => $objectPath,
                'predefinedAcl' => $aclPolicy,
            ];

            /** @var resource $handle */
            $handle = $this->filesystemDriver
                ->fileOpen($realPath, 'r');

            $storage->uploadObject($handle, $options);
        } catch (Exception $e) {
            $this->logger->critical($e->getMessage());
        }

        return $result;
    }
}
