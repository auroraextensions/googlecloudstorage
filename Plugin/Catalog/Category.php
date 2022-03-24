<?php

namespace AuroraExtensions\GoogleCloudStorage\Plugin\Catalog;

use AuroraExtensions\GoogleCloudStorage\Model\File\Storage\Synchronization;
use Magento\Catalog\Model\Category as CategoryModel;

class Category
{
    /**
     * @var Synchronization
     */
    protected $synchronization;

    public function __construct(
        Synchronization $synchronization
    ) {
        $this->synchronization = $synchronization;
    }

    public function afterGetImageUrl(CategoryModel $category, $result)
    {
        $this->synchronization->synchronize($result);
        return $result;
    }
}
