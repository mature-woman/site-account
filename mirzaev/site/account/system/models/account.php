<?php

declare(strict_types=1);

namespace mirzaev\site\account\models;

// Файлы проекта
use mirzaev\site\account\models\vk;

// Фреймворк ArangoDB
use mirzaev\arangodb\collection,
  mirzaev\arangodb\document;

// Библиотека для ArangoDB
use ArangoDBClient\Document as _document;

// Встроенные библиотеки
use exception;

/**
 * Модель аккаунта
 *
 * @package mirzaev\site\account\models
 * @author Arsen Mirzaev Tatyano-Muradovich <arsen@mirzaev.sexy>
 */
final class account extends core
{
  /**
   * Коллекция
   */
  public const COLLECTION = 'account';

  /**
   * Инстанция в базе данных
   */
  public ?_document $instance;

  /**
   * Прочитать
   *
   * @param string $login Входной псевдоним
   * @param array &$errors Журнал ошибок
   *
   * @return ?self Инстанция аккаунта, если найден
   */
  public static function read(string $login, array &$errors = []): ?self
  {
    try {
      if (collection::init(static::$db->session, self::COLLECTION)) {
        // Инициализирована коллекция

        // Инициализация инстанции аккаунта
        $instance = new self;

        // Поиск аккаунта
        $instance->instance = collection::search(
          static::$db->session,
          sprintf(
            <<<AQL
              FOR d IN %s
              FILTER d.login == '%s'
              RETURN d
            AQL,
            self::COLLECTION,
            $login
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

  /**
   * Создать
   *
   * @param array &$errors Журнал ошибок
   *
   * @return ?_document Инстанция аккаунта, если удалось создать
   */
  public static function create(array &$errors = []): ?_document
  {
    try {
      if (collection::init(static::$db->session, self::COLLECTION)) {
        // Инициализирована коллекция

        // Запись аккаунта в базу данных
        $_id = document::write(static::$db->session, self::COLLECTION);

        if ($account = collection::search(static::$db->session, sprintf(
          <<<AQL
                            FOR d IN %s
                            FILTER d._id == '$_id'
                            RETURN d
                        AQL,
          self::COLLECTION
        ))) {
          // Найден созданный аккаунт

          return $account;
        } else throw new exception('Не удалось создать аккаунт');
      } else throw new exception('Не удалось инициализировать коллекцию');
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

  /**
   * Связь аккаунта с аккаунтом ВКонтакте
   *
   * @param _document $account Инстанция аккаунта
   * @param _document $vk Инстанция аккаунта ВКонтакте
   * @param array &$errors Журнал ошибок
   *
   * @return bool Статус выполнения
   */
  public static function connect(_document $account, _document $vk, array &$errors = []): bool
  {
    try {
      if (
        collection::init(static::$db->session, self::COLLECTION)
        && collection::init(static::$db->session, vk::COLLECTION)
        && collection::init(static::$db->session, self::COLLECTION . '_edge_' . vk::COLLECTION, true)
      ) {
        // Инициализированы коллекции

        if (document::write(static::$db->session, self::COLLECTION . '_edge_' . vk::COLLECTION, [
          '_from' => $account->getId(),
          '_to' => $vk->getId()
        ])) {
          // Создано ребро: account -> vk

          return true;
        } else throw new exception('Не удалось создать ребро: account -> vk');
      } else throw new exception('Не удалось инициализировать коллекцию');
    } catch (exception $e) {
      // Запись в журнал ошибок
      $errors[] = [
        'text' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine(),
        'stack' => $e->getTrace()
      ];
    }

    return false;
  }

  /**
   * Поиск связанного аккаунта ВКонтакте
   *
   * @param _document $account Инстанция аккаунта
   * @param array &$errors Журнал ошибок
   *
   * @return ?_document Инстанция аккаунта, если удалось найти
   */
  public static function vk(_document $account, array &$errors = []): ?_document
  {
    try {
      if (
        collection::init(static::$db->session, self::COLLECTION)
        && collection::init(static::$db->session, vk::COLLECTION)
        && collection::init(static::$db->session, self::COLLECTION . '_edge_' . vk::COLLECTION, true)
      ) {
        // Инициализирована коллекция

        if ($vk = collection::search(static::$db->session, sprintf(
          <<<AQL
                        FOR document IN %s
                        LET edge = (
                            FOR edge IN %s
                            FILTER edge._from == '%s'
                            SORT edge._key DESC
                            LIMIT 1
                            RETURN edge
                        )
                        FILTER document._id == edge[0]._to
                        LIMIT 1
                        RETURN document
                    AQL,
          vk::COLLECTION,
          self::COLLECTION . '_edge_' . vk::COLLECTION,
          $account->getId()
        ))) {
          // Найден аккаунт ВКонтакте

          return $vk;
        } else throw new exception('Не удалось найти аккаунт ВКонтакте');
      } else throw new exception('Не удалось инициализировать коллекцию');
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
}
