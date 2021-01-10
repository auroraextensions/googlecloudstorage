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
 * @package     AuroraExtensions\GoogleCloudStorage\Plugin\MediaStorage\File\Storage
 * @copyright   Copyright (C) 2021 Aurora Extensions <support@auroraextensions.com>
 * @license     MIT
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

use const DIRECTORY_SEPARATOR;
use function implode;

class FileProcessor
{
    /**
     * @var ModuleConfig $moduleConfig
     * @var StorageObjectManagementInterface $storageAdapter
     * @method ModuleConfig getConfig()
     * @method StorageObjectManagementInterface getStorage()
     */
    use ModuleConfigTrait, StorageAdapterTrait;

    /** @var LoggerInterface $logger */
    private $logger;

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

        /** @var string $filename */
        $filename = $file['filename'] ?? '';

        /** @var string $dirname */
        $dirname = $file['directory'] ?? '';

        if (!empty($dirname)) {
            $filename = implode(DIRECTORY_SEPARATOR, [
                $dirname,
                $filename,
            ]);
        }

        /** @var string $objectPath */
        $objectPath = $this->getStorage()->getObjectPath($filename);

        /** @var string $aclPolicy */
        $aclPolicy = $this->getStorage()->getObjectAclPolicy();

        try {
            $this->getStorage()->uploadObject($file['content'], [
                'name' => $objectPath,
                'predefinedAcl' => $aclPolicy,
            ]);
        } catch (Exception $e) {
            $this->logger->critical($e->getMessage());
        }

        return $result;
    }
}
