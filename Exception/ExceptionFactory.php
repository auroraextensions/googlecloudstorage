<?php
/**
 * ExceptionFactory.php
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the MIT license, which
 * is bundled with this package in the file LICENSE.txt.
 *
 * It is also available on the Internet at the following URL:
 * https://docs.auroraextensions.com/magento/extensions/2.x/modulecomponents/LICENSE.txt
 *
 * @package     AuroraExtensions\ModuleComponents\Exception
 * @copyright   Copyright (C) 2021 Aurora Extensions <support@auroraextensions.com>
 * @license     MIT
 */
declare(strict_types=1);

namespace AuroraExtensions\GoogleCloudStorage\Exception;

use Exception;
use Throwable;
use Magento\Framework\{
    ObjectManagerInterface,
    Phrase
};

use function is_subclass_of;
use function __;

class ExceptionFactory
{
    /** @constant string ERROR_DEFAULT_MSG */
    private const ERROR_DEFAULT_MSG = 'An error occurred. Unable to process the request.';

    /** @constant string ERROR_INVALID_TYPE */
    private const ERROR_INVALID_TYPE = 'Invalid exception class type %1 was given.';

    /** @var ObjectManagerInterface $objectManager */
    private $objectManager;

    /**
     * @param ObjectManagerInterface $objectManager
     * @return void
     */
    public function __construct(ObjectManagerInterface $objectManager)
    {
        $this->objectManager = $objectManager;
    }

    /**
     * @param string|null $type
     * @param Phrase|null $message
     * @return Throwable
     * @throws Exception
     */
    public function create(
        string $type = Exception::class,
        Phrase $message = null
    ) {
        /** @var array $arguments */
        $arguments = [];

        /* Set default message, as required. */
        $message = $message ?? __(static::ERROR_DEFAULT_MSG);

        if (!is_subclass_of($type, Throwable::class)) {
            throw new Exception(
                __(
                    static::ERROR_INVALID_TYPE,
                    $type
                )->__toString()
            );
        }

        if ($type !== Exception::class) {
            $arguments['phrase'] = $message;
        } else {
            $arguments['message'] = $message->__toString();
        }

        return $this->objectManager->create($type, $arguments);
    }
}