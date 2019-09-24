<?php
/**
 * StorageObjectManagementInterface.php
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the MIT License, which
 * is bundled with this package in the file LICENSE.txt.
 *
 * It is also available on the Internet at the following URL:
 * https://docs.auroraextensions.com/magento/extensions/2.x/googlecloudstorage/LICENSE.txt
 *
 * @package        AuroraExtensions_GoogleCloudStorage
 * @copyright      Copyright (C) 2019 Aurora Extensions <support@auroraextensions.com>
 * @license        MIT License
 */
declare(strict_types=1);

namespace AuroraExtensions\GoogleCloudStorage\Api;

use Google\Cloud\{
    Storage\StorageObject,
    Storage\ObjectIterator
};

interface StorageObjectManagementInterface
{
    /**
     * @param string $path
     * @return \Google\Cloud\Storage\StorageObject|null
     */
    public function getObject(string $path): ?StorageObject;

    /**
     * @param array $options
     * @return \Google\Cloud\Storage\ObjectIterator|null
     */
    public function getObjects(array $options): ?ObjectIterator;

    /**
     * @param string $source
     * @param string $target
     * @return \Google\Cloud\Storage\StorageObject|null
     */
    public function copyObject(string $source, string $target): ?StorageObject;

    /**
     * @param string $source
     * @param string $target
     * @return \Google\Cloud\Storage\StorageObject|null
     */
    public function renameObject(string $source, string $target): ?StorageObject;

    /**
     * @param string $path
     * @return bool
     */
    public function deleteObject(string $path): bool;

    /**
     * @param array $options
     * @return \AuroraExtensions\GoogleCloudStorage\Api\StorageObjectManagementInterface
     */
    public function deleteAllObjects(array $options): StorageObjectManagementInterface;
}
