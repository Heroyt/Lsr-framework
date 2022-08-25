<?php
/**
 * @file  config/constants.php
 * @brief Constants that need to be imported into DI container
 */

if (!defined('ROOT')) {
	define('ROOT', dirname(__DIR__).'/');
}

require_once ROOT.'include/constants.php';

return [
	'parameters' => [
		'constants' => [
			'appDir'      => ROOT,
			'tempDir'     => TMP_DIR,
			'logDir'      => LOG_DIR,
			'templateDir' => TEMPLATE_DIR,
			'uploadDir'   => UPLOAD_DIR,
			'privateDir'  => PRIVATE_DIR,
			'languageDir' => LANGUAGE_DIR,
			'assetsDir'   => ASSETS_DIR,
		]
	]
];