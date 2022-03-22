<?php

namespace AuroraExtensions\GoogleCloudStorage\Plugin\Catalog\View\Asset;

use Magento\Framework\View\Asset\LocalInterface;
use AuroraExtensions\GoogleCloudStorage\Model\File\Storage;
use Magento\Catalog\Model\Product\Media\Config;
use Magento\Framework\Filesystem;
use Magento\Framework\Filesystem\Directory\WriteInterface;
use Magento\Framework\App\Filesystem\DirectoryList;
use OuterEdge\Base\Helper\Image as ImageHelper;

class Image
{
    /**
     * @var Storage\BucketFactory
     */
    protected $storageFactory;

    /**
     * @var WriteInterface
     */
    protected $mediaDirectory;

    /**
     * @var Config
     */
    protected $mediaConfig;

    /**
     * @var ImageHelper
     */
    protected $imageHelper;

    public function __construct(
        Filesystem $filesystem,
        Config $mediaConfig,
        Storage\BucketFactory $storageFactory,
        ImageHelper $imageHelper
    ) {
        $this->mediaDirectory = $filesystem->getDirectoryRead(DirectoryList::MEDIA);
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
        if (!$this->mediaDirectory->isFile($relativeFileName)) {
            $storage->downloadFile($relativeFileName);
        }

        // Download the cached image
        if ($image->getModule() == 'cache') {
            $cacheFilename = $this->imageHelper->prepareFilename($image->getPath());
            if (!$this->mediaDirectory->isFile($cacheFilename)) {
                $storage->downloadFile($cacheFilename);
            }
        }
    }
}
