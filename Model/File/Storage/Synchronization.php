<?php

namespace AuroraExtensions\GoogleCloudStorage\Model\File\Storage;

use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Filesystem\Directory\WriteInterface as DirectoryWrite;

class Synchronization
{
    /**
     * @var BucketFactory
     */
    protected $bucket;

    /**
     * File stream handler
     *
     * @var DirectoryWrite
     */
    protected $mediaDirectory;

    public function __construct(
        DirectoryWrite $directory,
        BucketFactory $bucket
    ) {
        $this->mediaDirectory = $directory;
        $this->bucket = $bucket;
    }

    /**
     * Synchronize file from GCS to local filesystem
     *
     * @param string $relativeFileName
     * @return void
     */
    public function synchronize($relativeFileName)
    {
        $storage = $this->bucket->loadByFilename($relativeFileName);

        if ($storage->getId()) {
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
