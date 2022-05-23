<?php
/**
 * PathUtils.php
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the MIT license, which
 * is bundled with this package in the file LICENSE.txt.
 *
 * It is also available on the Internet at the following URL:
 * https://docs.auroraextensions.com/magento/extensions/2.x/modulecomponents/LICENSE.txt
 *
 * @package     AuroraExtensions\ModuleComponents\Model\Utils
 * @copyright   Copyright (C) 2021 Aurora Extensions <support@auroraextensions.com>
 * @license     MIT
 */
declare(strict_types=1);

namespace AuroraExtensions\GoogleCloudStorage\Model\Utils;

use Magento\Framework\Stdlib\StringUtils;

use const DIRECTORY_SEPARATOR;
use const PHP_MAXPATHLEN;
use function array_filter;
use function array_map;
use function array_merge;
use function array_pop;
use function array_shift;
use function array_values;
use function explode;
use function implode;

class PathUtils
{
    /** @constant int TRIM_LEFT */
    public const TRIM_LEFT = 1;

    /** @constant int TRIM_RIGHT */
    public const TRIM_RIGHT = 2;

    /** @constant array TRIM_CONTEXTS */
    public const TRIM_CONTEXTS = [
        self::TRIM_LEFT => 'ltrim',
        self::TRIM_RIGHT => 'rtrim',
        self::TRIM_LEFT | self::TRIM_RIGHT => 'trim',
    ];

    /** @var StringUtils $stringUtils */
    private $stringUtils;

    /**
     * @param StringUtils $stringUtils
     * @return void
     */
    public function __construct(StringUtils $stringUtils)
    {
        $this->stringUtils = $stringUtils;
    }

    /**
     * @param string[] $pieces
     * @return string
     */
    public function build(string ...$pieces): string
    {
        /** @var string $basePath */
        $basePath = $this->trim((string) array_shift($pieces), self::TRIM_RIGHT);

        if (!empty($basePath)) {
            /** @var bool $isAbsolute */
            $isAbsolute = false;

            if ($basePath[0] === DIRECTORY_SEPARATOR) {
                $isAbsolute = true;
            }

            /** @var string[] $baseParts */
            $baseParts = $this->stringUtils->split($basePath, PHP_MAXPATHLEN, true, true, '\/');

            /** @var string[] $basePaths */
            $basePaths = $this->split(!empty($baseParts) ? $baseParts[0] : $basePath, ' ', true);
            $basePath = $this->concat($isAbsolute ? array_merge([null], $basePaths) : $basePaths);
        }

        /** @var array $result */
        $result[] = [$basePath];

        /** @var string $basename */
        $basename = $this->trim((string) array_pop($pieces));

        if (!empty($basename)) {
            /** @var string[] $nameParts */
            $nameParts = $this->split($basename, DIRECTORY_SEPARATOR, true);
            $basename = $this->concat($nameParts);
        }

        /** @var string[] $dirs */
        $dirs = $this->filter($pieces);

        /** @var int|string $key */
        /** @var string $value */
        foreach ($dirs as $key => $value) {
            /** @var string[] $parts */
            $parts = $this->stringUtils->split($value, PHP_MAXPATHLEN, true, true, '\/');

            /** @var string[] $paths */
            $paths = $this->split(!empty($parts) ? $parts[0] : $value, ' ', true);

            /** @var string $path */
            $path = $this->concat($paths);
            $dirs[$key] = $this->trim($path);
        }

        $result[] = $dirs;
        $result[] = [$basename];

        return $this->concat(array_merge(...$result));
    }

    /**
     * @param array $pieces
     * @param string $delimiter
     * @param bool $filter
     * @return string
     */
    public function concat(
        array $pieces,
        string $delimiter = DIRECTORY_SEPARATOR,
        bool $filter = false
    ): string {
        if ($filter) {
            $pieces = array_filter($pieces, 'strlen');
        }

        return implode($delimiter, $pieces);
    }

    /**
     * @param array $pieces
     * @param callable|null $callback
     * @param bool $preserveKeys
     * @return array
     */
    public function filter(
        array $pieces,
        callable $callback = null,
        bool $preserveKeys = false
    ): array {
        /* Defaults strlen for empty values. */
        $callback = $callback ?? 'strlen';

        /** @var array $result */
        $result = array_filter($pieces, $callback);
        return $preserveKeys ? $result : array_values($result);
    }

    /**
     * @param string $subject
     * @param string $delimiter
     * @param bool $filter
     * @return string[]
     */
    public function split(
        string $subject,
        string $delimiter = DIRECTORY_SEPARATOR,
        bool $filter = false
    ): array {
        /** @var string[] $pieces */
        $pieces = explode($delimiter, $subject);

        if ($filter) {
            $pieces = array_filter($pieces, 'strlen');
        }

        return array_values($pieces);
    }

    /**
     * @param string $subject
     * @param int $context
     * @param string $delimiter
     * @return string
     */
    public function trim(
        string $subject,
        int $context = self::TRIM_LEFT | self::TRIM_RIGHT,
        string $delimiter = DIRECTORY_SEPARATOR
    ): string {
        /** @var string|null $callback */
        $callback = self::TRIM_CONTEXTS[$context] ?? null;

        if ($callback === null) {
            return $subject;
        }

        /** @var array $result */
        $result = array_map($callback, [$subject], [$delimiter]);
        return !empty($result) ? $result[0] : $subject;
    }
}