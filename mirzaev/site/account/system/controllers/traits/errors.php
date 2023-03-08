<?php

declare(strict_types=1);

namespace mirzaev\site\account\controllers\traits;

/**
 * Заготовка для обработки ошибок
 *
 * @package mirzaev\site\account\controllers\traits
 * @author Arsen Mirzaev Tatyano-Muradovich <arsen@mirzaev.sexy>
 */
trait errors
{
  private static function parse_only_text(array $errors): array
  {
    // Инициализация буфера вывода
    $buffer = [];

    foreach ($errors as $offset => $error) {
      // Перебор ошибок

      // Проверка на вложенность и запись в буфер вывода (вход в рекурсию)
      if (isset($error['text'])) $buffer[] = $error['text'];
      else if (is_array($error) && count($error) > 0) $buffer[$offset] = static::parse_only_text($error);
    }

    return $buffer;
  }
}
