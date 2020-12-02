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
 * @package       AuroraExtensions\GoogleCloudStorage\Model\File\Storage
 * @copyright     Copyright (C) 2019 Aurora Extensions <support@auroraextensions.com>
 * @license       MIT
 */
declare(strict_types=1);

namespace AuroraExtensions\GoogleCloudStorage\Model\File\Storage;

use Exception;
use AuroraExtensions\GoogleCloudStorage\{
    Api\StorageObjectManagementInterface,
    Component\ModuleConfigTrait,
    Component\StorageAdapterTrait,
    Exception\ExceptionFactory,
    Model\System\ModuleConfig
};
use Google\Cloud\{
    Storage\StorageObject,
    Storage\ObjectIterator
};
use Magento\Framework\{
    Exception\LocalizedException,
    Filesystem\Driver\File as FilesystemDriver,
    Model\AbstractModel,
    Phrase
};
use Magento\MediaStorage\Helper\File\Storage\Database as StorageHelper;
use Psr\Log\LoggerInterface;

class Bucket extends AbstractModel
{
    /** @trait ModuleConfigTrait */
    /** @trait StorageAdapterTrait */
    use ModuleConfigTrait, StorageAdapterTrait;

    /** @property ExceptionFactory $exceptionFactory */
    protected $exceptionFactory;

    /** @property FilesystemDriver $filesystemDriver */
    protected $filesystemDriver;

    /** @property LoggerInterface $logger */
    protected $logger;

    /** @property ModuleConfig $moduleConfig */
    protected $moduleConfig;

    /** @property ObjectIterator<StorageObject> $objects */
    protected $objects;

    /** @property StorageHelper $storageHelper */
    protected $storageHelper;

    /** @property StorageObjectManagementInterface $storageAdapter */
    protected $storageAdapter;

    /**
     * @param ExceptionFactory $exceptionFactory
     * @param FilesystemDriver $filesystemDriver
     * @param LoggerInterface $logger
     * @param ModuleConfig $moduleConfig
     * @param StorageHelper $storageHelper
     * @param StorageObjectManagementInterface $storageAdapter
     * @return void
     */
    public function __construct(
        ExceptionFactory $exceptionFactory,
        FilesystemDriver $filesystemDriver,
        LoggerInterface $logger,
        ModuleConfig $moduleConfig,
        StorageHelper $storageHelper,
        StorageObjectManagementInterface $storageAdapter
    ) {
        $this->exceptionFactory = $exceptionFactory;
        $this->filesystemDriver = $filesystemDriver;
        $this->logger = $logger;
        $this->moduleConfig = $moduleConfig;
        $this->storageHelper = $storageHelper;
        $this->storageAdapter = $storageAdapter;
    }

    /**
     * @return Phrase
     */
    public function getStorageName(): Phrase
    {
        return __('Google Cloud Storage');
    }

    /**
     * @return $this
     */
    public function init()
    {
        return $this;
    }

    /**
     * @return bool
     */
    public function hasObjects(): bool
    {
        return ($this->objects !== null && iterator_count($this->objects) > 0);
    }

    /**
     * @return ObjectIterator|null
     */
    public function getObjects(): ?ObjectIterator
    {
        return $this->objects;
    }

    /**
     * @param ObjectIterator|null $objects
     * @return $this
     */
    public function setObjects(?ObjectIterator $objects)
    {
        $this->objects = $objects;
        return $this;
    }

    /**
     * @param string $filename
     * @return $this
     */
    public function loadByFilename(string $filename)
    {
        /** @var string $relativePath */
        $relativePath = $this->storageHelper->getMediaRelativePath($filename);

        /** @var StorageObjectManagementInterface $storage */
        $storage = $this->getStorage();

        if ($storage->objectExists($relativePath)) {
            $this->setData('id', $filename);
            $this->setData('filename', $filename);
            $this->setData('content', $storage->getObject($relativePath)->downloadAsString());
        } else {
            $this->unsetData();
        }

        return $this;
    }

    /**
     * @return $this
     */
    public function clear()
    {
        $this->getStorage()
            ->deleteAllObjects();

        return $this;
    }

    /**
     * @param int $offset
     * @param int $count
     * @return bool
     * @see Magento\MediaStorage\Model\File\Storage\File::exportDirectories()
     */
    public function exportDirectories(
        int $offset = 0,
        int $count = 100
    ) {
        return false;
    }

    /**
     * @param int $offset
     * @param int $count
     * @return array|bool
     */
    public function exportFiles(
        int $offset = 0,
        int $count = 1
    ) {
        /** @var array $files */
        $files = [];

        if (!$this->hasObjects()) {
            $this->setObjects(
                $this->getStorage()->getObjects(['maxResults' => $count])
            );
        } else {
            $this->setObjects(
                $this->getStorage()->getObjects([
                    'maxResults'    => $count,
                    'nextPageToken' => $this->getObjects()->nextPageToken,
                ])
            );
        }

        if (!$this->hasObjects()) {
            return false;
        }

        /** @var StorageObject $object */
        foreach ($this->getObjects() as $object) {
            /** @var string $name */
            $name = $object->name();

            if (strlen($name) && $name[0] !== '/') {
                $files[] = [
                    'filename' => $name,
                    'content'  => $object->downloadAsString(),
                ];
            }
        }

        return $files;
    }

    /**
     * @param array $dirs
     * @return $this
     */
    public function importDirectories(array $dirs = [])
    {
        return $this;
    }

