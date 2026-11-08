<?php

use Bitrix\Main\EventManager;
use Bitrix\Main\Loader;
use Hipot\BitrixUtils\Iblock\ElementDescriptionType;
use Hipot\UserField\HtmlType;
use Hipot\UserField\UserType;

require_once dirname(__DIR__, 2) . '/Stubs/PropertyTypes.php';

it('registers all three property types through legacy compatible events', function (): void {
	defined('B_PROLOG_INCLUDED') || define('B_PROLOG_INCLUDED', true);
	$manager = EventManager::getInstance();
	$previous = $manager->handlers;
	try {
		$manager->reset();
		$eventManager = $manager;
		require dirname(__DIR__, 3) . '/src/lib/handlers/property_types.php';
		$types = $manager->send('main', 'OnUserTypeBuildList');
		expect(array_column($types, 'USER_TYPE_ID'))->toBe(['hipot_user', 'hipot_html']);
		expect($manager->send('iblock', 'OnIBlockPropertyBuildList')[0]['PROPERTY_TYPE'])->toBe('E');
	} finally {
		$manager->handlers = $previous;
	}
});

it('preserves multiple editor names and HTML without touching POST', function (): void {
	$previousModules = Loader::$modules;
	$previousPost = $_POST;
	try {
		Loader::$modules['fileman'] = true;
		CHTMLEditor::$calls = [];
		$_POST = ['UF_BODY_0_' => 'unrelated', 'UF_BODY' => ['<p>First & second</p>', '<b>Third</b>']];
		$originalPost = $_POST;
		HtmlType::getPublicEdit([
			'FIELD_NAME' => 'UF_BODY', 'MULTIPLE' => 'Y', 'VALUE' => $_POST['UF_BODY'],
		]);
		expect(array_column(CHTMLEditor::$calls, 'name'))->toBe(['UF_BODY[0]', 'UF_BODY[1]', 'UF_BODY[2]']);
		expect(array_column(CHTMLEditor::$calls, 'content'))->toBe(['<p>First & second</p>', '<b>Third</b>', '']);
		expect(count(array_unique(array_column(CHTMLEditor::$calls, 'id'))))->toBe(3);
		foreach ($_POST['UF_BODY'] as $value) {
			expect(HtmlType::onBeforeSave(['MULTIPLE' => 'Y'], $value))->toBe($value);
		}
		expect($_POST)->toBe($originalPost);
	} finally {
		Loader::$modules = $previousModules;
		$_POST = $previousPost;
	}
});

it('decodes legacy control values once and escapes the textarea fallback', function (): void {
	$previous = Loader::$modules;
	try {
		Loader::$modules['fileman'] = false;
		$html = HtmlType::getEditFormHtml(['ENTITY_VALUE_ID' => 12], [
			'NAME' => 'UF_BODY[0]', 'VALUE' => '&lt;p&gt;&amp;amp;&lt;/p&gt;',
		]);
		expect($html)->toContain('name="UF_BODY[0]"', '&lt;p&gt;&amp;amp;&lt;/p&gt;');
		expect(HtmlType::getPublicView(['VALUE' => '<p>&amp;</p>']))->toBe('<p>&amp;</p>');
	} finally {
		Loader::$modules = $previous;
	}
});

it('escapes link descriptions and uses the popup result target for each control', function (): void {
	$html = ElementDescriptionType::getPropertyFieldHtml(['LINK_IBLOCK_ID' => 7], [
		'VALUE' => '', 'DESCRIPTION' => '"><script>alert(1)</script>',
	], ['VALUE' => 'PROP[42][n0][VALUE]', 'DESCRIPTION' => 'PROP[42][n0][DESCRIPTION]']);
		expect($html)->toContain('name="PROP[42][n0][DESCRIPTION]"', '&lt;script&gt;',
		'id="sp_' . md5('PROP[42][n0][VALUE]') . '_0"');
		expect($html)->not->toContain('<script>');
});

it('rejects invalid user references before performing a user query', function ($value): void {
	$errors = UserType::checkFields(['FIELD_NAME' => 'UF_AUTHOR'], $value);
		expect($errors)->toHaveCount(1);
		expect($errors[0]['id'])->toBe('UF_AUTHOR');
})->with([-1, '1.5', '1 OR 1=1', '2147483648', [['1']]]);

it('normalizes empty user references without a database lookup', function ($value): void {
	$field = ['FIELD_NAME' => 'UF_AUTHOR'];
		expect(UserType::checkFields($field, $value))->toBe([]);
		expect(UserType::onBeforeSave($field, $value))->toBeNull();
})->with([null, false, '', 0, '0']);
