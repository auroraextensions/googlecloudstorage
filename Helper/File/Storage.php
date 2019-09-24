<?php
/**
 * Storage.php
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

namespace AuroraExtensions\GoogleCloudStorage\Helper\File;

use AuroraExtensions\GoogleCloudStorage\Model\File\Storage as GoogleCloudStorage;
use Magento\MediaStorage\{
    Helper\File\Storage as StorageHelper,
    Model\File\Storage as FileStorage
};

class Storage extends StorageHelper
{
    /** @property array $_internalStorageList */
    protected $_internalStorageList = [
        FileStorage::STORAGE_MEDIA_FILE_SYSTEM,
        GoogleCloudStorage::STORAGE_MEDIA_GCS
    ];
}
