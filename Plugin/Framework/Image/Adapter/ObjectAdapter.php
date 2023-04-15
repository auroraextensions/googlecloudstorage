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
 * @package     AuroraExtensions\GoogleCloudStorage\Plugin\Framework\Image\Adapter
 * @copyright   Copyright (C) 2021 Aurora Extensions <support@auroraextensions.com>
 * @license     MIT
 */
declare(strict_types=1);

namespace AuroraExtensions\GoogleCloudStorage\Plugin\Framework\Image\Adapter;

use AuroraExtensions\GoogleCloudStorage\Api\StorageObjectManagementInterface;
use AuroraExtensions\GoogleCloudStorage\Component\ModuleConfigTrait;
use AuroraExtensions\GoogleCloudStorage\Component\StorageAdapterTrait;
use AuroraExtensions\GoogleCloudStorage\Model\System\ModuleConfig;
use Magento\Framework\Filesystem\Driver\File;
use Magento\Framework\Image\Adapter\AdapterInterface;
use Magento\MediaStorage\Helper\File\Storage\Database as StorageHelper;
use Psr\Log\LoggerInterface;
use Throwable;

class ObjectAdapter
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

    /** @var StorageObjectManagementInterface $storageAdapter */
    private $storageAdapter;

    /** @var StorageHelper $storageHelper */
    private $storageHelper;

    /**
     * @param File $file
     * @param LoggerInterface $logger
     * @param ModuleConfig $moduleConfig
     * @param StorageObjectManagementInterface $storageAdapter
     * @param StorageHelper $storageHelper
     * @return void
     */
    public function __construct(
        File $file,
        LoggerInterface $logger,
        ModuleConfig $moduleConfig,
        StorageObjectManagementInterface $storageAdapter,
        StorageHelper $storageHelper
    ) {
        $this->file = $file;
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
        if (!empty($destination)) {
            /** @var string $filePath */
            $filePath = $this->storageHelper
                ->getMediaRelativePath($destination);

            try {
                /** @var resource $handle */
                $handle = $this->file->fileOpen($destination, 'r');

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
}
