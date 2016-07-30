<?php 

function mail_autoloader($class) {
	$path = sprintf('%s.php', str_replace('\\', DIRECTORY_SEPARATOR, $class));
	$path = __DIR__ . DIRECTORY_SEPARATOR . 'lib' . DIRECTORY_SEPARATOR . $path;
	
	if (file_exists($path)) include_once($path);
}
spl_autoload_register('mail_autoloader');