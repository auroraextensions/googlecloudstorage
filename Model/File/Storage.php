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
 * @package       AuroraExtensions\GoogleCloudStorage\Model\File
 * @copyright     Copyright (C) 2019 Aurora Extensions <support@auroraextensions.com>
 * @license       MIT
 */
declare(strict_types=1);

namespace AuroraExtensions\GoogleCloudStorage\Model\File;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\MediaStorage\Model\File\Storage as FileStorage;

class Storage
{
    /** @constant int STORAGE_MEDIA_GCS */
    public const STORAGE_MEDIA_GCS = 2;

    /** @var ScopeConfigInterface $scopeConfig */
    private $scopeConfig;

    /**
     * @param ScopeConfigInterface $scopeConfig
     * @return void
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig
    ) {
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * @return bool
     */
    public function checkBucketUsage(): bool
    {
        /** @var int $storage */
        $storage = (int) $this->scopeConfig->getValue(
            FileStorage::XML_PATH_STORAGE_MEDIA,
            ScopeConfigInterface::SCOPE_TYPE_DEFAULT
        );

        return ($storage === self::STORAGE_MEDIA_GCS);
    }
}
