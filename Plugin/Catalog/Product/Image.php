<?php

namespace AuroraExtensions\GoogleCloudStorage\Plugin\Catalog\Product;

use AuroraExtensions\GoogleCloudStorage\Model\File\Storage\Synchronization;
use Magento\Catalog\Model\Product\Image as ProductImage;
use Magento\Catalog\Model\Product\Media\Config;

class Image
{
    /**
     * @var Synchronization
     */
    protected $synchronization;

    /**
     * @var Config
     */
    protected $mediaConfig;

    public function __construct(
        Synchronization $synchronization,
        Config $mediaConfig
    ) {
        $this->synchronization = $synchronization;
        $this->mediaConfig    = $mediaConfig;
    }

    public function beforeSetBaseFile(ProductImage $image, $file)
    {
        $this->synchronization->synchronize($this->mediaConfig->getBaseMediaPath() . $file);
    }
}
