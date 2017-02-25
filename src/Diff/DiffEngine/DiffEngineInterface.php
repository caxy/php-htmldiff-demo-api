<?php

namespace Diff\DiffEngine;

/**
 * Interface DiffEngineInterface
 * @package Diff\DiffEngine
 */
interface DiffEngineInterface
{
    /**
     * @return string
     */
    public function getName();

    /**
     * @param string $oldText
     * @param string $newText
     * @param array $options
     *
     * @return string
     */
    public function getHtmlDiff($oldText, $newText, array $options = []);

    /**
     * @return array
     */
    public static function getAvailableOptions();

    /**
     * @return array
     */
    public static function getMetadata();
}
