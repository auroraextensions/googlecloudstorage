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

namespace AuroraExtensions\GoogleCloudStorage\Model\Adapter;

use AuroraExtensions\GoogleCloudStorage\{
    Api\StorageObjectManagementInterface,
    Model\System\ModuleConfig
};
use Google\Cloud\{
    Storage\Bucket,
    Storage\ObjectIterator,
    Storage\StorageClient,
    Storage\StorageObject
};
use Magento\Framework\{
    App\Filesystem\DirectoryList,
    Filesystem
};

class Storage implements StorageObjectManagementInterface
{
    /** @property StorageClient $client */
    protected $client;

    /** @property Filesystem $filesystem */
    protected $filesystem;

    /** @property ModuleConfig $moduleConfig */
    protected $moduleConfig;

    /**
     * @param Filesystem $filesystem
     * @param ModuleConfig $moduleConfig
     * @return void
     */
    public function __construct(
        Filesystem $filesystem,
        ModuleConfig $moduleConfig
    ) {
        $this->filesystem = $filesystem;
        $this->moduleConfig = $moduleConfig;
        $this->init();
    }

    /**
     * @return $this
     */
    protected function init()
    {
        if (!$this->client) {
            $this->client = new StorageClient([
                'projectId'   => $this->getConfig()->getGoogleCloudProject(),
                'keyFilePath' => $this->getConfig()->getJsonKeyFilePath(),
            ]);
        }

        return $this;
    }

    /**
     * @return ModuleConfig
     */
    public function getConfig(): ModuleConfig
    {
        return $this->moduleConfig;
    }

    /**
     * @return StorageClient
     */
    public function getClient(): StorageClient
    {
        return $this->client;
    }

    /**
     * @return Bucket|null
     */
    public function getBucket(): ?Bucket
    {
        return $this->getClient()
            ->bucket($this->getConfig()->getBucketName());
    }

    /**
     * @return string|null
     */
    public function getPrefixPath(): ?string
    {
        /** @var string $prefix */
        $prefix = preg_replace('#//+#', '/', $this->getConfig()->getBucketPrefix());

        if (strlen($prefix) && $prefix[0] === '/') {
            $prefix = ltrim($prefix, '/');
        }

        return $prefix;
    }

    /**
     * @return bool
     */
    public function hasPrefix(): bool
    {
        /** @var string|null $prefix */
        $prefix = $this->getConfig()->getBucketPrefix();
        $prefix = $prefix !== null && !empty($prefix)
            ? $prefix
            : null;

        if ($prefix !== null) {
            return true;
        }

        return false;
    }

    /**
     * @param string $path
     * @return StorageObject|null
     */
    public function getObject(string $path): ?StorageObject
    {
        /** @var Bucket $bucket */
        $bucket = $this->getBucket();

        if ($this->hasPrefix()) {
            $path = $this->getPrefixPath() . '/' . ltrim($path, DIRECTORY_SEPARATOR);
        }

        return $bucket->object($path);
    }

    /**
     * @param array $options
     * @return ObjectIterator<StorageObject>|null
     */
    public function getObjects(array $options = []): ?ObjectIterator
    {
        /** @var Bucket $bucket */
        $bucket = $this->getBucket();

        if ($this->hasPrefix()) {
            /** @var string $prefix */
            $prefix = $this->getPrefixPath();

            if (isset($options['prefix'])) {
                $options['prefix'] = $prefix . '/' . ltrim($options['prefix'], DIRECTORY_SEPARATOR);
            } else {
                $options['prefix'] = $prefix;
            }
        }

        return $bucket->objects($options);
    }

    /**
     * @param string $path
     * @return bool
     */
    public function objectExists(string $path): bool
    {
        /** @var StorageObject|null $object */
        $object = $this->getObject($path);

        return ($object && $object->exists());
    }

    /**
     * @param resource|string $handle
     * @param array $options
     * @return StorageObject|null
     */
    public function uploadObject($handle, array $options = []): ?StorageObject
    {
        if (!is_resource($handle) && !is_string($handle)) {
            return null;
        }

        if ($this->hasPrefix()) {
            /** @var string $prefix */
            $prefix = $this->getPrefixPath();

            if (isset($options['name'])) {
                $options['name'] = $prefix . '/' . ltrim($options['name'], DIRECTORY_SEPARATOR);
            } else {
                /** @var array $metadata */
                $metadata = stream_get_meta_data($handle);

                /** @var string $mediaBaseDir */
                $mediaBaseDir = rtrim($this->getMediaBaseDirectory(), DIRECTORY_SEPARATOR);

                /** @var string $absolutePath */
                $absolutePath = realpath($metadata['uri']);

                /** @var string $relativePath */
                $relativePath = ltrim(
                    str_replace($mediaBaseDir, '', $absolutePath),
                    DIRECTORY_SEPARATOR
                );

                /* Set bucket-prefixed, absolute pathname on $options['name']. */
                $options['name'] = $prefix . '/' . ltrim($mediaBaseDir, DIRECTORY_SEPARATOR) . '/' . $relativePath;
            }
        }

        return $this->getBucket()->upload($handle, $options);
    }

    /**
     * @param string $source
     * @param string $target
     * @return StorageObject|null
     */
    public function copyObject(string $source, string $target): ?StorageObject
    {
        if (!$this->objectExists($source)) {
            return null;
        }

        if ($this->hasPrefix()) {
            $target = $this->getPrefixPath() . '/' . ltrim($target, DIRECTORY_SEPARATOR);
        }

        /** @var StorageObject $object */
        $object = $this->getObject($source);

        if ($object->exists()) {
            return $object->copy($target);
        }

        return null;
    }

    /**
     * @param string $source
     * @param string $target
     * @return StorageObject|null
     */
    public function renameObject(string $source, string $target): ?StorageObject
    {
        if (!$this->objectExists($source)) {
            return null;
        }

        if ($this->hasPrefix()) {
            $target = $this->getPrefixPath() . '/' . ltrim($target, DIRECTORY_SEPARATOR);
        }

        /** @var StorageObject $object */
        $object = $this->getObject($source);

        if ($object->exists()) {
            return $object->rename($target);
        }

        return null;
    }

    /**
     * @param string $path
     * @return bool
     */
    public function deleteObject(string $path): bool
    {
        if (!$this->objectExists($path)) {
            return false;
        }

        /** @var StorageObject $object */
        $object = $this->getObject($path);

        if ($object->exists()) {
            $object->delete();
        }

        return !$this->objectExists($path);
    }

    /**
     * @param array $options
     * @return $this
     */
    public function deleteAllObjects(array $options = []): StorageObjectManagementInterface
    {
        /** @var ObjectIterator<StorageObject> $objects */
        $objects = $this->getObjects($options);

        /** @var StorageObject $object */
        foreach ($objects as $object) {
            if ($object->exists()) {
                $object->delete();
            }
        }

        return $this;
    }

    /**
     * @return string
     */
    public function getMediaBaseDirectory(): string
    {
        return $this->filesystem
            ->getDirectoryRead(DirectoryList::MEDIA)
            ->getAbsolutePath();
    }
}
