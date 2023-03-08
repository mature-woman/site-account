<?php

declare(strict_types=1);

namespace mirzaev\site\account\controllers;

// Файлы проекта
use mirzaev\site\account\controllers\core,
  mirzaev\site\account\controllers\traits\errors,
  mirzaev\site\account\models\generators\password;

// Встроенные библиотеки
use exception;

/**
 * Контроллер API
 *
 * @package mirzaev\site\account\controllers
 * @author Arsen Mirzaev Tatyano-Muradovich <arsen@mirzaev.sexy>
 */
final class api extends core
{
  use errors;

  /**
   * Сгенерировать пароль
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

    // Инициализация значений по умолчению
    $parameters['length'] ??= 6;
    $parameters['type'] ??= 'classic';

    try {
      // Проверка параметров на соответствие требованиям
      if (($parameters['length'] = (int) $parameters['length']) === 0) throw new exception('Минимальная длина генерируемого пароля: 1 символ');
      if ($parameters['type'] !== 'classic' && $parameters['type'] !== 'mnemonic') throw new exception('Допустимые типы пароля: "mnemonic", "classic"');

      // Генерация ответа по запрашиваемым параметрам
      foreach ($return as $parameter) match ($parameter) {
        'password' => $buffer['password'] = password::{$parameters['type'] ?? 'classic'}($parameters['length'], $this->errors),
        'errors' => null,
        default => throw new exception("Параметр не найден: $parameter")
      };
    } catch (exception $e) {
      // Запись в реестр ошибок
      $this->errors[] = [
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
