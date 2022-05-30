<?php

namespace AuroraExtensions\GoogleCloudStorage\Block;

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
     * @param State $appState
     * @param array $data
     */
    public function __construct(
        Context $context,
        StoreManagerInterface $storeManager,
        State $appState,
        array $data = []
    ) {
        parent::__construct($context, $data);

        $this->storeManager = $storeManager;
        $this->appState     = $appState;
    }

    public function getMediaUrl()
    {
        return $this->storeManager->getStore()->getBaseUrl(UrlInterface::URL_TYPE_MEDIA);
    }

    public function getBaseMediaUrl()
    {
        return $this->storeManager->getStore()->getBaseUrl(UrlInterface::URL_TYPE_WEB) . DirectoryList::MEDIA . DIRECTORY_SEPARATOR;
    }

    public function isDeveloperMode()
    {
        return $this->appState->getMode() == State::MODE_DEVELOPER;
    }
}
