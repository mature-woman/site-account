<?php

declare(strict_types=1);

namespace mirzaev\site\account\controllers;

// Файлы проекта
use mirzaev\site\account\controllers\core,
  mirzaev\site\account\controllers\traits\errors,
  mirzaev\site\account\models\invite,
  mirzaev\site\account\models\account;

// Встроенные библиотеки
use exception;

/**
 * Контроллер сессии 
 *
 * @package mirzaev\site\account\controllers
 * @author Arsen Mirzaev Tatyano-Muradovich <arsen@mirzaev.sexy>
 */
final class session extends core
{
  use errors;

  /**
   * Записать входной псевдоним в буфер сессии
   *
   * Проверяет существование аккаунта с этим входным псевдонимом
   * и запоминает для использования в процессе аутентификации
   *
   * @param array $parameters Параметры запроса
   * 
   * @return void В буфер вывода JSON-документ с запрашиваемыми параметрами
   */
  public function login(array $parameters = []): void
  {
    // Инициализация буфера ответа
    $buffer = [];

    // Инициализация реестра возвращаемых параметров
    $return = explode(',', $parameters['return'], 50);

    try {
      // Проверка наличия обязательных параметров
      if (empty($parameters['login'])) throw new exception('Необходимо передать входной псевдоним');

      // Вычисление длины
      $length = strlen($parameters['login']);

      // Проверка параметров на соответствование требованиям
      if ($length === 0) throw new exception('Входной псевдоним не может быть пустым');
      if ($length > 100) throw new exception('Входной псевдоним не может быть длиннее 100 символов');
      if (preg_match_all('/[^\w\s\r\n\t\0]+/u', $parameters['login'], $matches) > 0) throw new exception('Нельзя использовать символы: ' . implode(', ', ...$matches));

      // Поиск аккаунта
      $account = account::login($parameters['login']);

      // Генерация ответа по запрашиваемым параметрам
      foreach ($return as $parameter) match ($parameter) {
        'exist' => $buffer['exist'] = isset($account->document),
        'account' => (function () use ($parameters, &$buffer) {
          // Запись в буфер сессии
          if (isset($parameters['remember']) && $parameters['remember'] === '1')
            $this->session->write(['entry' => ['login' => $parameters['login']]], $this->errors);

          // Поиск аккаунта и запись в буфер вывода
          $buffer['account'] = isset((new account($this->session, authenticate: true, errors: $this->errors))->document);
        })(),
        'errors' => null,
        default => throw new exception("Параметр не найден: $parameter")
      };
    } catch (exception $e) {
      // Запись в реестр ошибок
      $this->errors['session'][] = [
        'text' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine(),
        'stack' => $e->getTrace()
      ];
    }

    // Запись реестра ошибок в буфер ответа
    if (in_array('errors', $return, true)) $buffer['errors'] = self::parse_only_text($this->errors);

    // Запись заголовков ответа
    header('Content-Type: application/json');
    header('Content-Encoding: none');
    header('X-Accel-Buffering: no');

    // Инициализация буфера вывода
    ob_start();

    // Генерация ответа
    echo json_encode($buffer);

    // Запись заголовков ответа
    header('Content-Length: ' . ob_get_length());

    // Отправка и деинициализация буфера вывода
    ob_end_flush();
    flush();

    // Запись в буфер сессии
    if (!in_array('account', $return, true) && isset($parameters['remember']) && $parameters['remember'] === '1')
      $this->session->write(['entry' => ['login' => $parameters['login']]]);
  }

