<?php

namespace AuroraExtensions\GoogleCloudStorage\Model\File\Storage;

class Synchronization
{
    /**
     * @var BucketFactory
     */
    protected $storageFactory;

    public function __construct(
        BucketFactory $storageFactory
    ) {
        $this->storageFactory = $storageFactory;
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

        $storage->downloadFile($relativeFileName);
    }
}
