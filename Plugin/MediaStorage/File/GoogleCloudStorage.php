<?php
/**
 * GoogleCloudStorage.php
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the MIT license, which
 * is bundled with this package in the file LICENSE.txt.
 *
 * It is also available on the Internet at the following URL:
 * https://docs.auroraextensions.com/magento/extensions/2.x/googlecloudstorage/LICENSE.txt
 *
 * @package     AuroraExtensions\GoogleCloudStorage\Plugin\MediaStorage\File
 * @copyright   Copyright (C) 2021 Aurora Extensions <support@auroraextensions.com>
 * @license     MIT
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
    /** @var BucketFactory $bucketFactory */
    private $bucketFactory;

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
     * @param AbstractModel|bool $result
     * @param int|null $storage
     * @param array $params
     * @return AbstractModel|bool
     */
    public function afterGetStorageModel(
        Storage $subject,
        $result,
        $storage = null,
        $params = []
    ) {
        return $storage !== StorageDriver::STORAGE_MEDIA_GCS ? $result : $this->bucketFactory->create();
    }
}
