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

namespace AuroraExtensions\GoogleCloudStorage\Model\File;

use AuroraExtensions\GoogleCloudStorage\{
    Model\File\Storage\Bucket,
    Model\File\Storage\BucketFactory
};
use Magento\Framework\{
    App\Config\ScopeConfigInterface,
    Data\Collection\AbstractDb,
    Filesystem,
    Model\Context,
    Model\ResourceModel\AbstractResource,
    Registry
};
use Magento\MediaStorage\{
    Helper\File\Storage as StorageHelper,
    Model\File\Storage as StorageModel,
    Model\File\Storage\Flag,
    Model\File\Storage\FileFactory,
    Model\File\Storage\DatabaseFactory
};

class Storage extends StorageModel
{
    /** @constant int STORAGE_MEDIA_GCS */
    public const STORAGE_MEDIA_GCS = 2;

    /** @property BucketFactory $bucketFactory */
    protected $bucketFactory;

    /**
     * @param Context $context
     * @param Registry $registry
     * @param StorageHelper $coreFileStorage
     * @param ScopeConfigInterface $scopeConfig
     * @param ScopeConfigInterface $coreConfig
     * @param Flag $fileFlag
     * @param FileFactory $fileFactory
     * @param DatabaseFactory $databaseFactory
     * @param Filesystem $filesystem
     * @param AbstractResource|null $resource
     * @param AbstractDb|null $resourceCollection
     * @param array $data
     * @param BucketFactory $bucketFactory
     * @return void
     */
    public function __construct(
        Context $context,
        Registry $registry,
        StorageHelper $coreFileStorage,
        ScopeConfigInterface $scopeConfig,
        ScopeConfigInterface $coreConfig,
        Flag $fileFlag,
        FileFactory $fileFactory,
        DatabaseFactory $databaseFactory,
        Filesystem $filesystem,
        AbstractResource $resource = null,
        AbstractDb $resourceCollection = null,
        array $data = [],
        BucketFactory $bucketFactory
    ) {
        parent::__construct(
            $context,
            $registry,
            $coreFileStorage,
            $scopeConfig,
            $coreConfig,
            $fileFlag,
            $fileFactory,
            $databaseFactory,
            $filesystem,
            $resource,
            $resourceCollection,
            $data
        );
        $this->bucketFactory = $bucketFactory;
    }

    /**
     * @param int|null $storage
     * @param array $params
     * @return AbstractModel|bool
     */
    public function getStorageModel($storage = null, $params = [])
    {
        /** @var AbstractModel|bool $storageModel */
        $storageModel = parent::getStorageModel($storage, $params);

        if (!$storageModel) {
            /* Get storage code value, if not given. */
            $storage = $storage !== null
                ? (int) $storage
                : $this->_coreFileStorage->getCurrentStorageCode();

            switch ($storage) {
                case self::STORAGE_MEDIA_GCS:
                    $storageModel = $this->bucketFactory->create();
                    break;
                default:
                    return false;
            }

            if (isset($params['init']) && $params['init']) {
                $storageModel->init();
            }
        }

        return $storageModel;
    }
}
