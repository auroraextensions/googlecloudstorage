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
 * @package     AuroraExtensions\GoogleCloudStorage\Plugin\Catalog\Product\Gallery
 * @copyright   Copyright (C) 2021 Aurora Extensions <support@auroraextensions.com>
 * @license     MIT
 */
declare(strict_types=1);

namespace AuroraExtensions\GoogleCloudStorage\Plugin\Catalog\Product\Gallery;

use AuroraExtensions\GoogleCloudStorage\Api\StorageObjectManagementInterface;
use AuroraExtensions\GoogleCloudStorage\Component\ModuleConfigTrait;
use AuroraExtensions\GoogleCloudStorage\Component\StorageAdapterTrait;
use AuroraExtensions\GoogleCloudStorage\Model\System\ModuleConfig;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Model\Product\Media\ConfigInterface;
use Magento\Framework\EntityManager\Operation\ExtensionInterface;
use Magento\Framework\Filesystem\Driver\File;
use Magento\Framework\Image\Adapter\AdapterInterface;
use Magento\MediaStorage\Helper\File\Storage\Database as StorageHelper;
use Psr\Log\LoggerInterface;
use Throwable;

use function implode;

use const DIRECTORY_SEPARATOR;

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

    /** @var ConfigInterface $mediaConfig */
    private $mediaConfig;

    /** @var StorageHelper $storageHelper */
    private $storageHelper;

    /**
     * @param File $file
     * @param LoggerInterface $logger
     * @param ConfigInterface $mediaConfig
     * @param ModuleConfig $moduleConfig
     * @param StorageObjectManagementInterface $storageAdapter
     * @param StorageHelper $storageHelper
     * @return void
     */
    public function __construct(
        File $file,
        LoggerInterface $logger,
        ConfigInterface $mediaConfig,
        ModuleConfig $moduleConfig,
        StorageObjectManagementInterface $storageAdapter,
        StorageHelper $storageHelper
    ) {
        $this->file = $file;
        $this->logger = $logger;
        $this->mediaConfig = $mediaConfig;
        $this->moduleConfig = $moduleConfig;
        $this->storageAdapter = $storageAdapter;
        $this->storageHelper = $storageHelper;
    }

    /**
     * @param ExtensionInterface $subject
     * @param ProductInterface $result
     * @return ProductInterface
     */
    public function afterExecute(
        ExtensionInterface $subject,
        ProductInterface $result
    ): ProductInterface {
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
    ): void {
        /** @var string $basePath */
        $basePath = $this->storageHelper->getMediaBaseDir();

        /** @var string $filePath */
        $filePath = $this->mediaConfig->getMediaPath($image['file']);

        /** @var string $realPath */
        $realPath = implode(
            DIRECTORY_SEPARATOR,
            [
                $basePath,
                $filePath,
            ]
        );

        try {
            /** @var resource $handle */
            $handle = $this->file->fileOpen($realPath, 'r');

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
