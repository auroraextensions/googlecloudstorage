<?php
/**
 * Bucket.php
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the MIT license, which
 * is bundled with this package in the file LICENSE.txt.
 *
 * It is also available on the Internet at the following URL:
 * https://docs.auroraextensions.com/magento/extensions/2.x/googlecloudstorage/LICENSE.txt
 *
 * @package       AuroraExtensions\GoogleCloudStorage\Model\ResourceModel\File\Storage
 * @copyright     Copyright (C) 2019 Aurora Extensions <support@auroraextensions.com>
 * @license       MIT
 */
declare(strict_types=1);

namespace AuroraExtensions\GoogleCloudStorage\Model\ResourceModel\File\Storage;

use AuroraExtensions\GoogleCloudStorage\Api\StorageObjectManagementInterface;

class Bucket
{
    /** @property StorageObjectManagementInterface $storageAdapter */
    protected $storageAdapter;

    /**
     * @param StorageObjectManagementInterface $storageAdapter
     * @return void
     */
    public function __construct(
        StorageObjectManagementInterface $storageAdapter
    ) {
        $this->storageAdapter = $storageAdapter;
    }

    /**
     * @param string $dirname
     * @return void
     */
    public function deleteFolder(string $dirname = ''): void
    {
        /* Trim trailing slash from $dirname. */
        $dirname = rtrim($dirname, '/');

        if (strlen($dirname)) {
            /* Append slash back to $dirname. */
            $dirname .= '/';

            /* Delete all objects with $dirname prefix. */
            $this->storageAdapter->deleteAllObjects(['prefix' => $dirname]);
        }
    }
}
