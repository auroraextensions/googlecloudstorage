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
    Exception\FileSystemException,
    EntityManager\Operation\ExtensionInterface,
    Filesystem\Driver\File as FileDriver,
    Image\Adapter\AdapterInterface
};
use Magento\MediaStorage\Helper\File\Storage\Database as StorageHelper;
use Psr\Log\LoggerInterface;

use const DIRECTORY_SEPARATOR;
use function implode;

class ObjectUploader
{
    /**
     * @var ModuleConfig $moduleConfig
     * @var StorageObjectManagementInterface $storageAdapter
     * @method ModuleConfig getConfig()
     * @method StorageObjectManagementInterface getStorage()
     */
    use ModuleConfigTrait, StorageAdapterTrait;

    /** @var FileDriver $fileDriver */
    private $fileDriver;

    /** @var LoggerInterface $logger */
    private $logger;

    /** @var ConfigInterface $mediaConfig */
    private $mediaConfig;

    /** @var StorageHelper $storageHelper */
    private $storageHelper;

    /**
     * @param FileDriver $fileDriver
     * @param LoggerInterface $logger
     * @param ConfigInterface $mediaConfig
     * @param ModuleConfig $moduleConfig
     * @param StorageObjectManagementInterface $storageAdapter
     * @param StorageHelper $storageHelper
     * @return void
     */
    public function __construct(
        FileDriver $fileDriver,
        LoggerInterface $logger,
        ConfigInterface $mediaConfig,
        ModuleConfig $moduleConfig,
        StorageObjectManagementInterface $storageAdapter,
        StorageHelper $storageHelper
    ) {
        $this->fileDriver = $fileDriver;
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
        /** @var string $basePath */
        $basePath = $this->storageHelper->getMediaBaseDir();

        /** @var string $filePath */
        $filePath = $this->mediaConfig->getMediaPath($image['file']);

        /** @var string $realPath */
        $realPath = implode(DIRECTORY_SEPARATOR, [
            $basePath,
            $filePath,
        ]);

        /** @var string $objectPath */
        $objectPath = $this->getStorage()->getObjectPath($filePath);

        /** @var string $aclPolicy */
        $aclPolicy = $this->getStorage()->getObjectAclPolicy();

        /** @var array $options */
        $options = [
            'name' => $objectPath,
            'predefinedAcl' => $aclPolicy,
        ];

        try {
            /** @var resource $handle */
            $handle = $this->fileDriver->fileOpen($realPath, 'r');
            $this->getStorage()->uploadObject($handle, $options);
        } catch (FileSystemException | Exception $e) {
            $this->logger->critical($e->getMessage());
        }
    }
}
