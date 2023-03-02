<?php

declare(strict_types=1);

namespace mirzaev\site\account\views;

// Файлы проекта
use mirzaev\minimal\controller;

// Шаблонизатор представлений
use Twig\Loader\FilesystemLoader,
  Twig\Environment as twig;

// Встроенные библиотеки
use ArrayAccess;

/**
 * Шаблонизатор представлений
 *
 * @package mirzaev\site\account\controllers
 * @author Arsen Mirzaev Tatyano-Muradovich <arsen@mirzaev.sexy>
 */
final class templater extends controller implements ArrayAccess
{
  /**
   * Реестр глобальных переменных
   */
  public array $variables = [];

  /**
   * Инстанция окружения twig 
   */
  public twig $twig;

  /**
   * Конструктор
   *
   * @return void
   */
  public function __construct()
  {
    // Инициализация шаблонизатора
    $this->twig = new twig(new FilesystemLoader(VIEWS));

    // Инициализация глобальных переменных
    $this->twig->addGlobal('cookie', $_COOKIE);
  }

  /**
   * Отрисовка HTML-документа
   *
   * @param string $file Относительный директории представлений путь до файла представления
   * @param ?array $variables Реестр переменных
   *
   * @return ?string HTML-документ
   */
  public function render(string $file, ?array $variables = null): ?string
  {
    // Генерация представления
    return $this->twig->render($file, $variables ?? $this->variables);
  }

  /**
   * Записать
   *
   * Записывает переменную в реестр глобальных переменных
   *
   * @param string $name Название
   * @param mixed $value Содержимое
   *
   * @return void
   */
  public function __set(string $name, mixed $value = null): void
  {
    $this->variables[$name] = $value;
  }

  /**
   * Прочитать
   *
   * Читает переменную из реестра глобальных переменных
   *
   * @param string $name Название 
   *
   * @return mixed Данные переменной из реестра глобальных переменных
   */
  public function __get(string $name): mixed
  {
    return $this->variables[$name];
  }

  /**
   * Проверить инициализированность
   *
   * Проверяет инициализированность переменной в буфере переменных представления
   *
   * @param string $name Название
   *
   * @return bool Переменная инициализирована?
   */
  public function __isset(string $name): bool
  {
    return isset($this->variables[$name]);
  }

  /**
   * Удалить
   *
   * Деинициализирует переменную в реестре глобальных переменных 
   *
   * @param string $name Название
   *
   * @return void
   */
  public function __unset(string $name): void
  {
    unset($this->variables[$name]);
  }

  /**
   * Записать
   *
   * Записывает переменную в реестр глобальных переменных
   *
   * @param mixed $offset Сдвиг, либо идентификатор 
   * @param mixed $value Содержимое
   *
   * @return void
   */
  public function offsetSet(mixed $offset, mixed $value): void
  {
    $this->variables[$offset] = $value;
  }

  /**
   * Прочитать
   *
   * Читает переменную из реестра глобальных переменных
   *
   * @param mixed $offset Сдвиг, либо идентификатор 
   *
   * @return mixed Данные переменной из реестра глобальных переменных
   */
  public function offsetGet(mixed $offset): mixed
  {
    return $this->variables[$offset];
  }

  /**
   * Проверить инициализированность
   *
   * Проверяет инициализированность переменной в реестре глобальных переменных 
   *
   * @param mixed $offset Сдвиг, либо идентификатор 
   *
   * @return bool Переменная инициализирована?
   */
  public function offsetExists(mixed $offset): bool
  {
    return isset($this->variables[$offset]);
  }

  /**
   * Удалить
   *
   * Деинициализирует переменную в реестре глобальных переменных 
   *
   * @param mixed $offset Сдвиг, либо идентификатор 
   *
   * @return void
   */
  public function offsetUnset(mixed $offset): void
  {
    unset($this->variables[$offset]);
  }
}
