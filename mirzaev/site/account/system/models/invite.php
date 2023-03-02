<?php

declare(strict_types=1);

namespace mirzaev\site\account\models;

// Файлы проекта
use mirzaev\site\account\models\account;

// Фреймворк ArangoDB
use mirzaev\arangodb\collection,
  mirzaev\arangodb\document;

// Библиотека для ArangoDB
use ArangoDBClient\Document as _document;

// Встроенные библиотеки
use exception;

/**
 * Модель приглашения
 *
 * @package mirzaev\site\account\models
 * @author Arsen Mirzaev Tatyano-Muradovich <arsen@mirzaev.sexy>
 */
final class invite extends core
{
  /**
   * Коллекция
   */
  public const COLLECTION = 'invite';

  /**
   * Инстанция в базе данных
   */
  public ?_document $instance;

  /**
   * Прочитать
   *
   * @param string $invite Ключ приглашения
   * @param array &$errors Журнал ошибок
   *
   * @return ?self Инстанция приглашения, если оно найдено
   */
  public static function read(string $invite, array &$errors = []): ?self
  {
    try {
      if (collection::init(static::$db->session, self::COLLECTION)) {
        // Инициализирована коллекция

        // Инициализация инстанции приглашения
        $instance = new self;

        // Поиск приглашения
        $instance->instance = collection::search(
          static::$db->session,
          sprintf(
            <<<AQL
              FOR d IN %s
              FILTER d.key == '%s' && d.active == true
              RETURN d
            AQL,
            self::COLLECTION,
            $invite
          )
        );

        return $instance;
      }

      throw new exception('Не удалось инициализировать коллекцию');
    } catch (exception $e) {
      // Запись в журнал ошибок
      $errors[] = [
        'text' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine(),
        'stack' => $e->getTrace()
      ];
    }

    return null;
  }

  public function from(): ?account
  {
    return new account();
  }
}
