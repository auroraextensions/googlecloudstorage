<?php
/**
 * ExceptionFactory.php
 *
 * Factory for generating various exception types.
 * Requires ObjectManager for instance generation.
 *
 * @link https://devdocs.magento.com/guides/v2.3/extension-dev-guide/factories.html
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the MIT license, which
 * is bundled with this package in the file LICENSE.txt.
 *
 * It is also available on the Internet at the following URL:
 * https://docs.auroraextensions.com/magento/extensions/2.x/googlecloudstorage/LICENSE.txt
 *
 * @package       AuroraExtensions\GoogleCloudStorage\Exception
 * @copyright     Copyright (C) 2019 Aurora Extensions <support@auroraextensions.com>
 * @license       MIT
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

final class ExceptionFactory
{
    /** @constant string ERROR_DEFAULT_MESSAGE */
    public const ERROR_DEFAULT_MESSAGE = 'An error has occurred and we are unable to process the request.';

    /** @constant string ERROR_INVALID_EXCEPTION_TYPE */
    public const ERROR_INVALID_EXCEPTION_TYPE = 'Invalid exception class type %1 was given.';

    /** @var ObjectManagerInterface $objectManager */
    private $objectManager;

    /**
     * @param ObjectManagerInterface $objectManager
     * @return void
     */
    public function __construct(
        ObjectManagerInterface $objectManager
    )
    {
        $this->objectManager = $objectManager;
    }

    /**
     * @param string $type
     * @param Phrase|null $message
     * @return Throwable
     * @throws Exception
     */
    public function create(
        string $type = Exception::class,
        ?Phrase $message = null
    ) {
        /** @var array $arguments */
        $arguments = [];

        /* If no message was given, set default message. */
        $message = $message ?? __(self::ERROR_DEFAULT_MESSAGE);

        if (!is_subclass_of($type, Throwable::class)) {
            throw new Exception(
                __(
                    self::ERROR_INVALID_EXCEPTION_TYPE,
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
