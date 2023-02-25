<?php

declare(strict_types=1);

namespace mirzaev\site\account\controllers;

// Файлы проекта
use mirzaev\site\account\controllers\core;

/**
 * Контроллер графика
 *
 * @package mirzaev\site\account\controllers
 * @author Arsen Mirzaev Tatyano-Muradovich <arsen@mirzaev.sexy>
 */
final class graph_controller extends core
{
    /**
     * Страница с графиком
     *
     * Можно использовать совместно с элементом <iframe> для изоляции
     * содержимого бегущей строки от поисковых роботов
     *
     * @param array $parameters
     */
    public function index(array $parameters = []): ?string
    {
        // Инициализация элементов для генерации в головном элементе
        $this->variables['head'] = [
            'title' => 'Бегущая строка',
            'metas' => [
                [
                    'attributes' => [
                        'name' => 'robots',
                        'content' => 'nofollow'
                    ]
                ]
            ]
        ];

        // Инициализация бегущей строки
        $this->variables['graph'] = [
            'id' => $this->variables['request']['id'] ?? 'graph'
        ];

        // Инициализация аттрибутов бегущей строки
        $this->variables['graph']['attributes'] = [

        ];

        // Инициализация элементов бегущей строки
        $this->variables['graph']['elements'] = [
        ];

        // Генерация представления
        return $this->view->render(DIRECTORY_SEPARATOR . 'graph' . DIRECTORY_SEPARATOR . 'index.html', $this->variables);
    }

}
