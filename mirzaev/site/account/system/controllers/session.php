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
   * Записать входной псевдоним 
   *
   * Проверяет существование аккаунта с этим входным псевдонимом
   * и запоминает для использования в процессе аутентификации
   *
   * @param array $parameters Параметры запроса
   * 
   * @return string JSON-документ с запрашиваемыми параметрами
   */
  public function login(array $parameters = []): string
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

      // Поиск аккаунта
      $account = account::read($parameters['login'], $this->errors['account']);

      // Генерация ответа по запрашиваемым параметрам
      foreach ($return as $parameter) match ($parameter) {
        'exist' => $buffer['exist'] = isset($account->instance),
        'errors' => null,
        default => throw new exception("Параметр не найден: $parameter")
      };

      if ($parameters['remember'] === '1') $this->session->remember('account.identification.login', $parameters['login']);
    } catch (exception $e) {
      // Запись в журнал ошибок
      $this->errors['session'][] = [
        'text' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine(),
        'stack' => $e->getTrace()
      ];
    }

    // Запись реестра ошибок в буфер ответа
    if (in_array('errors', $return, true)) $buffer['errors'] = self::parse_only_text($this->errors);

    // Запись заголовка ответа
    header('Content-Type: application/json');

    return json_encode($buffer);
  }

  /**
   * Записать пароль
   *
   * Проверяет на соответствие требованиям 
   * и запоминает для использования в процессе аутентификации
   *
   * @param array $parameters Параметры запроса
   * 
   * @return string JSON-документ с запрашиваемыми параметрами
   */
  public function password(array $parameters = []): string
  {
    // Инициализация буфера ответа
    $buffer = [];

    // Инициализация реестра возвращаемых параметров
    $return = explode(',', $parameters['return'], 50);

    try {
      // Проверка наличия обязательных параметров
      if (empty($parameters['password'])) throw new exception('Необходимо передать пароль');

      // Вычисление длины
      $length = strlen($parameters['password']);

      // Проверка параметров на соответствование требованиям
      if ($length === 0) throw new exception('Пароль не может быть пустым');
      if ($length > 300) throw new exception('Пароль не может быть длиннее 300 символов');

      // Генерация ответа по запрашиваемым параметрам
      foreach ($return as $parameter) match ($parameter) {
        'verify' => $buffer['verify'] = true,
        'errors' => null,
        default => throw new exception("Параметр не найден: $parameter")
      };

      if ($parameters['remember'] === '1') throw new exception('Запоминать пароль не безопасно');
    } catch (exception $e) {
      // Запись в журнал ошибок
      $this->errors['session'][] = [
        'text' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine(),
        'stack' => $e->getTrace()
      ];

      // Запись реестра ошибок в буфер ответа
      if (in_array('verify', $return, true)) $buffer['verify'] = false;
    }

    // Запись реестра ошибок в буфер ответа
    if (in_array('errors', $return, true)) $buffer['errors'] = self::parse_only_text($this->errors);

    // Запись заголовка ответа
    header('Content-Type: application/json');

    return json_encode($buffer);
  }

  /**
   * Записать код приглашения
   *
   * Проверяет существование приглашения с этим кодом
   * и запоминает для использования в процессе регистрации
   *
   * @param array $parameters Параметры запроса
   * 
   * @return string JSON-документ с запрашиваемыми параметрами
   */
  public function invite(array $parameters = []): string
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

      // Поиск приглашения
      $invite = invite::read($parameters['invite'], $this->errors['session']);

      // Генерация ответа по запрашиваемым параметрам
      foreach ($return as $parameter) match ($parameter) {
        'exist' => $buffer['exist'] = isset($invite->instance),
        // from временное решение пока не будет разработана система сессий
        'from' => $return['from'] = ['login' => 'mirzaev'] ?? $invite->from(),
        'errors' => null,
        default => throw new exception("Параметр не найден: $parameter")
      };

      if ($parameters['remember'] === '1') $this->session->remember('account.registration.invite', $parameters['invite']);
    } catch (exception $e) {
      // Запись в журнал ошибок
      $this->errors['session'][] = [
        'text' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine(),
        'stack' => $e->getTrace()
      ];
    }

    // Запись реестра ошибок в буфер ответа
    if (in_array('errors', $return, true)) $buffer['errors'] = self::parse_only_text($this->errors);

    // Запись заголовка ответа
    header('Content-Type: application/json');
  
    return json_encode($buffer);
  }
}
