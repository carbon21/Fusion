<?php

/**
	module sql.
	
	do config from file.
	disk cache for design files from db.
*/

function __autoload($name)
{
	$file = strtolower(ltrim(\str_replace('\\', '/', $name.'.php'), '/'));
	if(\file_exists($file)) {
		require_once($file);
	} else {
		throw new \Exception('Class not found: '.$name);
	}
}

define('FSN_EXEC', 1);
ini_set('display_errors', 1); 
error_reporting(E_ALL);
session_start();

$appContext = \Core\Application\ApplicationContext::Current();

\Core\Application\Debug::Write('Application Starting', 'Application');

$appContext->LoadConfig();

\Core\Application\ModuleManager::Init();
\Core\Application\ModuleManager::Load('User', array());
\Core\Application\ModuleManager::Load('Form', array());
\Core\Application\ModuleManager::Load('Route', array());
\Core\Application\ModuleManager::Load('Location', array());

$appContext->SetControllerFactory(new \Core\Controllers\ControllerFactory());
$appContext->RegisterRouteHandler(new \Core\Routing\DefaultRouteHandler());
$appContext->RegisterRequestHandler(new \Core\Request\DefaultRequestHandler());

$appContext->RegisterRoute('area', '/area?/controller?/action?', array('controller' => 'home', 'action' => 'index'));
$appContext->RegisterRoute('module', '/module/module?/controller?/action?', array('controller' => 'default'));
$appContext->RegisterRoute('module2', '/module/module?/action?', array('controller' => 'default'));
$appContext->RegisterRoute('default', '/controller?/action?', array('controller' => 'home'));
$appContext->RegisterRoute('content', '/url?', array('controller' => 'home', 'action' => 'index', 'url' => ''));

\Core\Application\ValidationManager::Current();

$appContext->Execute();

\Core\Application\Debug::Write('Application Ending', 'Application');

if(\Core\Application\ApplicationContext::Current()->GetConfig('debug') == 'On')
	\Core\Application\Debug::Display();

?>
