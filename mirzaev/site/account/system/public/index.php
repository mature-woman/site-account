<?php

declare(strict_types=1);

namespace mirzaev\site\account;

use mirzaev\minimal\core;
use mirzaev\minimal\router;

ini_set('error_reporting', E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

define('VIEWS', realpath('..' . DIRECTORY_SEPARATOR . 'views'));
define('STORAGE', realpath('..' . DIRECTORY_SEPARATOR . 'storage'));
define('INDEX', __DIR__);

// Автозагрузка
require __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . '..'  . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';

// Инициализация маршрутазитора
$router = new router;

// Запись маршрутов
$router->write('/', 'index', 'index');
$router->write('/system/hotline', 'hotline', 'index');
$router->write('/system/graph', 'graph', 'index');
$router->write('/account/initialization', 'account', 'initialization', 'PUT');
$router->write('/account/vk/connect', 'account', 'connect');
$router->write('/account/panel', 'account', 'panel');

// Инициализация ядра
$core = new core(namespace: __NAMESPACE__, router: $router);

// Обработка запроса
echo $core->start();
