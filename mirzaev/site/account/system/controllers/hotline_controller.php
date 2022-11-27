<?php

declare(strict_types=1);

namespace mirzaev\site\account\controllers;

// Файлы проекта
use mirzaev\site\account\controllers\core;

/**
 * Контроллер бегущей строки
 *
 * @package mirzaev\site\account\controllers
 * @author Arsen Mirzaev Tatyano-Muradovich <arsen@mirzaev.sexy>
 */
final class hotline_controller extends core
{
    /**
     * Страница с бегущей строкой
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
        $this->variables['hotline'] = [
            'id' => $this->variables['request']['id'] ?? 'hotline'
        ];

        // Инициализация параметров бегущей строки
        $this->variables['hotline']['parameters'] = [
            // 'step' => 2
        ];

        // Инициализация аттрибутов бегущей строки
        $this->variables['hotline']['attributes'] = [

        ];

        // Инициализация элементов бегущей строки
        $this->variables['hotline']['elements'] = [
            ['content' => '1'],
            [
                'tag' => 'article',
                'content' => '2'
            ],
            ['content' => '3'],
            ['content' => '4'],
            ['content' => '5'],
            ['content' => '6'],
            ['content' => '7'],
            ['content' => '8'],
            ['content' => '9'],
            ['content' => '10'],
            ['content' => '11'],
            ['content' => '12'],
            ['content' => '13'],
            ['content' => '14'],
            ['content' => '15']
        ];

        // Генерация представления
        return $this->view->render(DIRECTORY_SEPARATOR . 'hotline' . DIRECTORY_SEPARATOR . 'index.html', $this->variables);
    }

}
