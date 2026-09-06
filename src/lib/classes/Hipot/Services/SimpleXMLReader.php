<?php
/**
 * hipot studio source file <info AT hipot-studio DOT com>
 * Created 13.04.2023 18:11
 * @version pre 1.0
 */
namespace Hipot\Services;

use XMLReader;

/**
 * A utility class for reading XML files and parsing their contents.
 */
class SimpleXMLReader
{
	/**
	 * <code>
	 * foreach (SimpleXMLReader::readXmlFile('file.xml', 'item') as $item) {
	 *      echo $item->name . ": " . $item->price . "<br>";
	 * }
	 * </code>
	 *
	 * @param string $filePath
	 * @param string $needTagName
	 * @return \Generator
	 */
	public static function readXmlFile(string $filePath, string $needTagName): \Generator
	{
		$xml_reader = new XMLReader();
		$xml_reader->open($filePath);

		try {
			while ($xml_reader->read()) {
				if ($xml_reader->nodeType == XMLReader::ELEMENT && $xml_reader->name == $needTagName) {
					yield simplexml_load_string($xml_reader->readOuterXml());
				}
			}
		} finally {
			$xml_reader->close();
		}
	}

	/**
	 * <code>
	 * $reader = new \XMLReader();
	 * $reader->open('file.xml');
	 * SimpleXMLReader::readXmlFileCallback($xml, 'item', static function (array $item) {
	 *      // $item['tag']
	 *      // $item['childs']
	 *      // $item['attr']
	 *      // $item['text']
	 * });
	 * $reader->close();
	 * </code>
	 *
	 * @param \XMLReader $xml
	 * @param string     $needTagName
	 * @param callable   $callback
	 *
	 * @return array
	 */
	public static function readXmlCallback(XMLReader $xml, string $needTagName, callable $callback): array
	{
		$tree = [];

		while ($xml->read()) {
			if ($xml->nodeType == XMLReader::END_ELEMENT) {
				return $tree;
			} else if ($xml->nodeType == XMLReader::ELEMENT) {
				$node = [];

				$node['tag'] = $xml->name;

				if (!$xml->isEmptyElement) {
					$childs         = self::readXmlCallback($xml, $needTagName, $callback);
					$node['childs'] = $childs;
				}

				if ($xml->hasAttributes) {
					$attributes = [];
					while ($xml->moveToNextAttribute()) {
						$attributes[$xml->name] = $xml->value;
					}
					$node['attr'] = $attributes;
				}

				if ($node['tag'] == $needTagName) {
					$callback($node);
					$tree = [];     // drop find item recursive
				} else {
					$tree[] = $node;
				}
			} else if ($xml->nodeType == XMLReader::TEXT) {
				$node         = [];
				$node['text'] = $xml->value;
				$tree[]       = $node;
			}
		}

		return $tree;
	}
}