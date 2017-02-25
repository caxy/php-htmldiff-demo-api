<?php

namespace DiffCompare\DiffAdapter;

use Caxy\HtmlDiff\HtmlDiff;
use Caxy\HtmlDiff\HtmlDiffConfig;
use Diff\DiffEngine\DiffEngineInterface;
use HTMLPurifier;
use HTMLPurifier_Config;

/**
 * Class CaxyPhpHtmlDiff
 * @package Diff\DiffEngine
 *
 * https://github.com/caxy/php-htmldiff
 */
class CaxyPhpHtmlDiff implements DiffEngineInterface
{
    /**
     * @var \HTMLPurifier
     */
    private $purifier;

    /**
     * @var string
     */
    private $purifierCachePath = __DIR__.'/../../../var/cache';

    public function __construct()
    {
        $purifierConfig = HTMLPurifier_Config::createDefault();
        $purifierConfig->set('Cache.SerializerPath', $this->purifierCachePath);
        $purifierConfig->set('Cache.SerializerPermissions', 777);
        $this->addTagTransform('b', 'strong', $purifierConfig);
        $this->addTagTransform('i', 'em', $purifierConfig);
        $this->purifier = new HTMLPurifier($purifierConfig);
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'caxy_htmldiff';
    }

    /**
     * @param string $oldText
     * @param string $newText
     * @param array $options
     *
     * @return string
     */
    public function getHtmlDiff($oldText, $newText, array $options = [])
    {
        $config = HtmlDiffConfig::create();
        $config->setSpecialCaseTags([]);
        $config->setSpecialCaseChars(['.', ',', '(', ')', "'", ':', '-', '_']);
        $config->setEncoding('UTF-8');
        $config->setInsertSpaceInReplace(true);
        $config->setPurifierCacheLocation($this->purifierCachePath);

        if (array_key_exists('match_threshold', $options)) {
            $config->setMatchThreshold($options['match_threshold']);
        }

        if (array_key_exists('use_table_diffing', $options)) {
            $config->setUseTableDiffing($options['use_table_diffing']);
        }

        $oldText = $this->purifier->purify($oldText);
        $newText = $this->purifier->purify($newText);

        $diff = HtmlDiff::create($oldText, $newText, $config);

        $diffOutput = $diff->build();

        $diffOutput = $this->checkForListItemsInDiffContent($diffOutput);

        return iconv('UTF-8', 'UTF-8//IGNORE', $diffOutput);
    }

    /**
     * @return array
     */
    public static function getAvailableOptions()
    {
        return [
            'match_threshold' => [
                'type' => 'number',
                'default' => 80,
            ],
            'use_table_diffing' => [
                'type' => 'boolean',
                'default' => true,
            ],
        ];
    }

    /**
     * @return array
     */
    public static function getMetadata()
    {
        return [
            'url' => 'https://github.com/caxy/php-htmldiff',
            'prettyName' => 'caxy/php-htmldiff',
            'description' => 'This is the diffing engine that is currently used in cdpACCESS.',
        ];
    }

    /**
     * @param string $from
     * @param string $to
     * @param \HTMLPurifier_Config $config
     */
    private function addTagTransform($from, $to, HTMLPurifier_Config $config)
    {
        $def = $config->getHTMLDefinition(true);
        $def->info_tag_transform[$from] = new \HTMLPurifier_TagTransform_Simple($to);
    }

    /**
     * Check for an ordered list contained in section content
     * if found, process with appropriate classes.
     *
     * @param string $diffContent
     *
     * @return string
     */
    public function checkForListItemsInDiffContent($diffContent)
    {
        //check for <ol>, <li> tags in diffContent
        $searchPattern = '@<(ol).+>@i';

        if (preg_match($searchPattern, $diffContent)) {
            $diffContent = $this->processDiffListItem($diffContent);
        }

        return $diffContent;
    }

    /**
     * process diff list item content and apply classes to allow for renumbering and styling
     * of list item numbers.
     *
     * @param string $diffText
     *
     * @return string
     */
    public function processDiffListItem($diffText)
    {
        $orderedListClass = 'diff-list';
        $dom = new \DOMDocument();

        // Wrap the text in a div, which is used to output the HTML manipulated by DOM
        // without the DOCTYPE and html tags the DOMDocument adds
        try {
            libxml_use_internal_errors(true);
            // Load html as utf encoded content.
            $dom->loadHTML('<?xml encoding="UTF-8">' . $diffText);
            foreach ($dom->childNodes as $item) {
                // Remove what we don't want.
                if ($item->nodeType == XML_PI_NODE) {
                    $dom->removeChild($item);
                }
            }
            // Set encoding.
            $dom->encoding = 'UTF-8'; // insert proper
            libxml_use_internal_errors(false);
            libxml_clear_errors();
        } catch (\Exception $e) {
            return $diffText;
        }

        $orderedLists = $dom->getElementsByTagName('ol');
        foreach ($orderedLists as $orderedList) {
            $orderedListClasses = $orderedList->getAttribute('class');
            $orderedListClasses = empty($orderedListClasses) ? $orderedListClass : $orderedListClasses.' '.$orderedListClass;
            $orderedList->setAttribute('class', $orderedListClasses);

            $listItems = $orderedList->getElementsByTagName('li');

            $removedItem = false;
            foreach ($listItems as $key => $listItem) {
                $listItemClasses = $listItem->getAttribute('class');
                if ($this->isDeletedListItem($listItem)) {
                    $assignedClass = 'removed';
                    $removedItem = true;
                } elseif ($removedItem) {
                    //first non-removed item after a removed item gets assigned class for renumbering
                    $assignedClass = 'replacement';
                    $removedItem = false;
                } else {
                    $assignedClass = 'normal';
                }
                $listItemClasses = empty($listItemClasses) ? $assignedClass : $listItemClasses.' '.$assignedClass;
                $listItem->setAttribute('class', $listItemClasses);
            }
        }

        return $dom->saveHTML();
    }

    /**
     * Check if all contents of list item are marked for deletion.
     *
     * @param \DOMElement $listItem
     *
     * @return bool
     */
    protected function isDeletedListItem(\DOMElement $listItem)
    {
        $deletedItem = false;
        $deletedContent = '';
        $listItemContent = $listItem->nodeValue;

        //Check for proposal processing with span style line-through
        $spanElements = $listItem->getElementsByTagName('span');
        foreach ($spanElements as $spanElement) {
            $style = $spanElement->getAttribute('style');
            $nodeContent = trim($spanElement->nodeValue, " \n\r\t\0\xC2\xA0");
            if (!empty($nodeContent) && preg_match('/text-decoration:[\s]*line-through;/i', $style)) {
                $deletedContent .= $spanElement->nodeValue;
            }
        }

        //if sum of del tag content equals the li tag contents, then all contents have been removed
        $childNodes = $listItem->getElementsByTagName('del');
        foreach ($childNodes as $childNode) {
            $nodeContent = trim($childNode->nodeValue, " \n\r\t\0\xC2\xA0");
            if (!empty($nodeContent)) {
                $deletedContent .= $childNode->nodeValue;
            }
        }

        if (trim($deletedContent) == trim($listItemContent)) {
            $deletedItem = true;
        }

        return $deletedItem;
    }
}
