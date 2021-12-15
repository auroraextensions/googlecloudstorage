<?php

namespace AuroraExtensions\GoogleCloudStorage\Model\File\Storage;

use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Filesystem\Directory\WriteInterface as DirectoryWrite;

class Synchronization
{
    /**
     * @var BucketFactory
     */
    protected $storageFactory;

    /**
     * File stream handler
     *
     * @var DirectoryWrite
     */
    protected $mediaDirectory;

    public function __construct(
        BucketFactory $storageFactory,
        DirectoryWrite $directory
    ) {
        $this->storageFactory = $storageFactory;
        $this->mediaDirectory = $directory;
    }

    /**
     * Synchronize file from GCS to local filesystem
     *
     * @param string $relativeFileName
     * @return void
     */
    public function synchronize($relativeFileName)
    {
        /** @var $storage Bucket */
        $storage = $this->storageFactory->create();
        
        if (!$storage->getStorage()->isEnabled()) {
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
