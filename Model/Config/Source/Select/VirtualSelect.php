<?php
/**
 * VirtualSelect.php
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the MIT license, which
 * is bundled with this package in the file LICENSE.txt.
 *
 * It is also available on the Internet at the following URL:
 * https://docs.auroraextensions.com/magento/extensions/2.x/modulecomponents/LICENSE.txt
 *
 * @package     AuroraExtensions\ModuleComponents\Model\Config\Source\Select
 * @copyright   Copyright (C) 2021 Aurora Extensions <support@auroraextensions.com>
 * @license     MIT
 */
declare(strict_types=1);

namespace AuroraExtensions\GoogleCloudStorage\Model\Config\Source\Select;

use Magento\Framework\Data\OptionSourceInterface;

use const true;
use function array_flip;
use function array_walk;
use function __;

class VirtualSelect implements OptionSourceInterface
{
    /** @var array $options */
    private $options = [];

    /**
     * @param array $data
     * @param bool $flip
     * @return void
     */
    public function __construct(
        array $data = [],
        bool $flip = true
    ) {
        /** @var array $opts */
        $opts = $flip ? array_flip($data) : $data;

        array_walk($opts, [
            $this,
            'setOption'
        ]);
    }

    /**
     * @param int|string|null $value
     * @param int|string $key
     * @return void
     */
    private function setOption($value, $key): void
    {
        $this->options[] = [
            'label' => __($key),
            'value' => $value,
        ];
    }

    /**
     * @return array
     */
    public function toOptionArray()
    {
        return $this->options;
    }
}