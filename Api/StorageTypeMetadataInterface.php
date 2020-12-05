<?php
/**
 * StorageTypeMetadataInterface.php
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the MIT license, which
 * is bundled with this package in the file LICENSE.txt.
 *
 * It is also available on the Internet at the following URL:
 * https://docs.auroraextensions.com/magento/extensions/2.x/googlecloudstorage/LICENSE.txt
 *
 * @package        AuroraExtensions\GoogleCloudStorage\Api
 * @copyright      Copyright (C) 2019 Aurora Extensions <support@auroraextensions.com>
 * @license        MIT
 */
declare(strict_types=1);

namespace AuroraExtensions\GoogleCloudStorage\Api;

use Google\Cloud\Storage\StorageClient;

interface StorageTypeMetadataInterface
{
    /** @constant int STORAGE_MEDIA_GCS */
    public const STORAGE_MEDIA_GCS = 2;
}