  /**
   * Записать пароль в буфер сессии
   *
   * Проверяет на соответствие требованиям 
   * и запоминает для использования в процессе аутентификации
   *
   * @param array $parameters Параметры запроса
   * 
   * @return void В буфер вывода JSON-документ с запрашиваемыми параметрами
   */
  public function password(array $parameters = []): void
  {
    // Инициализация буфера ответа
    $buffer = [];

    // Инициализация реестра возвращаемых параметров
    $return = explode(',', $parameters['return'], 50);

    try {
      // Вычисление длины
      $length = strlen($parameters['password']);

      // Проверка параметров на соответствование требованиям
      if ($length > 300) throw new exception('Пароль не может быть длиннее 300 символов');
      if (preg_match_all('/[^\w\s\r\n\t\0]+/u', $parameters['password'], $matches) > 0) throw new exception('Нельзя использовать символы: ' . implode(', ', ...$matches));

      // Генерация ответа по запрашиваемым параметрам
      foreach ($return as $parameter) match ($parameter) {
        'verify' => $buffer['verify'] = true,
        'account' => (function() use ($parameters, &$buffer) {
          // Запись в буфер сессии
          if (isset($parameters['remember']) && $parameters['remember'] === '1')
            $this->session->write(['entry' => ['password' => $parameters['password']]], $this->errors);

          // Поиск аккаунта и запись в буфер вывода
          $buffer['account'] = isset((new account($this->session, authenticate: true, register: true, errors: $this->errors))->document);
        })(),
        'errors' => null,
        default => throw new exception("Параметр не найден: $parameter")
      };
    } catch (exception $e) {
      // Запись в реестр ошибок
      $this->errors['session'][] = [
        'text' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine(),
        'stack' => $e->getTrace()
      ];
    }

    // Запись реестра ошибок в буфер ответа
    if (in_array('errors', $return, true)) $buffer['errors'] = self::parse_only_text($this->errors);

    // Запись заголовков ответа
    header('Content-Type: application/json');
    header('Content-Encoding: none');
    header('X-Accel-Buffering: no');

    // Инициализация буфера вывода
    ob_start();

    // Генерация ответа
    echo json_encode($buffer);

    // Запись заголовков ответа
    header('Content-Length: ' . ob_get_length());

    // Отправка и деинициализация буфера вывода
    ob_end_flush();
    flush();

    // Запись в буфер сессии
    if (!in_array('account', $return, true) && isset($parameters['remember']) && $parameters['remember'] === '1')
      $this->session->write(['entry' => ['password' => $parameters['password']]]);
  }

  /**
   * Записать код приглашения в буфер сессии
   *
   * Проверяет существование приглашения с этим кодом
   * и запоминает для использования в процессе регистрации
   *
   * @param array $parameters Параметры запроса
   * 
   * @return void В буфер вывода JSON-документ с запрашиваемыми параметрами
   */
  public function invite(array $parameters = []): void
  {
    // Инициализация буфера ответа
    $buffer = [];

    // Инициализация реестра возвращаемых параметров
    $return = explode(',', $parameters['return'], 50);

    try {
      // Проверка наличия обязательных параметров
      if (empty($parameters['invite'])) throw new exception('Необходимо передать ключ приглашения');

      // Вычисление длины
      $length = strlen($parameters['invite']);

      // Проверка параметров на соответствование требованиям
      if ($length === 0) throw new exception('Получен пустой ключ приглашения');
      if (preg_match_all('/[^\w\s\r\n\t\0]+/u', $parameters['invite'], $matches) > 0) throw new exception('Нельзя использовать символы: ' . implode(', ', ...$matches));

      // Поиск приглашения
      $invite = invite::read($parameters['invite']);

      // Генерация ответа по запрашиваемым параметрам
      foreach ($return as $parameter) match ($parameter) {
        'exist' => $buffer['exist'] = isset($invite->document),
        // from временное решение пока не будет разработана система сессий
        'from' => $buffer['from'] = ['login' => 'mirzaev'] ?? $invite->from(),
        'account' => (function () use ($parameters, &$buffer) {
          // Запись в буфер сессии
          if (isset($parameters['remember']) && $parameters['remember'] === '1')
            $this->session->write(['entry' => ['invite' => $parameters['invite']]], $this->errors);

          // Поиск аккаунта и запись в буфер вывода
          $buffer['account'] = isset((new account($this->session, authenticate: true, errors: $this->errors))->document);
        })(),
        'errors' => null,
        default => throw new exception("Параметр не найден: $parameter")
      };
    } catch (exception $e) {
      // Запись в реестр ошибок
      $this->errors['session'][] = [
        'text' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine(),
        'stack' => $e->getTrace()
      ];
    }

    // Запись реестра ошибок в буфер ответа
    if (in_array('errors', $return, true)) $buffer['errors'] = self::parse_only_text($this->errors);

    // Запись заголовков ответа
    header('Content-Type: application/json');
    header('Content-Encoding: none');
    header('X-Accel-Buffering: no');

    // Инициализация буфера вывода
    ob_start();

    // Генерация ответа
    echo json_encode($buffer);

    // Запись заголовков ответа
    header('Content-Length: ' . ob_get_length());

    // Отправка и деинициализация буфера вывода
    ob_end_flush();
    flush();

    // Запись в буфер сессии
    if (!in_array('account', $return, true) && isset($parameters['remember']) && $parameters['remember'] === '1')
      $this->session->write(['entry' => ['invite' => $parameters['invite']]]);
  }
}
