<?php
/**
 * SynchronizeStorageParams.php
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the MIT license, which
 * is bundled with this package in the file LICENSE.txt.
 *
 * It is also available on the Internet at the following URL:
 * https://docs.auroraextensions.com/magento/extensions/2.x/googlecloudstorage/LICENSE.txt
 *
 * @package     AuroraExtensions\GoogleCloudStorage\Plugin\MediaStorage\Config
 * @copyright   Copyright (C) 2021 Aurora Extensions <support@auroraextensions.com>
 * @license     MIT
 */
declare(strict_types=1);

namespace AuroraExtensions\GoogleCloudStorage\Plugin\MediaStorage\Config;

use AuroraExtensions\GoogleCloudStorage\Api\StorageTypeMetadataInterface;
use Magento\MediaStorage\{
    Block\System\Config\System\Storage\Media\Synchronize,
    Model\File\Storage as FileStorage,
    Model\File\Storage\Flag
};

class SynchronizeStorageParams
{
    /** @var FileStorage $storage */
    private $storage;

    /**
     * @param FileStorage $storage
     * @return void
     */
    public function __construct(FileStorage $storage)
    {
        $this->storage = $storage;
    }

    /**
     * @param Synchronize $subject
     * @param array $result
     * @return array
     */
    public function afterGetSyncStorageParams(
        Synchronize $subject,
        array $result
    ): array {
        /** @var Flag $flag */
        $flag = $this->storage->getSyncFlag();

        /** @var array $data */
        $data = (array) $flag->getFlagData();

        /** @var int $storageType */
        $storageType = (int)($data['destination_storage_type'] ?? null);

        if ($storageType === StorageTypeMetadataInterface::STORAGE_MEDIA_GCS) {
            $result = [
                'storage_type' => $storageType,
                'connection_name' => '',
            ];
        }

        return $result;
    }
}
