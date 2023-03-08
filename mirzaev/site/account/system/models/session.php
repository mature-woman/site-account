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
 * Модель сессий
 *
 * @package mirzaev\site\account\models
 * @author Arsen Mirzaev Tatyano-Muradovich <arsen@mirzaev.sexy>
 */
final class session extends core
{
  /**
   * Коллекция
   */
  public const COLLECTION = 'session';

  /**
   * Инстанция документа сессии в базе данных 
   */
  public _document $document;

  /**
   * Конструктор 
   *
   * Инициализация сессии и запись в свойство $this->document
   *
   * @param ?string $hash Хеш сессии в базе данных
   * @param ?int $expires Дата окончания работы сессии (используется при создании новой сессии)
   * @param array &$errors Реестр ошибок
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
              FILTER d.hash == '$hash' && d.expires > %d && d.status == 'active'
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
              FILTER d.ip == '%s' && d.expires > %d && d.status == 'active'
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
            'status' => 'active',
            'expires' => $expires ?? time() + 604800,
            'ip' => $_SERVER['REMOTE_ADDR']
          ]);

          if ($session = collection::search(static::$db->session, sprintf(
            <<<AQL
              FOR d IN %s
                FILTER d._id == '$_id' && d.expires > %d && d.status == 'active'
                RETURN d
            AQL,
            self::COLLECTION,
            time()
          ))) {
            // Найдена только что созданная сессия

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
      // Запись в реестр ошибок
      $errors[] = [
        'text' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine(),
        'stack' => $e->getTrace()
      ];
    }
  }

  public function __destruct()
  {
    // Закрыть сессию
  }

  /**
   * Инициализировать связб сессии с аккаунтом
   *
   * Ищет связь сессии с аккаунтом, если не находит, то создаёт её
   *
   * @param account $account Инстанция аккаунта
   * @param array &$errors Реестр ошибок
   *
   * @return bool Связан аккаунт?
   */
  public function connect(account $account, array &$errors = []): bool
  {
    try {
      if (
        collection::init(static::$db->session, self::COLLECTION)
        && collection::init(static::$db->session, account::COLLECTION)
        && collection::init(static::$db->session, self::COLLECTION . '_edge_' . account::COLLECTION, true)
      ) {
        // Инициализирована коллекция

        if (
          collection::search(static::$db->session, sprintf(
            <<<AQL
              FOR document IN %s
                FILTER document._from == '%s' && document._to == '%s'
                LIMIT 1
                RETURN document
            AQL,
            self::COLLECTION . '_edge_' . account::COLLECTION,
            $this->document->getId(),
            $account->getId()
          )) instanceof _document
          || document::write(static::$db->session, self::COLLECTION . '_edge_' . account::COLLECTION, [
            '_from' => $this->document->getId(),
            '_to' => $account->getId()
          ])
        ) {
          // Найдено, либо создано ребро: session -> account

          return true;
        } else throw new exception('Не удалось создать ребро: session -> account');
      } else throw new exception('Не удалось инициализировать коллекцию');
    } catch (exception $e) {
      // Запись в реестр ошибок
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
   * Найти связанный аккаунт
   *
   * @param array &$errors Реестр ошибок
   *
   * @return ?account Инстанция аккаунта, если удалось найти
   */
  public function account(array &$errors = []): ?account
  {
    try {
      if (
        collection::init(static::$db->session, self::COLLECTION)
        && collection::init(static::$db->session, account::COLLECTION)
        && collection::init(static::$db->session, self::COLLECTION . '_edge_' . account::COLLECTION, true)
      ) {
        // Инициализированы коллекции

        // Инициализация инстанции аккаунта
        $account = new account;

        // Поиск инстанции аккаунта в базе данных
        $account->document = collection::search(static::$db->session, sprintf(
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
          $this->getId()
        ));

        if ($account->document instanceof _document) return $account;
        else throw new exception('Не удалось найти инстанцию аккаунта в базе данных');
      } else throw new exception('Не удалось инициализировать коллекцию');
    } catch (exception $e) {
      // Запись в реестр ошибок
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
   * Записать в буфер сессии
   *
   * @param array $data Данные для записи
   * @param array &$errors Реестр ошибок
   *
   * @return bool Записаны данные в буфер сессии?
   */
  public function write(array $data, array &$errors = []): bool
  {
    try {
      if (collection::init(static::$db->session, self::COLLECTION)) {
        // Инициализирована коллекция

        // Проверка инициализированности инстанции документа из базы данных
        if (!isset($this->document)) throw new exception('Не инициализирована инстанция документа из базы данных');

        // Запись параметров в инстанцию документа из базы данных
        $this->document->buffer = array_replace_recursive($this->document->buffer ?? [], $data);

        if (document::update(static::$db->session, $this->document)) {
          // Записано обновление

          return true;
        }

        throw new exception('Не удалось записать данные в буфер сессии');
      } else throw new exception('Не удалось инициализировать коллекцию');
    } catch (exception $e) {
      // Запись в реестр ошибок
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
   */
  public function __call(string $name, array $arguments = [])
  {
    if (method_exists($this->document, $name)) return $this->document->{$name}($arguments);
  }
}
