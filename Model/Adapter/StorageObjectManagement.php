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
 * @package     AuroraExtensions\GoogleCloudStorage\Model\Adapter
 * @copyright   Copyright (C) 2021 Aurora Extensions <support@auroraextensions.com>
 * @license     MIT
 */
declare(strict_types=1);

namespace AuroraExtensions\GoogleCloudStorage\Model\Adapter;

use AuroraExtensions\GoogleCloudStorage\{
    Api\StorageObjectManagementInterface,
    Api\StorageObjectPathResolverInterface,
    Component\ModuleConfigTrait,
    Exception\InvalidGoogleCloudStorageSetupException,
    Model\System\ModuleConfig
};
use AuroraExtensions\ModuleComponents\{
    Api\LocalizedScopeDeploymentConfigInterface,
    Api\LocalizedScopeDeploymentConfigInterfaceFactory,
    Exception\ExceptionFactory
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
use function __;

class StorageObjectManagement implements StorageObjectManagementInterface, StorageObjectPathResolverInterface
{
    /**
     * @var ModuleConfig $moduleConfig
     * @method ModuleConfig getConfig()
     */
    use ModuleConfigTrait;

    /** @constant string DIRSEP_REGEX */
    private const DIRSEP_REGEX = '#//+#';

    /** @var Bucket $bucket */
    private $bucket;

    /** @var StorageClient $client */
    private $client;

    /** @var LocalizedScopeDeploymentConfigInterface $deploymentConfig */
    private $deploymentConfig;

    /** @var ExceptionFactory $exceptionFactory */
    private $exceptionFactory;

    /** @var FileDriver $fileDriver */
    private $fileDriver;

    /** @var Filesystem $filesystem */
    private $filesystem;

    /** @var StreamInterfaceFactory $streamFactory */
    private $streamFactory;

    /** @var bool $useModuleConfig */
    private $useModuleConfig;

    /**
     * @param LocalizedScopeDeploymentConfigInterfaceFactory $deploymentConfigFactory
     * @param ExceptionFactory $exceptionFactory
     * @param FileDriver $fileDriver
     * @param Filesystem $filesystem
     * @param ModuleConfig $moduleConfig
     * @param StreamInterfaceFactory $streamFactory
     * @param bool $useModuleConfig
     * @return void
     */
    public function __construct(
        LocalizedScopeDeploymentConfigInterfaceFactory $deploymentConfigFactory,
        ExceptionFactory $exceptionFactory,
        FileDriver $fileDriver,
        Filesystem $filesystem,
        ModuleConfig $moduleConfig,
        StreamInterfaceFactory $streamFactory,
        bool $useModuleConfig = false
    ) {
        $this->deploymentConfig = $deploymentConfigFactory->create(['scope' => 'googlecloud']);
        $this->exceptionFactory = $exceptionFactory;
        $this->fileDriver = $fileDriver;
        $this->filesystem = $filesystem;
        $this->moduleConfig = $moduleConfig;
        $this->streamFactory = $streamFactory;
        $this->useModuleConfig = $useModuleConfig;
        $this->initialize();
    }

    /**
     * @return void
     * @throws InvalidGoogleCloudStorageSetupException
     */
    private function initialize(): void
    {
        /** @var string|null $projectName */
        $projectName = $this->useModuleConfig
            ? $this->getConfig()->getGoogleCloudProject()
            : $this->deploymentConfig->get('storage/project_name');

        /** @var string|null $keyFilePath */
        $keyFilePath = $this->useModuleConfig
            ? $this->getConfig()->getJsonKeyFilePath()
            : $this->deploymentConfig->get('storage/key_file_path');

        if (!empty($projectName) && !empty($keyFilePath)) {
            $this->client = new StorageClient([
                'projectId' => $projectName,
                'keyFilePath' => $this->getAbsolutePath($keyFilePath),
            ]);

            /** @var string|null $bucketName */
            $bucketName = $this->useModuleConfig
                ? $this->getConfig()->getBucketName()
                : $this->deploymentConfig->get('storage/bucket/name');

            if (!empty($bucketName)) {
                $this->bucket = $this->client->bucket($bucketName);
            } else {
                /** @var InvalidGoogleCloudStorageSetupException $exception */
                $exception = $this->exceptionFactory->create(
                    InvalidGoogleCloudStorageSetupException::class,
                    __('Bucket name is invalid')
                );
                throw $exception;
            }
        } else {
            /** @var InvalidGoogleCloudStorageSetupException $exception */
            $exception = $this->exceptionFactory->create(
                InvalidGoogleCloudStorageSetupException::class,
                __('Project name and/or key file path is invalid')
            );
            throw $exception;
        }
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

    /**
     * @return string
     */
    private function getMediaBaseDirectory(): string
    {
        return $this->filesystem
            ->getDirectoryRead(DirectoryList::MEDIA)
            ->getAbsolutePath();
    }

    /**
     * @return string|null
     */
    private function getPrefix(): ?string
    {
        /** @var string|null $config */
        $config = $this->useModuleConfig
            ? $this->getConfig()->getBucketPrefix()
            : $this->deploymentConfig->get('storage/bucket/prefix');

        if (empty($config)) {
            return null;
        }

        /** @var string $prefix */
        $prefix = preg_replace(self::DIRSEP_REGEX, DIRECTORY_SEPARATOR, $config);

        if (!empty($prefix) && $prefix[0] === DIRECTORY_SEPARATOR) {
            $prefix = ltrim($prefix, DIRECTORY_SEPARATOR);
        }

        return $prefix;
    }

    /**
     * @return bool
     */
    private function hasPrefix(): bool
    {
        /** @var string|null $prefix */
        $prefix = $this->useModuleConfig
            ? $this->getConfig()->getBucketPrefix()
            : $this->deploymentConfig->get('storage/bucket/prefix');

        return !empty($prefix);
    }

    /**
     * {@inheritdoc}
     */
    public function getClient(): StorageClient
    {
        return $this->client;
    }

    /**
     * {@inheritdoc}
     */
    public function getObject(string $path): ?StorageObject
    {
        if ($this->hasPrefix()) {
            $path = implode(DIRECTORY_SEPARATOR, [
                $this->getPrefix(),
                ltrim($path, DIRECTORY_SEPARATOR),
            ]);
        }

        return $this->bucket->object($path);
    }

    /**
     * {@inheritdoc}
     */
    public function getObjects(array $options = []): ?ObjectIterator
    {
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

        return $this->bucket->objects($options);
    }

    /**
     * {@inheritdoc}
     */
    public function objectExists(string $path): bool
    {
        /** @var StorageObject|null $object */
        $object = $this->getObject($path);
        return ($object !== null && $object->exists());
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
                    ltrim($options['name'], DIRECTORY_SEPARATOR  . $prefix),
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

        return $this->bucket->upload($handle, $options);
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
     * {@inheritdoc}
     */
    public function getObjectPath(string $path): string
    {
        /** @var array $parts */
        $parts[] = '';

        if ($this->hasPrefix()) {
            $parts[] = trim($this->getPrefix(), DIRECTORY_SEPARATOR);
        }

        $parts[] = trim($path, DIRECTORY_SEPARATOR);
        return implode(DIRECTORY_SEPARATOR, $parts);
    }

    /**
     * @return string
     * @deprecated Serves as stopgap during {@see ModuleConfig} deprecation.
     */
    public function getObjectAclPolicy(): string
    {
        /** @var string|null $aclPolicy */
        $aclPolicy = $this->useModuleConfig
            ? $this->getConfig()->getBucketAclPolicy()
            : $this->deploymentConfig->get('storage/bucket/acl');

        return !empty($aclPolicy) ? $aclPolicy : ModuleConfig::DEFAULT_ACL_POLICY;
    }
}
