<?php

declare(strict_types=1);

namespace mirzaev\site\account\controllers;

// Файлы проекта
use mirzaev\site\account\views\templater,
  mirzaev\site\account\models\core as models,
  mirzaev\site\account\models\account,
  mirzaev\site\account\models\session;

// Фреймворк PHP
use mirzaev\minimal\controller;

// Встроенные библиотеки
use exception;

/**
 * Ядро контроллеров
 *
 * @package mirzaev\site\account\controllers
 * @author Arsen Mirzaev Tatyano-Muradovich <arsen@mirzaev.sexy>
 */
class core extends controller
{
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
   * @param bool $initialize Инициализировать контроллер?
   */
  public function __construct(bool $initialize = true)
  {
    parent::__construct($initialize);

    if ($initialize) {
      // Запрошена инициализация

      // Инициализация ядра моделей (соединение с базой данных...)
      new models();

      // Инициализация даты до которой будет активна сессия
      $expires = time() + 604800;

      // Инициализация значения по умолчанию
      $_COOKIE["session"] ??= null;

      // Инициализация сессии
      $this->session = new session($_COOKIE["session"], $expires);

      if ($_COOKIE["session"] !== $this->session->hash) {
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

      // Инициализация аккаунта
      $this->account = new account($this->session);

      // Инициализация шаблонизатора представлений
      $this->view = new templater($this->session, $this->account);
    }
  }
}
