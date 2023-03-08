<?php

declare(strict_types=1);

namespace mirzaev\site\account\controllers;

// Файлы проекта
use mirzaev\site\account\controllers\core,
  mirzaev\site\account\models\account as model;

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
}
