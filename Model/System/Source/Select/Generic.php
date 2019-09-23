<?php
/**
 * Generic.php
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

namespace AuroraExtensions\GoogleCloudStorage\Model\System\Source\Select;

use Magento\Framework\Option\ArrayInterface;

class Generic implements ArrayInterface
{
    /** @property array $options */
    protected $options = [];

    /**
     * @param array $data
     * @return void
     */
    public function __construct(array $data = [])
    {
        /* Invert KV pairs for array_walk. */
        $data = array_flip($data);

        array_walk(
            $data,
            [
                $this,
                'setOption'
            ]
        );
    }

    /**
     * @param int|string|null $value
     * @param int|string $key
     * @return void
     */
    protected function setOption($value, $key): void
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
