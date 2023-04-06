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
 * @package     AuroraExtensions\GoogleCloudStorage\Model\ResourceModel\File\Storage
 * @copyright   Copyright (C) 2021 Aurora Extensions <support@auroraextensions.com>
 * @license     MIT
 */
declare(strict_types=1);

namespace AuroraExtensions\GoogleCloudStorage\Model\ResourceModel\File\Storage;

use AuroraExtensions\GoogleCloudStorage\Api\StorageObjectManagementInterface;
use AuroraExtensions\GoogleCloudStorage\Component\StorageAdapterTrait;

use function implode;
use function ltrim;
use function rtrim;

use const DIRECTORY_SEPARATOR;

class Bucket
{
    /**
     * @var StorageObjectManagementInterface $storageAdapter
     * @method StorageObjectManagementInterface getStorage()
     */
    use StorageAdapterTrait;

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
        $dirname = rtrim(
            $dirname,
            DIRECTORY_SEPARATOR
        );

        if (!empty($dirname)) {
            $dirname = $dirname[0] === DIRECTORY_SEPARATOR
                ? implode(DIRECTORY_SEPARATOR, [
                    '',
                    ltrim($dirname, DIRECTORY_SEPARATOR),
                ]) : $dirname;
            $dirname .= DIRECTORY_SEPARATOR;
            $this->getStorage()->deleteAllObjects(['prefix' => $dirname]);
        }
    }
}
