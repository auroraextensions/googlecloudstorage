<?php
/**
 * GoogleCloudStorage.php
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the MIT License, which
 * is bundled with this package in the file LICENSE.txt.
 *
 * It is also available on the Internet at the following URL:
 * https://docs.auroraextensions.com/magento/extensions/2.x/googlecloudstorage/LICENSE.txt
 *
 * @package       AuroraExtensions_GoogleCloudStorage
 * @copyright     Copyright (C) 2019 Aurora Extensions <support@auroraextensions.com>
 * @license       MIT License
 */
declare(strict_types=1);

namespace AuroraExtensions\GoogleCloudStorage\Plugin\MediaStorage\File;

use AuroraExtensions\GoogleCloudStorage\{
    Model\File\Storage as StorageDriver,
    Model\File\Storage\Bucket,
    Model\File\Storage\BucketFactory
};
use Magento\MediaStorage\Model\File\Storage;

class GoogleCloudStorage
{
    /** @property BucketFactory $bucketFactory */
    protected $bucketFactory;

    /**
     * @param BucketFactory $bucketFactory
     * @return void
     */
    public function __construct(
        BucketFactory $bucketFactory
    ) {
        $this->bucketFactory = $bucketFactory;
    }

    /**
     * @param Storage $subject
     * @param AbstractModel|bool
     * @param int|null $storage
     * @param array $params
     */
    public function afterGetStorageModel(
        Storage $subject,
        $result,
        $storage = null,
        $params = []
    ) {
        switch ($storage) {
            case StorageDriver::STORAGE_MEDIA_GCS:
                return $this->bucketFactory->create();
            default:
                return $result;
        }
    }
}
