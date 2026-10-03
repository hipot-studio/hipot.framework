<?
namespace Hipot\Utils;

use Hipot\Utils\Helper\ArrayTools;
use Hipot\Utils\Helper\ObjectTools;
use Hipot\Utils\Helper\StringUtils;
use Hipot\Utils\Helper\ContextUtils;
use Hipot\Utils\Helper\ComponentUtils;
use Hipot\Utils\Helper\DateTimeUtils;
use Hipot\Utils\Helper\UserFieldUtils;
use Hipot\Utils\Helper\WebFormUtils;

/**
 * Различные не-структурированные утилиты
 *
 * @version 1.0
 * @author hipot studio
 */
class UUtils
{
	// text utils
	use StringUtils;

	// environment utils
	use ContextUtils;

	// bx components utils
	use ComponentUtils;

	// calendar utils
	use DateTimeUtils;

	// UF_-field utils
	use UserFieldUtils;

	// misc collection utils
	use ArrayTools;

	// misc object utils
	use ObjectTools;

	// form module utils
	use WebFormUtils;

} // end class

