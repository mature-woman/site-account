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
final class index_controller extends core
{
    /**
     * Главная страница
     *
     * @param array $parameters Параметры запроса
     */
    public function index(array $parameters = []): ?string
    {
        // Инициализация загружаемых категорий
        $this->variables['include'] = [
            'head' => ['self'],
            'body' => ['self']
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
        return $this->view->render(DIRECTORY_SEPARATOR . 'index.html', $this->variables);
    }
}
