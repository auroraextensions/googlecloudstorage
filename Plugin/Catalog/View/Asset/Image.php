<?php

namespace AuroraExtensions\GoogleCloudStorage\Plugin\Catalog\View\Asset;

use Magento\Framework\View\Asset\LocalInterface;
use AuroraExtensions\GoogleCloudStorage\Model\File\Storage;
use Magento\Catalog\Model\Product\Media\Config;
use OuterEdge\Base\Helper\Image as ImageHelper;

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

    /**
     * @var ImageHelper
     */
    protected $imageHelper;

    public function __construct(
        Config $mediaConfig,
        Storage\BucketFactory $storageFactory,
        ImageHelper $imageHelper
    ) {
        $this->mediaConfig    = $mediaConfig;
        $this->storageFactory = $storageFactory;
        $this->imageHelper    = $imageHelper;
    }

    public function beforeGetUrl(LocalInterface $image)
    {
        /** @var $storage Storage\Bucket */
        $storage = $this->storageFactory->create();

        if (!$storage->getStorage()->isEnabled()) {
            return;
        }

        // Download the main image
        $relativeFileName = $this->mediaConfig->getBaseMediaPath() . $image->getFilePath();
        $storage->downloadFile($relativeFileName);

        // Download the cached image
        if ($image->getModule() == 'cache') {
            $cacheFilename = $this->imageHelper->prepareFilename($image->getPath());
            $storage->downloadFile($cacheFilename);
        }
    }
}
