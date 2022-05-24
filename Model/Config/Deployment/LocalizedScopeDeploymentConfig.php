<?php
/**
 * LocalizedScopeDeploymentConfig.php
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the MIT license, which
 * is bundled with this package in the file LICENSE.txt.
 *
 * It is also available on the Internet at the following URL:
 * https://docs.auroraextensions.com/magento/extensions/2.x/modulecomponents/LICENSE.txt
 *
 * @package     AuroraExtensions\ModuleComponents\Model\Config\Deployment
 * @copyright   Copyright (C) 2021 Aurora Extensions <support@auroraextensions.com>
 * @license     MIT
 */
declare(strict_types=1);

namespace AuroraExtensions\GoogleCloudStorage\Model\Config\Deployment;

use AuroraExtensions\GoogleCloudStorage\Api\LocalizedScopeDeploymentConfigInterface;
use Magento\Framework\App\DeploymentConfig;

use function array_filter;
use function array_merge;
use function explode;
use function implode;
use function trim;

class LocalizedScopeDeploymentConfig implements LocalizedScopeDeploymentConfigInterface
{
    /** @constant string DELIMITER */
    private const DELIMITER = '/';

    /** @var DeploymentConfig $deploymentConfig */
    private $deploymentConfig;

    /** @var string $delimiter */
    private $delimiter;

    /** @var string $scope */
    private $scope;

    /**
     * @param DeploymentConfig $deploymentConfig
     * @param string $delimiter
     * @param string|null $scope
     * @return void
     */
    public function __construct(
        DeploymentConfig $deploymentConfig,
        string $delimiter = self::DELIMITER,
        string $scope = null
    ) {
        $this->deploymentConfig = $deploymentConfig;
        $this->delimiter = $delimiter;
        $this->scope = $scope ?? '';
    }

    /**
     * {@inheritdoc}
     */
    public function get(?string $path = null)
    {
        /** @var string $scope */
        $scope = trim($this->scope, $this->delimiter);

        /** @var array $parts */
        $parts = explode($this->delimiter, !empty($path) ? $path : '');

        /** @var array $merge */
        $merge = array_merge([$scope], array_filter($parts, 'strlen'));

        /** @var string $xpath */
        $xpath = implode($this->delimiter, $merge);
        return !empty($xpath) ? $this->deploymentConfig->get($xpath) : null;
    }
}
