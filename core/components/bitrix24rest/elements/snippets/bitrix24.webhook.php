<?php
if(!$hook)return false;
if($hook->formit)$controller=&$hook->formit;
if($hook->controller&&$hook->controller->login)$controller=&$hook->controller->login;
if(!$controller)return false;

$config=$controller->config;
$fields=$hook->fields;

$success=true;
$service=$modx->getService('bitrix24rest','bitrix24REST',MODX_CORE_PATH.'components/bitrix24rest/model/bitrix24rest/');
$service->initialize(['mode'=>'webhook']);

$actions=$config['bitrix24'];
if(!is_array($actions))$actions = $hook->modx->fromJSON($actions)?:[];

$success=$service->processActions($hook,$fields,$actions);

return $success;