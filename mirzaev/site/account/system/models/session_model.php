<?php

declare(strict_types=1);

namespace mirzaev\site\account\models;

// Файлы проекта
use mirzaev\site\account\models\account_model as account;

// Фреймворк ArangoDB
use mirzaev\arangodb\collection,
  mirzaev\arangodb\document;

// Библиотека для ArangoDB
use ArangoDBClient\Document as _document;

// Встроенные библиотеки
use exception;

/**
 * Модель сессий
 *
 * @package mirzaev\site\account\models
 * @author Arsen Mirzaev Tatyano-Muradovich <arsen@mirzaev.sexy>
 */
final class session_model extends core
{
  /**
   * Коллекция
   */
  public const COLLECTION = 'session';

  /**
   * Данные сессии из базы данных 
   */
  public _document $document;

  /**
   * Конструктор 
   *
   * Инициализация сессии и запись в свойство $this->document
   *
   * @param ?string $hash Хеш сессии в базе данных
   * @param ?int $expires Дата окончания работы сессии (используется при создании новой сессии)
   * @param array &$errors Журнал ошибок
   *
   * @return static Инстанция сессии
   */
  public function __construct(?string $hash = null, ?int $expires = null, array &$errors = [])
  {
    try {
      if (collection::init(static::$db->session, self::COLLECTION)) {
        // Инициализирована коллекция

        if (isset($hash) && $session = collection::search(static::$db->session, sprintf(
          <<<AQL
            FOR d IN %s
            FILTER d.hash == '$hash' && d.expires > %d
            RETURN d
          AQL,
          self::COLLECTION,
          time()
        ))) {
          // Найдена сессия по хешу

          // Запись в свойство
          $this->document = $session;
        } else if ($session = collection::search(static::$db->session, sprintf(
          <<<AQL
            FOR d IN %s
            FILTER d.ip == '%s' && d.expires > %d
            RETURN d
          AQL,
          self::COLLECTION,
          $_SERVER['REMOTE_ADDR'],
          time()
        ))) {
          // Найдена сессия по данным пользователя

          // Запись в свойство
          $this->document = $session;
        } else {
          // Не найдена сессия

          // Запись сессии в базу данных
          $_id = document::write(static::$db->session, self::COLLECTION, [
            'ip' => $_SERVER['REMOTE_ADDR'],
            'expires' => $expires ?? time() + 604800
          ]);

          if ($session = collection::search(static::$db->session, sprintf(
            <<<AQL
              FOR d IN %s
              FILTER d._id == '$_id' && d.expires > %d
              RETURN d
            AQL,
            self::COLLECTION,
            time()
          ))) {
            // Найдена созданная сессия

            // Запись хеша
            $session->hash = sodium_bin2hex(sodium_crypto_generichash($_id));

            if (document::update(static::$db->session, $session)) {
              // Записано обновление

              // Запись в свойство
              $this->document = $session;
            } else throw new exception('Не удалось записать данные сессии');
          } else throw new exception('Не удалось создать или найти созданную сессию');
        }
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
  }

  public function __destruct() {
    // Закрыть сессию
  }

  /**
   * Связь сессии с аккаунтом
   *
   * @param _document $account Инстанция аккаунта
   * @param array &$errors Журнал ошибок
   *
   * @return bool Статус выполнения
   */
  public function connect(_document $account, array &$errors = []): bool
  {
    try {
      if (
        collection::init(static::$db->session, self::COLLECTION)
        && collection::init(static::$db->session, account::COLLECTION)
        && collection::init(static::$db->session, self::COLLECTION . '_edge_' . account::COLLECTION, true)
      ) {
        // Инициализирована коллекция

        if (document::write(static::$db->session, self::COLLECTION . '_edge_' . account::COLLECTION, [
          '_from' => $this->document->getId(),
          '_to' => $account->getId()
        ])) {
          // Создано ребро: session -> account

          return true;
        } else throw new exception('Не удалось создать ребро: session -> account');
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
   * Поиск связанного аккаунта
   *
   * @param array &$errors Журнал ошибок
   *
   * @return ?_document Инстанция аккаунта, если удалось найти
   */
  public function account(array &$errors = []): ?_document
  {
    try {
      if (
        collection::init(static::$db->session, self::COLLECTION)
        && collection::init(static::$db->session, account::COLLECTION)
        && collection::init(static::$db->session, self::COLLECTION . '_edge_' . account::COLLECTION, true)
      ) {
        // Инициализированы коллекции

        if ($account = collection::search(static::$db->session, sprintf(
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
          account::COLLECTION,
          self::COLLECTION . '_edge_' . account::COLLECTION,
          $this->document->getId()
        ))) {
          // Найден аккаунт

          return $account;
        } else throw new exception('Не удалось найти аккаунт');
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
   * Записать
   *
   * Записывает свойство в инстанцию документа сессии из базы данных
   *
   * @param string $name Название
   * @param mixed $value Содержимое
   *
   * @return void
   */
  public function __set(string $name, mixed $value = null): void
  {
   $this->document->{$name} = $value;
  }

  /**
   * Прочитать
   *
   * Читает свойство из инстанции документа сессии из базы данных
   *
   * @param string $name Название
   *
   * @return mixed Данные свойства инстанции сессии или инстанции документа сессии из базы данных
   */
  public function __get(string $name): mixed
  {
    return $this->document->{$name};
  }

  /**
   * Проверить инициализированность
   *
   * Проверяет инициализированность свойства в инстанции документа сессии из базы данных
   *
   * @param string $name Название
   *
   * @return bool Свойство инициализировано?
   */
  public function __isset(string $name): bool
  {
    return isset($this->document->{$name});
  }

  /**
   * Удалить
   *
   * Деинициализировать свойство в инстанции документа сессии из базы данных
   *
   * @param string $name Название
   *
   * @return void
   */
  public function __unset(string $name): void
  {
    unset($this->document->{$name});
  }

  /**
   * Выполнить метод
   *
   * Выполнить метод в инстанции документа сессии из базы данных
   *
   * @param string $name Название
   * @param array $arguments Аргументы
   *
   * @return void
   */
  public function __call(string $name, array $arguments = []): mixed
  {
    return $this->document->{$name}($arguments);
  }
}
