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
 * @package     AuroraExtensions\GoogleCloudStorage\Model\File\Storage
 * @copyright   Copyright (C) 2023 Aurora Extensions <support@auroraextensions.com>
 * @license     MIT
 */
declare(strict_types=1);

namespace AuroraExtensions\GoogleCloudStorage\Model\File\Storage;

use AuroraExtensions\GoogleCloudStorage\Api\StorageObjectManagementInterface;
use AuroraExtensions\GoogleCloudStorage\Component\ModuleConfigTrait;
use AuroraExtensions\GoogleCloudStorage\Component\StorageAdapterTrait;
use AuroraExtensions\GoogleCloudStorage\Model\System\ModuleConfig;
use AuroraExtensions\ModuleComponents\Exception\ExceptionFactory;
use Google\Cloud\Storage\ObjectIterator;
use Google\Cloud\Storage\StorageObject;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Filesystem;
use Magento\Framework\Filesystem\Driver\File;
use Magento\Framework\Model\AbstractModel;
use Magento\Framework\Phrase;
use Magento\MediaStorage\Helper\File\Storage\Database as StorageHelper;
use Psr\Log\LoggerInterface;
use Throwable;

use function __;
use function implode;
use function iterator_count;
use function ltrim;
use function rtrim;
use function strlen;
use function substr;

use const DIRECTORY_SEPARATOR;

class Bucket extends AbstractModel
{
    /**
     * @var ModuleConfig $moduleConfig
     * @method ModuleConfig getConfig()
     * ---
     * @var StorageObjectManagementInterface $storageAdapter
     * @method StorageObjectManagementInterface getStorage()
     */
    use ModuleConfigTrait, StorageAdapterTrait;

    /** @var ExceptionFactory $exceptionFactory */
    private $exceptionFactory;

    /** @var File $file */
    private $file;

    /** @var Filesystem $filesystem */
    private $filesystem;

    /** @var LoggerInterface $logger */
    private $logger;

    /** @var ObjectIterator<StorageObject> $objects */
    private $objects;

    /** @var StorageHelper $storageHelper */
    private $storageHelper;

    /**
     * @param ExceptionFactory $exceptionFactory
     * @param File $file
     * @param Filesystem $filesystem
     * @param LoggerInterface $logger
     * @param ModuleConfig $moduleConfig
     * @param StorageHelper $storageHelper
     * @param StorageObjectManagementInterface $storageAdapter
     * @return void
     */
    public function __construct(
        ExceptionFactory $exceptionFactory,
        File $file,
        Filesystem $filesystem,
        LoggerInterface $logger,
        ModuleConfig $moduleConfig,
        StorageHelper $storageHelper,
        StorageObjectManagementInterface $storageAdapter
    ) {
        $this->exceptionFactory = $exceptionFactory;
        $this->file = $file;
        $this->filesystem = $filesystem;
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

        if ($this->getStorage()->objectExists($relativePath)) {
            $this->setData('id', $filename);
            $this->setData('filename', $filename);
            $this->setData('content', $this->getStorage()->getObject($relativePath)->downloadAsString());
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
        $this->getStorage()->deleteAllObjects();
        return $this;
    }

    /**
     * @param int $offset
     * @param int $count
     * @return bool
     * @see \Magento\MediaStorage\Model\File\Storage\File::exportDirectories()
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

            if (!empty($name) && $name[0] !== DIRECTORY_SEPARATOR) {
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
            $filePath = $this->getFilePath(
                $file['filename'],
                $file['directory']
            );

            /** @var string $relativePath */
            $relativePath = $this->storageHelper
                ->getMediaRelativePath($filePath);

            try {
                /* Upload file object to bucket. */
                $storage->uploadObject(
                    $file['content'],
                    [
                        'name' => $relativePath,
                        'predefinedAcl' => $storage->getObjectAclPolicy(),
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
            } catch (Throwable $e) {
                $this->errors[] = $e->getMessage();
                $this->logger->error($e->getMessage());
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
        /** @var StorageObjectManagementInterface $storage */
        $storage = $this->getStorage();

        /** @var string $mediaPath */
        $mediaPath = $this->filesystem
            ->getDirectoryRead(DirectoryList::MEDIA)
            ->getAbsolutePath();

        /** @var string $filePath */
        $filePath = $this->getFilePath($filename, $mediaPath);

        try {
            /** @var resource $handle */
            $handle = $this->file->fileOpen($filePath, 'r');

            /** @var string $relativePath */
            $relativePath = $this->storageHelper
                ->getMediaRelativePath($filePath);

            /* Upload file object to bucket. */
            $storage->uploadObject(
                $handle,
                [
                    'name' => $relativePath,
                    'predefinedAcl' => $storage->getObjectAclPolicy(),
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
        } catch (Throwable $e) {
            $this->errors[] = $e->getMessage();
            $this->logger->error($e->getMessage());
        }

        return $this;
    }

    /**
     * @param string $filePath
     * @return bool
     */
    public function fileExists(string $filePath): bool
    {
        return $this->getStorage()->objectExists($filePath);
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
        $prefix = implode(
            DIRECTORY_SEPARATOR,
            [
                rtrim($mediaPath, DIRECTORY_SEPARATOR),
                '',
            ]
        );

        /** @var ObjectIterator<StorageObject> $objectsPrefixes */
        $objectsPrefixes = $this->getStorage()->getObjects([
            'delimiter' => DIRECTORY_SEPARATOR,
            'prefix'    => $prefix,
        ]);

        if (isset($objectsPrefixes['prefixes'])) {
            /** @var string $subdir */
            foreach ($objectsPrefixes['prefixes'] as $subdir) {
                $subdirs[] = [
                    'name' => substr(
                        $subdir,
                        strlen($prefix)
                    ),
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
        $prefix = implode(
            DIRECTORY_SEPARATOR,
            [
                rtrim($mediaPath, DIRECTORY_SEPARATOR),
                '',
            ]
        );

        /** @var ObjectIterator<StorageObject> $objectsPrefixes */
        $objectsPrefixes = $this->getStorage()->getObjects([
            'delimiter' => DIRECTORY_SEPARATOR,
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
    ): string {
        if (!empty($prefix)) {
            $path = implode(
                DIRECTORY_SEPARATOR,
                [
                    rtrim($prefix, DIRECTORY_SEPARATOR),
                    ltrim($path, DIRECTORY_SEPARATOR),
                ]
            );
        }

        return $path;
    }
}
