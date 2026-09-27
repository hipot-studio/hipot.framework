<?php

declare(strict_types=1);

namespace Hipot\Types\Enums;

/**
 * Стандартные идентификаторы типов пользовательских полей Bitrix.
 */
enum UserFieldTypes: string
{
	case STRING = 'string'; // Строка
	case STRING_FORMATTED = 'string_formatted'; // Шаблон
	case INTEGER = 'integer'; // Целое число
	case DOUBLE = 'double'; // Число
	case DATE = 'date'; // Дата
	case DATETIME = 'datetime'; // Дата со временем
	case BOOLEAN = 'boolean'; // Да / Нет
	case FILE = 'file'; // Файл
	case ENUMERATION = 'enumeration'; // Список
	case URL = 'url'; // Ссылка
	case ADDRESS = 'address'; // Адрес
	case VIDEO = 'video'; // Видео
	case IBLOCK_SECTION = 'iblock_section'; // Раздел инфоблока
	case IBLOCK_ELEMENT = 'iblock_element'; // Элемент инфоблока
	case HLBLOCK = 'hlblock'; // Привязка к HL-блоку
	case EMPLOYEE = 'employee'; // Сотрудник
	case CRM = 'crm'; // Элемент CRM
	case CRM_STATUS = 'crm_status'; // Привязка к справочнику CRM
}
