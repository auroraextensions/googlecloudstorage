<?php

namespace AuroraExtensions\GoogleCloudStorage\Plugin\Catalog\Product;

use AuroraExtensions\GoogleCloudStorage\Model\File\Storage;
use Magento\Catalog\Model\Product\Image as ProductImage;
use Magento\Catalog\Model\Product\Media\Config;
use Magento\Framework\Filesystem;
use Magento\Framework\Filesystem\Directory\WriteInterface;
use Magento\Framework\App\Filesystem\DirectoryList;

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

    public function __construct(
        Filesystem $filesystem,
        Config $mediaConfig,
        Storage\BucketFactory $storageFactory
    ) {
        $this->mediaDirectory = $filesystem->getDirectoryWrite(DirectoryList::MEDIA);
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

        try {
            $storage->loadByFilename($relativeFileName);
        } catch (\Exception $e) {
        }
        if ($storage->getId()) {
            /** @var WriteInterface $file */
            $file = $this->mediaDirectory->openFile($relativeFileName, 'w');
            try {
                $file->lock();
                $file->write($storage->getContent());
                $file->unlock();
                $file->close();
            } catch (FileSystemException $e) {
                $file->close();
            }
        }
    }
}
