<?php
/**
 * InternalStorageList.php
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

use AuroraExtensions\GoogleCloudStorage\Model\File\Storage as GoogleCloudStorage;
use Magento\MediaStorage\{
    Helper\File\Storage as StorageHelper,
    Model\File\Storage as FileStorage
};
use function in_array;

class InternalStorageList
{
    /** @constant array STORAGE_TYPES */
    private const STORAGE_TYPES = [
        FileStorage::STORAGE_MEDIA_FILE_SYSTEM,
        GoogleCloudStorage::STORAGE_MEDIA_GCS,
    ];

    /**
     * @param StorageHelper $subject
     * @param bool $result
     * @param int|null $storage
     * @return bool
     */
    public function afterIsInternalStorage(
        StorageHelper $subject,
        $result,
        $storage = null
    ) {
        return !$result ? in_array($storage, self::STORAGE_TYPES) : $result;
    }
}
