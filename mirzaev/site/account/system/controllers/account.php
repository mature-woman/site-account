<?php

declare(strict_types=1);

namespace mirzaev\site\account\controllers;

// Файлы проекта
use mirzaev\site\account\controllers\core,
  mirzaev\site\account\models\account as model,
  mirzaev\site\account\models\session,
  mirzaev\site\account\models\vk;

// Фреймворк для ВКонтакте
use mirzaev\vk\core as api;

// Библиотека для ArangoDB
use ArangoDBClient\Document as _document;

// Встроенные библиотеки
use stdClass;

/**
 * Контроллер аккаунта
 *
 * @package mirzaev\site\account\controllers
 * @author Arsen Mirzaev Tatyano-Muradovich <arsen@mirzaev.sexy>
 */
final class account extends core
{
  /**
   * Страница профиля
   *
   * @param array $parameters Параметры запроса
   */
  public function index(array $parameters = []): ?string
  {
    return null;
  }

  /**
   * Инициализация
   *
   * @param array $parameters Параметры запроса
   */
  public function initialization(array $parameters = []): ?string
  {
    if ($this->variables['account'] instanceof _document) {
      // Найден аккаунт

      if ($this->variables['vk'] instanceof _document) {
        // Найден аккаунт ВКонтакте

        // Инициализация данных аккаунта ВКонтакте
        vk::parse($this->variables['vk'], $this->variables['errors']['vk']);
      }

      // Запись кода ответа
      http_response_code(200);

      return null;
    } else {
      // Не найден аккаунт

      // Запись кода ответа
      http_response_code(401);

      // Запись заголовка ответа с ключом аккаунта
      header('session: ' . $this->variables['session']->hash);

      return null;
    }

    // Запись кода ответа
    http_response_code(500);

    return null;
  }

  /**
   * Связь аккаунта с аккаунтом ВКонтакте
   *
   * @param array $parameters Параметры запроса
   */
  public function connect(array $parameters = []): ?string
  {
    if ($this->variables['session']->hash === $parameters['state']) {
      // Совпадает хеш сессии с полученным хешем из ответа ВКонтакте

      if (!empty($response = vk::key($parameters['code'], $this->variables['errors']['vk']))) {
        // Получены данные аккаунта ВКонтакте

        if (($this->variables['vk'] = vk::initialization($response, $this->variables['errors']['vk'])) instanceof _document) {
          // Инициализирован аккаунт ВКонтакте

          if (($this->variables['account'] = vk::account($this->variables['vk'])) instanceof _document) {
            // Найден аккаунт (существующий)

            if (session::connect($this->variables['session'], $this->variables['account'], $this->variables['errors']['session'])) {
              // Связана сессия с аккаунтом
            }
          } else if (($this->variables['account'] = model::create($this->variables['errors']['account'])) instanceof _document) {
            // Найден аккаунт (создан новый)

            if (session::connect($this->variables['session'], $this->variables['account'], $this->variables['errors']['session'])) {
              // Связана сессия с аккаунтом

              if (account::connect($this->variables['account'], $this->variables['vk'], $this->variables['errors']['account'])) {
                // Связан аккаунт с аккаунтом ВКонтакте
              }
            }
          }

          // Инициализация робота для аккаунта ВКонтакте
          $this->vk = api::init()->user(key: $this->variables['vk']->access['key']);

          if ($this->variables['vk'] instanceof _document) {
            // Инициализирован робот для аккаунта ВКонтакте

            // Инициализация данных аккаунта ВКонтакте
            $data = vk::parse($this->vk, $this->variables['errors']['vk']);
            var_dump($data);
            die;

            if ($data instanceof stdClass) {
              // Получены данные ВКонтакте

              // Запись в базу данных
              vk::update($this->variables['vk'], $data, $this->variables['errors']['vk']);
            }
          }
        }
      }
    }

    // Генерация представления
    return $this->view->render(DIRECTORY_SEPARATOR . 'account' . DIRECTORY_SEPARATOR . 'vk.html', $this->variables);
  }

  /**
   * Генерация панели аккаунта
   *
   * @param array $parameters Параметры запроса
   */
  public function panel(array $parameters = []): ?string
  {
    // Генерация представления
    return $this->view->render(DIRECTORY_SEPARATOR . 'account' . DIRECTORY_SEPARATOR . 'panel.html', $this->variables);
  }
}
