<?php
/**
 * StorageObjectManagement.php
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
    Storage\StorageObject
};
use Magento\Framework\{
    App\Filesystem\DirectoryList,
    Filesystem,
    Filesystem\Driver\File as FileDriver
};
use Psr\Http\{
    Message\StreamInterface,
    Message\StreamInterfaceFactory
};

use const DIRECTORY_SEPARATOR;
use function implode;
use function is_resource;
use function is_string;
use function ltrim;
use function preg_replace;
use function rtrim;
use function str_replace;
use function trim;

class StorageObjectManagement implements StorageObjectManagementInterface
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

    /** @var FileDriver $fileDriver */
    private $fileDriver;

    /** @var Filesystem $filesystem */
    private $filesystem;

    /** @var StreamInterfaceFactory $streamFactory */
    private $streamFactory;

    /**
     * @param FileDriver $fileDriver
     * @param Filesystem $filesystem
     * @param ModuleConfig $moduleConfig
     * @param StreamInterfaceFactory $streamFactory
     * @return void
     */
    public function __construct(
        FileDriver $fileDriver,
        Filesystem $filesystem,
        ModuleConfig $moduleConfig,
        StreamInterfaceFactory $streamFactory
    ) {
        $this->fileDriver = $fileDriver;
        $this->filesystem = $filesystem;
        $this->moduleConfig = $moduleConfig;
        $this->streamFactory = $streamFactory;
        $this->client = new StorageClient([
            'projectId' => $moduleConfig->getGoogleCloudProject(),
            'keyFilePath' => $this->getAbsolutePath($moduleConfig->getJsonKeyFilePath()),
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

        if (!empty($prefix) && $prefix[0] === DIRECTORY_SEPARATOR) {
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
        return implode(DIRECTORY_SEPARATOR, [
            '',
            trim($this->getPrefix(), DIRECTORY_SEPARATOR),
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
     * {@inheritdoc}
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
     * {@inheritdoc}
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
     * {@inheritdoc}
     */
    public function objectExists(string $path): bool
    {
        /** @var StorageObject|null $object */
        $object = $this->getObject($path);

        return ($object && $object->exists());
    }

    /**
     * {@inheritdoc}
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
                /** @var StreamInterface $stream */
                $stream = $this->streamFactory->create(['stream' => $handle]);

                /** @var string $absolutePath */
                $absolutePath = $this->fileDriver->getRealPath($stream->getMetadata('uri'));

                /** @var string $mediaBaseDir */
                $mediaBaseDir = rtrim($this->getMediaBaseDirectory(), DIRECTORY_SEPARATOR);

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
     * {@inheritdoc}
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
        return $object->exists() ? $object->copy($target) : null;
    }

    /**
     * {@inheritdoc}
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
        return $object->exists() ? $object->rename($target) : null;
    }

    /**
     * {@inheritdoc}
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
     * {@inheritdoc}
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

    /**
     * @param string $path
     * @return string|null
     */
    private function getAbsolutePath(string $path): ?string
    {
        if (!empty($path) && $path[0] !== DIRECTORY_SEPARATOR) {
            /** @var string $basePath */
            $basePath = $this->filesystem
                ->getDirectoryRead(DirectoryList::ROOT)
                ->getAbsolutePath();

            /** @var string $filePath */
            $filePath = implode(DIRECTORY_SEPARATOR, [
                rtrim($basePath, DIRECTORY_SEPARATOR),
                '',
                rtrim($path, DIRECTORY_SEPARATOR),
            ]);

            /** @var string $realPath */
            $realPath = $this->fileDriver->getRealPath($filePath);
            return $this->fileDriver->isFile($realPath) ? $realPath : null;
        }

        return !empty($path) ? $path : null;
    }
}
