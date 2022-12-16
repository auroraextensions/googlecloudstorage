<?php

namespace AuroraExtensions\GoogleCloudStorage\Model\Cache\Type;

use Magento\Framework\App\Cache\Type\FrontendPool;
use Magento\Framework\Cache\Frontend\Decorator\TagScope;

class GcsCache extends TagScope
{
    const TYPE_IDENTIFIER = 'outeredge_gcs_cache';

    const CACHE_TAG = 'OUTEREDGE_GCS_CACHE';

    /**
     * @param FrontendPool $cacheFrontendPool
     */
    public function __construct(FrontendPool $cacheFrontendPool)
    {
        parent::__construct(
            $cacheFrontendPool->get(self::TYPE_IDENTIFIER),
            self::CACHE_TAG
        );
    }
}
