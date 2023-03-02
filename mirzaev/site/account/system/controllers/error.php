<?php

declare(strict_types=1);

namespace mirzaev\site\account\controllers;

// Файлы проекта
use mirzaev\site\account\controllers\core;

/**
 * Контроллер ошибок
 *
 * @package mirzaev\site\account\controllers
 * @author Arsen Mirzaev Tatyano-Muradovich <arsen@mirzaev.sexy>
 */
final class error extends core
{
    /**
     * Страница с ошибкой
     *
     * @param array $parameters
     */
    public function index(array $parameters = []): ?string
    {
        // Запись текста ошибки в переменную окружения
        $this->view->text = $parameters['text'] ?? null;

        if (isset($parameters['code'])) {
            // Получен код ошибки

            // Запись кода ошибки в переменную окружения
            $this->view->code = $parameters['code'];

            // Запись кода ответа
            http_response_code($parameters['code']);

            // Генерация представления
            return $this->view->render(DIRECTORY_SEPARATOR . 'errors' . DIRECTORY_SEPARATOR . 'index.html');
        }

        // Генерация представления
        return $this->view->render(DIRECTORY_SEPARATOR . 'errors' . DIRECTORY_SEPARATOR . ($parameters['code'] ?? 'index') . '.html');
    }
}
