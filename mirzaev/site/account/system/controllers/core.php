<?php

declare(strict_types=1);

namespace mirzaev\site\account\controllers;

// Файлы проекта
use mirzaev\site\account\views\templater;
use mirzaev\site\account\models\core as models;
use mirzaev\site\account\models\account;
use mirzaev\site\account\models\session;

// Библиотека для ArangoDB
use ArangoDBClient\Document as _document;

// Фреймворк PHP
use mirzaev\minimal\controller;

// Фреймворк ВКонтакте
use mirzaev\vk\core as vk;
use mirzaev\vk\robots\user as robot;

/**
 * Ядро контроллеров
 *
 * @package mirzaev\site\account\controllers
 * @author Arsen Mirzaev Tatyano-Muradovich <arsen@mirzaev.sexy>
 */
class core extends controller
{
  /**
   * Переменные окружения
   */
  protected robot $vk;

  /**
   * Инстанция сессии
   */
  public session $session;

  /**
   * Инстанция аккаунта
   */
  public ?account $account;

  /**
   * Постфикс
   */
  public string $postfix = '';

  /**
   * Реестр ошибок
   */
  public array $errors = [
    'session' => [],
    'account' => []
  ];

  /**
   * Конструктор
   *
   * @return void
   */
  public function __construct()
  {
    parent::__construct();

    // Инициализация ядра моделей (соединение с базой данных...)
    new models();

    // Инициализация шаблонизатора представлений
    $this->view = new templater;

    // Инициализация даты до которой будет активна сессия
    $expires = time() + 604800;

    // Инициализация сессии (без журналирования)
    $this->session = new session($_COOKIE["session"] ?? null, $expires) ??
      header('Location: https://mirzaev.sexy/error?code=500&text=Не+удалось+инициализировать+сессию');

    if ($_COOKIE["session"] ?? null !== $this->session->hash) {
      // Изменился хеш сессии (подразумевается, что сессия устарела)

      // Запись хеша новой сессии
      setcookie('session', $this->session->hash, [
        'expires' => $expires,
        'domain' => 'mirzaev.sexy',
        'path' => '/',
        'secure' => true,
        'httponly' => true,
        'samesite' => 'strict'
      ]);
    }

    // Инициализация аккаунта (без журналирования)
    $this->account = $this->session->account();

    if ($this->account instanceof _document) {
      // Инициализирован аккаунт

    }
  }
}
