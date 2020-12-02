<?php
/**
 * FileProcessor.php
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the MIT license, which
 * is bundled with this package in the file LICENSE.txt.
 *
 * It is also available on the Internet at the following URL:
 * https://docs.auroraextensions.com/magento/extensions/2.x/googlecloudstorage/LICENSE.txt
 *
 * @package       AuroraExtensions\GoogleCloudStorage\Plugin\MediaStorage\File\Storage
 * @copyright     Copyright (C) 2019 Aurora Extensions <support@auroraextensions.com>
 * @license       MIT
 */
declare(strict_types=1);

namespace AuroraExtensions\GoogleCloudStorage\Plugin\MediaStorage\File\Storage;

use Exception;
use AuroraExtensions\GoogleCloudStorage\{
    Api\StorageObjectManagementInterface,
    Component\ModuleConfigTrait,
    Component\StorageAdapterTrait,
    Model\System\ModuleConfig
};
use Magento\MediaStorage\Model\File\Storage\File;
use Psr\Log\LoggerInterface;

class FileProcessor
{
    /** @trait ModuleConfigTrait */
    /** @trait StorageAdapterTrait */
    use ModuleConfigTrait, StorageAdapterTrait;

    /** @property LoggerInterface $logger */
    protected $logger;

    /** @property ModuleConfig $moduleConfig */
    protected $moduleConfig;

    /** @property StorageObjectManagementInterface $storageAdapter */
    protected $storageAdapter;

    /**
     * @param LoggerInterface $logger
     * @param ModuleConfig $moduleConfig
     * @param StorageObjectManagementInterface $storageAdapter
     * @return void
     */
    public function __construct(
        LoggerInterface $logger,
        ModuleConfig $moduleConfig,
        StorageObjectManagementInterface $storageAdapter
    ) {
        $this->logger = $logger;
        $this->moduleConfig = $moduleConfig;
        $this->storageAdapter = $storageAdapter;
    }

    /**
     * @param File $subject
     * @param bool $result
     * @param array $file
     * @param bool $overwrite
     * @return bool
     */
    public function afterSaveFile(
        File $subject,
        $result,
        $file,
        $overwrite = true
    ) {
        if (!$result) {
            return $result;
        }

        /** @var StorageObjectManagementInterface $storage */
        $storage = $this->getStorage();

        try {
            /** @var string $dirname */
            $dirname = $file['directory'] ?? null;

            /** @var string $filename */
            $filename = $dirname !== null
                ? $dirname . '/' . $file['filename']
                : $file['filename'];

            /** @var string $filePath */
            $filePath = $storage->hasPrefix()
                ? $storage->getPrefixedFilePath($filename)
                : $filename;

            /** @var string $aclPolicy */
            $aclPolicy = $this->getConfig()
                ->getBucketAclPolicy();

            /** @var array $options */
            $options = [
                'name' => $filePath,
                'predefinedAcl' => $aclPolicy,
            ];

            $storage->uploadObject($file['content'], $options);
        } catch (Exception $e) {
            $this->logger->critical($e->getMessage());
        }

        return $result;
    }
}