    /**
     * @param array $files
     * @return $this
     */
    public function importFiles(array $files = [])
    {
        /** @var StorageObjectManagementInterface $storage */
        $storage = $this->getStorage();

        /** @var array $file */
        foreach ($files as $file) {
            /** @var string $filePath */
            $filePath = $this->getFilePath($file['filename'], $file['directory']);

            /** @var string $content */
            $content = $file['content'];

            /** @var string $relativePath */
            $relativePath = $this->storageHelper
                ->getMediaRelativePath($filePath);

            try {
                /** @var string $aclPolicy */
                $aclPolicy = $this->getConfig()
                    ->getBucketAclPolicy();

                /* Upload file object to bucket. */
                $storage->uploadObject(
                    $content,
                    [
                        'name' => $relativePath,
                        'predefinedAcl' => $aclPolicy,
                    ]
                );

                if (!$storage->objectExists($relativePath)) {
                    /** @var LocalizedException $exception */
                    $exception = $this->exceptionFactory->create(
                        LocalizedException::class,
                        __('Unable to save file: %1', $filePath)
                    );

                    throw $exception;
                }
            } catch (LocalizedException $e) {
                $this->errors[] = $e->getMessage();
                $this->logger->critical($e);
            } catch (Exception $e) {
                $this->errors[] = $e->getMessage();
                $this->logger->critical($e);
            }
        }

        return $this;
    }

    /**
     * @param string $filename
     * @return $this
     */
    public function saveFile(string $filename)
    {
        /** @var string $mediaPath */
        $mediaPath = $this->getStorage()
            ->getMediaBaseDirectory();

        /** @var string $filePath */
        $filePath = $this->getFilePath(
            $filename,
            $mediaPath
        );

        try {
            /** @var resource $handle */
            $handle = $this->filesystemDriver
                ->fileOpen($filePath, 'r');

            /** @var string $relativePath */
            $relativePath = $this->storageHelper
                ->getMediaRelativePath($filePath);

            /* Upload file object to bucket. */
            $storage->uploadObject(
                $handle,
                [
                    'name'          => $relativePath,
                    'predefinedAcl' => $this->getConfig()->getBucketAclPolicy(),
                ]
            );

            if (!$this->getStorage()->objectExists($relativePath)) {
                /** @var LocalizedException $exception */
                $exception = $this->exceptionFactory->create(
                    LocalizedException::class,
                    __('Unable to save file: %1', $filePath)
                );

                throw $exception;
            }
        } catch (LocalizedException $e) {
            $this->errors[] = $e->getMessage();
            $this->logger->critical($e);
        } catch (Exception $e) {
            $this->errors[] = $e->getMessage();
            $this->logger->critical($e);
        }

        return $this;
    }

    /**
     * @param string $filePath
     * @return bool
     */
    public function fileExists(string $filePath): bool
    {
        return $this->getStorage()
            ->objectExists($filePath);
    }

    /**
     * @param string $source
     * @param string $target
     * @return $this
     */
    public function copyFile(string $source, string $target)
    {
        /** @var StorageObjectManagementInterface $storage */
        $storage = $this->getStorage();

        if ($storage->objectExists($source)) {
            $storage->copyObject($source, $target);
        }

        return $this;
    }

    /**
     * @param string $source
     * @param string $target
     * @return $this
     */
    public function renameFile(string $source, string $target)
    {
        /** @var StorageObjectManagementInterface $storage */
        $storage = $this->getStorage();

        if ($storage->objectExists($source)) {
            $storage->renameObject($source, $target);
        }

        return $this;
    }

    /**
     * @param string $path
     * @return $this
     */
    public function deleteFile(string $path)
    {
        /** @var StorageObjectManagementInterface $storage */
        $storage = $this->getStorage();

        if ($storage->objectExists($path)) {
            $storage->deleteObject($path);
        }

        return $this;
    }

    /**
     * @param string $path
     * @return array
     */
    public function getSubdirectories(string $path): array
    {
        /** @var array $subdirs */
        $subdirs = [];

        /** @var string $mediaPath */
        $mediaPath = $this->storageHelper
            ->getMediaRelativePath($path);

        /** @var string $prefix */
        $prefix = rtrim($mediaPath, '/') . '/';

        /** @var ObjectIterator<StorageObject> $objectsPrefixes */
        $objectsPrefixes = $this->getStorage()->getObjects([
            'delimiter' => '/',
            'prefix'    => $prefix,
        ]);

        if (isset($objectsPrefixes['prefixes'])) {
            /** @var string $subdir */
            foreach ($objectsPrefixes['prefixes'] as $subdir) {
                $subdirs[] = [
                    'name' => substr($subdir, strlen($prefix)),
                ];
            }
        }

        return $subdirs;
    }

    /**
     * @param string $path
     * @return array
     */
    public function getDirectoryFiles(string $path): array
    {
        /** @var array $files */
        $files = [];

        /** @var string $mediaPath */
        $mediaPath = $this->storageHelper
            ->getMediaRelativePath($path);

        /** @var string $prefix */
        $prefix = rtrim($mediaPath, '/') . '/';

        /** @var ObjectIterator<StorageObject> $objectsPrefixes */
        $objectsPrefixes = $this->getStorage()->getObjects([
            'delimiter' => '/',
            'prefix'    => $prefix,
        ]);

        if (isset($objectsPrefixes['objects'])) {
            /** @var StorageObject $object */
            foreach ($objectsPrefixes['objects'] as $object) {
                /** @var string $name */
                $name = $object->name();

                if ($name !== $prefix) {
                    $files[] = [
                        'filename' => $name,
                        'content'  => $object->downloadAsString(),
                    ];
                }
            }
        }

        return $files;
    }

    /**
     * @param string $path
     * @param string|null $prefix
     * @return string
     */
    public function getFilePath(
        string $path,
        ?string $prefix = null
    ): string
    {
        return $prefix !== null
            ? rtrim($prefix, '/') . '/' . ltrim($path, '/')
            : $path;
    }
}
