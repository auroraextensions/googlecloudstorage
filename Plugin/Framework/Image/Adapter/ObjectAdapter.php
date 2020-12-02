<?php
/**
 * ObjectAdapter.php
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the MIT license, which
 * is bundled with this package in the file LICENSE.txt.
 *
 * It is also available on the Internet at the following URL:
 * https://docs.auroraextensions.com/magento/extensions/2.x/googlecloudstorage/LICENSE.txt
 *
 * @package       AuroraExtensions\GoogleCloudStorage\Plugin\Framework\Image\Adapter
 * @copyright     Copyright (C) 2019 Aurora Extensions <support@auroraextensions.com>
 * @license       MIT
 */
declare(strict_types=1);

namespace AuroraExtensions\GoogleCloudStorage\Plugin\Framework\Image\Adapter;

use Exception;
use AuroraExtensions\GoogleCloudStorage\{
    Api\StorageObjectManagementInterface,
    Component\ModuleConfigTrait,
    Component\StorageAdapterTrait,
    Model\System\ModuleConfig
};
use Magento\Framework\{
    Filesystem\Driver\File as FilesystemDriver,
    Image\Adapter\AdapterInterface
};
use Magento\MediaStorage\Helper\File\Storage\Database as StorageHelper;
use Psr\Log\LoggerInterface;

class ObjectAdapter
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
     * @param AdapterInterface $subject
     * @param void $result
     * @param string|null $destination
     * @param string|null $newName
     * @return void
     */
    public function afterSave(
        AdapterInterface $subject,
        $result,
        $destination = null,
        $newName = null
    ) {
        if ($destination !== null) {
            /** @var StorageObjectManagementInterface $storage */
            $storage = $this->getStorage();

            try {
                /** @var string $filePath */
                $filePath = $this->storageHelper
                    ->getMediaRelativePath($destination);

                /** @var string $objectPath */
                $objectPath = $storage->hasPrefix()
                    ? $storage->getPrefixedFilePath($filePath)
                    : $filePath;

                /** @var string $aclPolicy */
                $aclPolicy = $this->getConfig()
                    ->getBucketAclPolicy();

                /** @var array $options */
                $options = [
                    'name' => $filePath,
                    'predefinedAcl' => $aclPolicy,
                ];

                /** @var resource $handle */
                $handle = $this->filesystemDriver
                    ->fileOpen($destination, 'r');

                $storage->uploadObject($handle, $options);
            } catch (Exception $e) {
                $this->logger->critical($e->getMessage());
            }
        }
    }
}
