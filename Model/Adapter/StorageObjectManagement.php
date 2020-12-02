<?php
/**
 * Storage.php
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the MIT license, which
 * is bundled with this package in the file LICENSE.txt.
 *
 * It is also available on the Internet at the following URL:
 * https://docs.auroraextensions.com/magento/extensions/2.x/googlecloudstorage/LICENSE.txt
 *
 * @package       AuroraExtensions\GoogleCloudStorage\Model\Adapter
 * @copyright     Copyright (C) 2019 Aurora Extensions <support@auroraextensions.com>
 * @license       MIT
 */
declare(strict_types=1);

namespace AuroraExtensions\GoogleCloudStorage\Model\Adapter;

use AuroraExtensions\GoogleCloudStorage\{
    Api\StorageObjectManagementInterface,
    Component\ModuleConfigTrait,
    Model\System\ModuleConfig
};
use Google\Cloud\{
    Storage\Bucket,
    Storage\ObjectIterator,
    Storage\StorageClient,
    Storage\StorageClientFactory,
    Storage\StorageObject
};
use Magento\Framework\{
    App\Filesystem\DirectoryList,
    Filesystem
};

use const DIRECTORY_SEPARATOR;
use function implode;
use function is_resource;
use function is_string;
use function ltrim;
use function preg_replace;
use function realpath;
use function rtrim;
use function stream_get_meta_data;
use function strlen;
use function str_replace;
use function trim;

class Storage implements StorageObjectManagementInterface
{
    /**
     * @var ModuleConfig $moduleConfig
     * @method ModuleConfig getConfig()
     */
    use ModuleConfigTrait;

    /** @constant string DIRSEP_REGEX */
    private const DIRSEP_REGEX = '#//+#';

    /** @var StorageClient $client */
    private $client;

    /** @var Filesystem $filesystem */
    private $filesystem;

    /**
     * @param Filesystem $filesystem
     * @param ModuleConfig $moduleConfig
     * @param StorageClientFactory $clientFactory
     * @return void
     */
    public function __construct(
        Filesystem $filesystem,
        ModuleConfig $moduleConfig,
        StorageClientFactory $clientFactory
    ) {
        $this->filesystem = $filesystem;
        $this->moduleConfig = $moduleConfig;
        $this->client = $clientFactory->create([
            'projectId' => $moduleConfig->getGoogleCloudProject(),
            'keyFilePath' => $moduleConfig->getJsonKeyFilePath(),
        ]);
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
    public function getPrefix(): ?string
    {
        /** @var string $prefix */
        $prefix = preg_replace(
            self::DIRSEP_REGEX,
            DIRECTORY_SEPARATOR,
            $this->getConfig()->getBucketPrefix()
        );

        if (strlen($prefix) && $prefix[0] === DIRECTORY_SEPARATOR) {
            $prefix = ltrim($prefix, DIRECTORY_SEPARATOR);
        }

        return $prefix;
    }

    /**
     * @param string $path
     * @return string
     */
    public function getPrefixedFilePath(string $path): string
    {
        /** @var string $prefix */
        $prefix = DIRECTORY_SEPARATOR . trim($this->getPrefix(), DIRECTORY_SEPARATOR);

        return implode(DIRECTORY_SEPARATOR, [
            $prefix,
            trim($path, DIRECTORY_SEPARATOR),
        ]);
    }

    /**
     * @return bool
     */
    public function hasPrefix(): bool
    {
        /** @var string|null $prefix */
        $prefix = $this->getConfig()->getBucketPrefix();

        if (!empty($prefix)) {
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
            $path = implode(DIRECTORY_SEPARATOR, [
                $this->getPrefix(),
                ltrim($path, DIRECTORY_SEPARATOR),
            ]);
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
            $prefix = $this->getPrefix();

            if (isset($options['prefix'])) {
                $options['prefix'] = implode(DIRECTORY_SEPARATOR, [
                    $prefix,
                    ltrim($options['prefix'], DIRECTORY_SEPARATOR),
                ]);
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
            $prefix = $this->getPrefix();

            if (isset($options['name'])) {
                $options['name'] = implode(DIRECTORY_SEPARATOR, [
                    $prefix,
                    ltrim($options['name'], DIRECTORY_SEPARATOR),
                ]);
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
                $options['name'] = implode(DIRECTORY_SEPARATOR, [
                    $prefix,
                    ltrim($mediaBaseDir, DIRECTORY_SEPARATOR),
                    $relativePath,
                ]);
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
            $target = implode(DIRECTORY_SEPARATOR, [
                $this->getPrefix(),
                ltrim($target, DIRECTORY_SEPARATOR),
            ]);
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
            $target = implode(DIRECTORY_SEPARATOR, [
                $this->getPrefix(),
                ltrim($target, DIRECTORY_SEPARATOR),
            ]);
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
