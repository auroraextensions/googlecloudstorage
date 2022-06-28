<?php

namespace AuroraExtensions\GoogleCloudStorage\Block;

use AuroraExtensions\GoogleCloudStorage\Model\File\Storage;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\App\State;
use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Magento\Store\Model\StoreManagerInterface;

class ImageCatcher extends Template
{
    /**
     * Store manager
     *
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @param Context $context
     * @param Config $mediaConfig
     * @param Storage $storage
     * @param array $data
     */
    public function __construct(
        Context $context,
        StoreManagerInterface $storeManager,
        Storage $appState,
        array $data = []
    ) {
        parent::__construct($context, $data);

        $this->storeManager = $storeManager;
        $this->storage      = $storage;
    }

    public function getMediaUrl()
    {
        return $this->storeManager->getStore()->getBaseUrl(UrlInterface::URL_TYPE_MEDIA);
    }

    public function getBaseMediaUrl()
    {
        return $this->storeManager->getStore()->getBaseUrl(UrlInterface::URL_TYPE_WEB) . DirectoryList::MEDIA . DIRECTORY_SEPARATOR;
    }

    public function isEnabled()
    {
        return $this->storage->checkBucketUsage();
    }
}
