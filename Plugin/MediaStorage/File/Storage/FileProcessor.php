<?php
/**
 * FileProcessor.php
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

namespace AuroraExtensions\GoogleCloudStorage\Plugin\MediaStorage\File\Storage;

use Exception;
use AuroraExtensions\GoogleCloudStorage\{
    Api\StorageObjectManagementInterface,
    Model\File\Storage\Bucket,
    Model\System\ModuleConfig
};
use Magento\Framework\Filesystem;
use Magento\MediaStorage\Model\File\Storage\File as FileResource;
use Psr\Log\LoggerInterface;

class FileProcessor
{
    /** @property Filesystem $filesystem */
    protected $filesystem;

    /** @property LoggerInterface $logger */
    protected $logger;

    /** @property ModuleConfig $moduleConfig */
    protected $moduleConfig;

    /** @property StorageObjectManagementInterface $storageAdapter */
    protected $storageAdapter;

    /**
     * @param Filesystem $filesystem
     * @param LoggerInterface $logger
     * @param ModuleConfig $moduleConfig
     * @param StorageObjectManagementInterface $storageAdapter
     * @return void
     */
    public function __construct(
        Filesystem $filesystem,
        LoggerInterface $logger,
        ModuleConfig $moduleConfig,
        StorageObjectManagementInterface $storageAdapter
    ) {
        $this->filesystem = $filesystem;
        $this->logger = $logger;
        $this->moduleConfig = $moduleConfig;
        $this->storageAdapter = $storageAdapter;
    }

    /**
     * @return StorageObjectManagementInterface
     */
    public function getStorage(): StorageObjectManagementInterface
    {
        return $this->storageAdapter;
    }

    /**
     * @return ModuleConfig
     */
    public function getConfig(): ModuleConfig
    {
        return $this->moduleConfig;
    }

    /**
     * @param FileResource $subject
     * @param bool $result
     * @param string $filePath
     * @param string $content
     * @param bool $overwrite
     * @return bool
     */
    public function afterSaveFile(
        FileResource $subject,
        $result,
        $filePath,
        $content,
        $overwrite
    ) {
        if (!$result) {
            return $result;
        }

        try {
            /** @var StorageObjectManagementInterface $storage */
            $storage = $this->getStorage();

            /* Prepend bucket prefix, if needed. */
            $filePath = $storage->hasPrefix()
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

            $storage->uploadObject($content, $options);
        } catch (Exception $e) {
            $this->logger->critical($e);
        }

        return $result;
    }
}
