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
 * @package       AuroraExtensions\GoogleCloudStorage\Plugin\MediaStorage\File
 * @copyright     Copyright (C) 2019 Aurora Extensions <support@auroraextensions.com>
 * @license       MIT
 */
declare(strict_types=1);

namespace AuroraExtensions\GoogleCloudStorage\Plugin\MediaStorage\File;

use AuroraExtensions\GoogleCloudStorage\Model\File\Storage as GoogleCloudStorage;
use Magento\MediaStorage\{
    Helper\File\Storage as StorageHelper,
    Model\File\Storage as FileStorage
};

class InternalStorageList
{
    /** @property array $storageList */
    protected $storageList = [
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
        if (!$result) {
            return in_array($storage, $this->storageList);
        }

        return $result;
    }
}
