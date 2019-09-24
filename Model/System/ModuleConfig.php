<?php
/**
 * ModuleConfig.php
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

namespace AuroraExtensions\GoogleCloudStorage\Model\System;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\{
    Model\ScopeInterface,
    Model\Store
};

class ModuleConfig
{
    /** @property ScopeConfigInterface $scopeConfig */
    protected $scopeConfig;

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
    ): bool
    {
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
    ): string
    {
        return $this->scopeConfig->getValue(
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
    ): string
    {
        return $this->scopeConfig->getValue(
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
    ): string
    {
        return $this->scopeConfig->getValue(
            'googlecloudstorage/bucket/name',
            $scope,
            $store
        );
    }

    /**
     * @param int $store
     * @param string $scope
     * @return string
     */
    public function getBucketPrefix(
        int $store = Store::DEFAULT_STORE_ID,
        string $scope = ScopeInterface::SCOPE_STORE
    ): string
    {
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
    ): string
    {
        return $this->scopeConfig->getValue(
            'googlecloudstorage/bucket/acl',
            $scope,
            $store
        );
    }

    /**
     * @param int $store
     * @param string $scope
     * @return string
     */
    public function getBucketRegion(
        int $store = Store::DEFAULT_STORE_ID,
        string $scope = ScopeInterface::SCOPE_STORE
    ): string
    {
        return $this->scopeConfig->getValue(
            'googlecloudstorage/bucket/region',
            $scope,
            $store
        );
    }
}
