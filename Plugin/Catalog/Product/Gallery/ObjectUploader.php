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
 * @package       AuroraExtensions\GoogleCloudStorage\Plugin\Catalog\Product\Gallery
 * @copyright     Copyright (C) 2019 Aurora Extensions <support@auroraextensions.com>
 * @license       MIT
 */
declare(strict_types=1);

namespace AuroraExtensions\GoogleCloudStorage\Plugin\Catalog\Product\Gallery;

use Exception;
use AuroraExtensions\GoogleCloudStorage\{
    Api\StorageObjectManagementInterface,
    Component\ModuleConfigTrait,
    Component\StorageAdapterTrait,
    Model\System\ModuleConfig
};
use Magento\Catalog\{
    Api\Data\ProductInterface,
    Model\Product\Media\ConfigInterface
};
use Magento\Framework\{
    EntityManager\Operation\ExtensionInterface,
    Filesystem\Driver\File as FilesystemDriver,
    Image\Adapter\AdapterInterface
};
use Magento\MediaStorage\Helper\File\Storage\Database as StorageHelper;
use Psr\Log\LoggerInterface;

class ObjectUploader
{
    use ModuleConfigTrait, StorageAdapterTrait;

    /** @property FilesystemDriver $filesystemDriver */
    protected $filesystemDriver;

    /** @property LoggerInterface $logger */
    protected $logger;

    /** @property ConfigInterface $mediaConfig */
    protected $mediaConfig;

    /** @property ModuleConfig $moduleConfig */
    protected $moduleConfig;

    /** @property StorageObjectManagementInterface $storageAdapter */
    protected $storageAdapter;

    /** @property StorageHelper $storageHelper */
    protected $storageHelper;

    /**
     * @param FilesystemDriver $filesystemDriver
     * @param LoggerInterface $logger
     * @param ConfigInterface $mediaConfig
     * @param ModuleConfig $moduleConfig
     * @param StorageObjectManagementInterface $storageAdapter
     * @param StorageHelper $storageHelper
     * @return void
     */
    public function __construct(
        FilesystemDriver $filesystemDriver,
        LoggerInterface $logger,
        ConfigInterface $mediaConfig,
        ModuleConfig $moduleConfig,
        StorageObjectManagementInterface $storageAdapter,
        StorageHelper $storageHelper
    ) {
        $this->filesystemDriver = $filesystemDriver;
        $this->logger = $logger;
        $this->mediaConfig = $mediaConfig;
        $this->moduleConfig = $moduleConfig;
        $this->storageAdapter = $storageAdapter;
        $this->storageHelper = $storageHelper;
    }

    /**
     * @param ExtensionInterface $subject
     * @param ProductInterface $result
     * @return void
     */
    public function afterExecute(
        ExtensionInterface $subject,
        ProductInterface $result
    ) {
        /** @var string $attrCode */
        $attrCode = $subject->getAttribute()
            ->getAttributeCode();

        /** @var array $value */
        $value = $result->getData($attrCode);

        /** @var array $images */
        $images = $value['images'] ?? [];

        /** @var array $image */
        foreach ($images as $image) {
            if (empty($image['removed'])) {
                $this->processImage($result, $image);
            }
        }

        return $result;
    }

    /**
     * @param ProductInterface $product
     * @param array $image
     * @return void
     */
    private function processImage(
        ProductInterface $product,
        array $image = []
    ): void
    {
        /** @var StorageObjectManagementInterface $storage */
        $storage = $this->getStorage();

        try {
            /** @var string $basePath */
            $basePath = $this->storageHelper
                ->getMediaBaseDir();

            /** @var string $filePath */
            $filePath = $this->mediaConfig
                ->getMediaPath($image['file']);

            /** @var string $realPath */
            $realPath = $basePath . '/' . $filePath;

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
    }
}
