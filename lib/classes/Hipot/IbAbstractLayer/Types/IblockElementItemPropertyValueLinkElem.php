<?
namespace Hipot\IbAbstractLayer\Types;

/**
 * Значение свойств инфоблока, возвращаемые CIBlockElement::GetProperty()
 * @author hipot
 * @version 1.0
 */
class IblockElementItemPropertyValueLinkElem extends IblockElementItemPropertyValue
{
	/**
	 * Цепочка из связанных элементов
	 * @var IblockElementItem
	 */
	public $CHAIN;
}
