<?php
/**
 * ModuleConfig.php
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the MIT license, which
 * is bundled with this package in the file LICENSE.txt.
 *
 * It is also available on the Internet at the following URL:
 * https://docs.auroraextensions.com/magento/extensions/2.x/googlecloudstorage/LICENSE.txt
 *
 * @package     AuroraExtensions\GoogleCloudStorage\Model\System
 * @copyright   Copyright (C) 2021 Aurora Extensions <support@auroraextensions.com>
 * @license     MIT
 */
declare(strict_types=1);

namespace AuroraExtensions\GoogleCloudStorage\Model\System;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\Store;

/**
 * @deprecated 1.2.0
 *
 * Starting in v1.2.0, an environment deployment configuration is required to supplant the current use of system configuration values.
 * An example configuration is available on {@link https://github.com/auroraextensions/googlecloudstorage/blob/master/env.php.sample GitHub}.
 * This class will be removed in minor release 1.3.0 and should not be relied upon in future development.
 */
class ModuleConfig
{
    public const DEFAULT_ACL_POLICY = 'publicRead';
    public const DEFAULT_BUCKET_REGION = 'us-central1';

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
     * @param int $store
     * @param string $scope
     * @return bool
     */
    public function isModuleEnabled(
        int $store = Store::DEFAULT_STORE_ID,
        string $scope = ScopeInterface::SCOPE_STORE
    ): bool {
        return (bool) $this->scopeConfig->isSetFlag(
            'googlecloudstorage/general/enable',
            $scope,
            $store
        );
    }

    /**
     * @param int $store
     * @param string $scope
     * @return string
     */
    public function getGoogleCloudProject(
        int $store = Store::DEFAULT_STORE_ID,
        string $scope = ScopeInterface::SCOPE_STORE
    ): string {
        return (string) $this->scopeConfig->getValue(
            'googlecloudstorage/general/gcp_project',
            $scope,
            $store
        );
    }

    /**
     * @param int $store
     * @param string $scope
     * @return string
     */
    public function getJsonKeyFilePath(
        int $store = Store::DEFAULT_STORE_ID,
        string $scope = ScopeInterface::SCOPE_STORE
    ): string {
        return (string) $this->scopeConfig->getValue(
            'googlecloudstorage/general/key_file_path',
            $scope,
            $store
        );
    }

    /**
     * @param int $store
     * @param string $scope
     * @return string
     */
    public function getBucketName(
        int $store = Store::DEFAULT_STORE_ID,
        string $scope = ScopeInterface::SCOPE_STORE
    ): string {
        return (string) $this->scopeConfig->getValue(
            'googlecloudstorage/bucket/name',
            $scope,
            $store
        );
    }

    /**
     * @param int $store
     * @param string $scope
     * @return string|null
     */
    public function getBucketPrefix(
        int $store = Store::DEFAULT_STORE_ID,
        string $scope = ScopeInterface::SCOPE_STORE
    ): ?string {
        return $this->scopeConfig->getValue(
            'googlecloudstorage/bucket/prefix',
            $scope,
            $store
        );
    }

    /**
     * @param int $store
     * @param string $scope
     * @return string
     */
    public function getBucketAclPolicy(
        int $store = Store::DEFAULT_STORE_ID,
        string $scope = ScopeInterface::SCOPE_STORE
    ): string {
        return $this->scopeConfig->getValue(
            'googlecloudstorage/bucket/acl',
            $scope,
            $store
        ) ?? self::DEFAULT_ACL_POLICY;
    }

    /**
     * @param int $store
     * @param string $scope
     * @return string
     */
    public function getBucketRegion(
        int $store = Store::DEFAULT_STORE_ID,
        string $scope = ScopeInterface::SCOPE_STORE
    ): string {
        return $this->scopeConfig->getValue(
            'googlecloudstorage/bucket/region',
            $scope,
            $store
        ) ?? self::DEFAULT_BUCKET_REGION;
    }
}
