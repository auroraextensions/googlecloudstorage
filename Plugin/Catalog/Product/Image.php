<?php

namespace AuroraExtensions\GoogleCloudStorage\Plugin\Catalog\Product;

use AuroraExtensions\GoogleCloudStorage\Model\File\Storage;
use Magento\Catalog\Model\Product\Image as ProductImage;
use Magento\Catalog\Model\Product\Media\Config;

class Image
{
    /**
     * @var Storage\BucketFactory
     */
    protected $storageFactory;

    /**
     * @var Config
     */
    protected $mediaConfig;

    public function __construct(
        Config $mediaConfig,
        Storage\BucketFactory $storageFactory
    ) {
        $this->mediaConfig    = $mediaConfig;
        $this->storageFactory = $storageFactory;
    }

    public function beforeSetBaseFile(ProductImage $image, $file)
    {
        /** @var $storage Storage\Bucket */
        $storage = $this->storageFactory->create();

        if (!$storage->getStorage()->isEnabled()) {
            return;
        }

        $relativeFileName = $this->mediaConfig->getBaseMediaPath() . $file;

        if ($this->mediaDirectory->isFile($relativeFileName)) {
            return;
        }

        $storage->downloadFile($relativeFileName);
    }
}
