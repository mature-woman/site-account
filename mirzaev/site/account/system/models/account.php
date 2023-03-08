<?php

declare(strict_types=1);

namespace mirzaev\site\account\models;

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
   * Инстанция документа аккаунта в базе данных
   */
  public ?_document $document;

  /**
   * Конструктор
   *
   * 1. Проверяет связь сессии с аккаунтом
   * 1.1. Если найдена связь, то возвращает связанный аккаунт (выход)
   * 2. [authenticate === true] Проверяет наличие данных в буфере сессии
   * 2.1 Если найден входной псевдоним и пароли совпадают, то аутентифицирует (выход)
   * 2.2 [register === true] Если найдены данные для регистрации, то регистрирует (выход)
   *
   * @param ?session $session Инстанция сессии
   * @param bool $authenticate Аутентифицировать аккаунт?
   * @param bool $register Регистрировать аккаунт?
   * @param array &$errors Реестр ошибок
   *
   * @return static Инстанция аккаунта
   */
  public function __construct(?session $session = null, bool $authenticate = false, bool $register = false, array &$errors = [])
  {
    try {
      if (isset($session)) {
        // Получена инстанция сессии

        if ($account = $session->account()) {
          // Найден связанный с сессией аккаунт

          // Инициализация инстанции документа аккаунта в базе данных
          $this->document = $account->document;

          // Связь сессии с аккаунтом
          $session->connect($this, $errors);

          return $this;
        } else {
          // Не найден связанный с сессией аккаунт

          if ($authenticate) {
            // Запрошена аутентификация

            if (!empty($session->buffer['entry'])) {
              // Найдены данные для идентификации в буфере сессии

              if (!empty($session->buffer['entry']['login'])) {
                // Найдены входной псевдоним в буфере сессии

                if (($account = self::login($session->buffer['entry']['login'])) instanceof self) {
                  // Найден аккаунт (игнорируются ошибки)

                  if (isset($account->password) && $account->password === '') {
                    // Не имеет пароля аккаунт 

                    // Проверка отсутствия переданного пароля
                    if (isset($session->buffer['entry']['password']) && $session->buffer['entry']['password'] !== '') throw new exception('Неправильный пароль');

                    // Инициализация инстанции документа аккаунта в базе данных
                    $this->document = $account->document;

                    // Связь сессии с аккаунтом
                    $session->connect($this, $errors);

                    // Удаление использованных данных из буфера сессии
                    $session->write(['entry' => ['password' => null]]);

                    return $this;
                  } else if (!empty($session->buffer['entry']['password'])) {
                    // Найден пароль в буфере сессии

                    if (sodium_crypto_pwhash_str_verify($account->password, $session->buffer['entry']['password'])) {
                      // Аутентифицирован аккаунт (прошёл проверку пароль, либо аккаунт не имеет пароля)

                      // Инициализация инстанции документа аккаунта в базе данных
                      $this->document = $account->document;

                      // Связь сессии с аккаунтом
                      $session->connect($this, $errors);

                      // Удаление использованных данных из буфера сессии
                      $session->write(['entry' => ['password' => null]]);

                      return $this;
                    } else throw new exception('Неправильный пароль');
                  } throw new exception('Неправильный пароль');
                } else {
                  // Не найден аккаунт

                  if ($register) {
                    // Запрошена регистрация

                    if (!empty($session->buffer['entry']['invite'])) {
                      // Найден ключ приглашения в буфере сессии

                      // Проверка наличия переданного пароля
                      if (!isset($session->buffer['entry']['password'])) throw new exception('Не найден пароль в буфере сессии');

                      if (self::create(
                        [
                          'login' => $session->buffer['entry']['login'],
                          'password' => $session->buffer['entry']['password'] === ''
                            ? ''
                            : sodium_crypto_pwhash_str(
                              $session->buffer['entry']['password'],
                              SODIUM_CRYPTO_PWHASH_OPSLIMIT_SENSITIVE,
                              SODIUM_CRYPTO_PWHASH_MEMLIMIT_SENSITIVE
                            )
                        ],
                        $errors
                      )) {
                        // Зарегистрирован аккаунт

                        if (($account = self::login($session->buffer['entry']['login'], $errors)) instanceof self) {
                          // Найден аккаунт

                          // Инициализация инстанции документа аккаунта в базе данных
                          $this->document = $account->document;

                          // Связь сессии с аккаунтом
                          $session->connect($this, $errors);

                          // Удаление использованных данных из буфера сессии
                          $session->write(['entry' => ['password' => null, 'invite' => null]]);

                          return $this;
                        } else throw new exception('Не удалось аутентифицировать аккаунт после его регистрации');
                      } else throw new exception('Не удалось зарегистрировать аккаунт');
                    } else throw new exception('Не найден ключ приглашения в буфере сессии');
                  }
                }
              } else throw new exception('Не найден входной псевдоним в буфере сессии');
            } else throw new exception('Не найдены данные для идентификации');
          }
        }
      }
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


  /**
   * Найти по входному псевдониму
   *
   * @param string $login Входной псевдоним
   * @param array &$errors Реестр ошибок
   *
   * @return ?self Инстанция аккаунта, если аутентифицирован
   */
  public static function login(string $login, array &$errors = []): ?self
  {
    try {
      if (collection::init(static::$db->session, self::COLLECTION)) {
        // Инициализирована коллекция

        // Инициализация инстанции аккаунта
        $instance = new self;

        // Поиск инстанции аккаунта в базе данных
        $instance->document = collection::search(
          static::$db->session,
          sprintf(
            <<<'AQL'
              FOR d IN %s
                FILTER d.login == '%s'
                RETURN d
            AQL,
            self::COLLECTION,
            $login
          )
        );

        if ($instance->document instanceof _document) return $instance;
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
   * Создать
   *
   * @param array $data Данные аккаунта
   * @param array &$errors Реестр ошибок
   *
   * @return bool Создан аккаунт?
   */
  public static function create(array $data = [], array &$errors = []): bool
  {
    try {
      if (collection::init(static::$db->session, self::COLLECTION))
        if (document::write(static::$db->session, self::COLLECTION, $data)) return true;
        else throw new exception('Не удалось создать аккаунт');
      else throw new exception('Не удалось инициализировать коллекцию');
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
   * Записывает свойство в инстанцию документа аккаунта из базы данных
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
   * Читает свойство из инстанции документа аккаунта из базы данных
   *
   * @param string $name Название
   *
   * @return mixed Данные свойства инстанции аккаунта или инстанции документа аккаунта из базы данных
   */
  public function __get(string $name): mixed
  {
    return $this->document->{$name};
  }

  /**
   * Проверить инициализированность
   *
   * Проверяет инициализированность свойства в инстанции документа аккаунта из базы данных
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
   * Деинициализировать свойство в инстанции документа аккаунта из базы данных
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
   * Выполнить метод в инстанции документа аккаунта из базы данных
   *
   * @param string $name Название
   * @param array $arguments Аргументы
   */
  public function __call(string $name, array $arguments = [])
  {
    if (method_exists($this->document, $name)) return $this->document->{$name}($arguments);
  }
}
