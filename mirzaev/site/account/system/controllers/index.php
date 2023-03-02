<?php

declare(strict_types=1);

namespace mirzaev\site\account\controllers;

// Файлы проекта
use mirzaev\site\account\controllers\core;

/**
 * Контроллер основной страницы
 *
 * @package mirzaev\site\account\controllers
 * @author Arsen Mirzaev Tatyano-Muradovich <arsen@mirzaev.sexy>
 */
final class index extends core
{
  /**
   * Главная страница
   *
   * @param array $parameters Параметры запроса
   */
  public function index(array $parameters = []): ?string
  {
    // Инициализация узлов
    $this->view->nodes = [
      'account' => $this->view->render(DIRECTORY_SEPARATOR . 'nodes' . DIRECTORY_SEPARATOR . (isset($this->account) ? 'profile.html' : 'authentication.html'))
      /* 'account' => $this->view->render(DIRECTORY_SEPARATOR . 'nodes' . DIRECTORY_SEPARATOR . (isset($this->account) ? 'profile.html' : 'connect.html')) */
    ];

    // Генерация представления
    return $this->view->render(DIRECTORY_SEPARATOR . 'index.html');
  }
}
