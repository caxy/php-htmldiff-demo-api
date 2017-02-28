<?php

namespace Diff;

use Diff\DiffEngine\DiffEngineInterface;

/**
 * Class DiffDispatcher
 * @package Diff
 */
class DiffDispatcher
{
    /**
     * @var array
     */
    private $engines = [];

    /**
     * DiffDispatcher constructor.
     * @param array $engines
     */
    public function __construct(array $engines = [])
    {
        foreach ($engines as $engine) {
            $this->addEngine($engine);
        }
    }

    /**
     * @return array
     */
    public function getEngines(): array
    {
        return $this->engines;
    }

    /**
     * @param array $engines
     * @return DiffDispatcher
     */
    public function setEngines(array $engines): DiffDispatcher
    {
        $this->engines = $engines;
        return $this;
    }

    /**
     * @param DiffEngineInterface $engine
     * @return DiffDispatcher
     */
    public function addEngine(DiffEngineInterface $engine): DiffDispatcher
    {
        if (!isset($this->engines[$engine->getName()])) {
            $this->engines[$engine->getName()] = $engine;
        }

        return $this;
    }

    /**
     * @param string|DiffEngineInterface $engine
     * @return bool
     */
    public function removeEngine($engine): bool
    {
        $key = $engine instanceof DiffEngineInterface ? $engine->getName() : $engine;

        if (!is_string($key)) {
            throw new \InvalidArgumentException(
                sprintf(
                    'Invalid argument passed to %s. Must be a string or instance of DiffEngineInterface.',
                    __METHOD__
                )
            );
        }

        if (isset($this->engines[$key])) {
            unset($this->engines[$key]);

            return true;
        }

        return false;
    }

    /**
     * @param string $engine
     * @param string $htmlOld
     * @param string $htmlNew
     * @return string
     */
    public function getHtmlDiff(string $engine, string $htmlOld, string $htmlNew)
    {
        if (!isset($this->engines[$engine])) {
            throw new \InvalidArgumentException(sprintf('No engine with name "%s" registered.', $engine));
        }

        return $this->engines[$engine]->getHtmlDiff($htmlOld, $htmlNew);
    }
}
