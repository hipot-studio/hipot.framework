<?php
/**
 * Файл с различными глобальными рубильниками-константами
 */

use Hipot\Services\BitrixEngine;

/**
 * @global $APPLICATION \CMain
 * @global $USER \CUser
 * @global $DB \CDatabase
 * @global $USER_FIELD_MANAGER \CUserTypeManager
 */

$be      = BitrixEngine::getInstance();
$request = $be->request;

/**
 * На сайте бета-тестировщик
 */
define('IS_BETA_TESTER', $be->user->isAdmin() || $be->user->getLogin() == 'hipot@ya.ru' || str_contains($be->user->getLogin(), '@hipot-studio.com'));

/**
 * Группа контент-редактора
 */
const CONTENT_MANAGER_GID = 0;      // TODO set correct group

/**
 * На сайте редактор
 */
define('IS_CONTENT_MANAGER', IS_BETA_TESTER || (CONTENT_MANAGER_GID > 0 && CSite::InGroup([CONTENT_MANAGER_GID])));

/**
 * Should PhpCacher use tagged cache in callback-function
 * @var bool PHPCACHER_TAGGED_CACHE_AUTOSTART
 */
const PHPCACHER_TAGGED_CACHE_AUTOSTART = false;

/**
 * Override The default cache service for PHPCacher on ALL cache()-calls
 * This means that when it's not empty then PHPCacher is used without explicitly providing a cache service (see 'services'-key in .settings_extra.php)
 * @var string PHPCACHER_DEFAULT_CACHE_SERVICE
 */
const PHPCACHER_DEFAULT_CACHE_SERVICE = '';