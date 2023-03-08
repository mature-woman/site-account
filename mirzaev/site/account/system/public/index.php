<?php

declare(strict_types=1);

namespace mirzaev\site\account;

// Файлы проекта
use mirzaev\site\account\controllers\core as controller,
  mirzaev\site\account\models\core as model;

// Фреймворк
use mirzaev\minimal\core,
  mirzaev\minimal\router;

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
$router->write('/account/initialization', 'account', 'initialization', 'POST');
$router->write('/account/vk/connect', 'account', 'connect');
$router->write('/account/panel', 'account', 'panel');
$router->write('/api/generate/password', 'api', 'password', 'POST');
$router->write('/session/login', 'session', 'login', 'POST');
$router->write('/session/password', 'session', 'password', 'POST');
$router->write('/session/invite', 'session', 'invite', 'POST');

// Инициализация ядра
$core = new core(namespace: __NAMESPACE__, router: $router, controller: new controller(false), model: new model(false));

// Обработка запроса
echo $core->start();
