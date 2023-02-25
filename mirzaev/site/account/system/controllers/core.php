<?php

declare(strict_types=1);

namespace mirzaev\site\account\controllers;

// Файлы проекта
use mirzaev\site\account\views\manager;
use mirzaev\site\account\models\core as models;
use mirzaev\site\account\models\account_model as account;
use mirzaev\site\account\models\session_model as session;

// Библиотека для ArangoDB
use ArangoDBClient\Document as _document;

// Фреймворк PHP
use mirzaev\minimal\controller;

// Фреймворк ВКонтакте
use mirzaev\vk\core as vk;
use mirzaev\vk\robots\user as robot;

/**
 * Ядро контроллеров
 *
 * @package mirzaev\site\account\controllers
 * @author Arsen Mirzaev Tatyano-Muradovich <arsen@mirzaev.sexy>
 */
class core extends controller
{
    /**
     * Переменные окружения
     */
    protected robot $vk;

    /**
     * Переменные окружения
     */
    protected array $variables = [];

    /**
     * Конструктор
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();

        // Инициализация ядра моделей (соединение с базой данных...)
        new models();

        // Инициализация журнала ошибок
        $this->variables['errors'] = [
            'session' => [],
            'account' => [],
            'vk' => []
        ];

        // Инициализация даты до которой будет активна сессия
        $expires = time() + 604800;

        // Инициализация сессии (без журналирования)
        $this->variables['session'] = new session($_COOKIE["session"] ?? null, $expires) ?? header('Location: https://mirzaev.sexy/error?code=500&text=Не+удалось+инициализировать+сессию');

        if ($_COOKIE["session"] ?? null !== $this->variables['session']->hash) {
            // Изменился хеш сессии (подразумевается, что сессия устарела)

            // Запись хеша новой сессии
            setcookie('session', $this->variables['session']->hash, [
                'expires' => $expires,
                'domain' => 'mirzaev.sexy',
                'path' => '/',
                'secure' => true,
                'httponly' => true,
                'samesite' => 'strict'
            ]);
        }

        // Инициализация аккаунта (без журналирования)
        $this->variables['account'] = $this->variables['session']->account();

        if ($this->variables['account'] instanceof _document) {
            // Инициализирован аккаунт

            // Инициализация аккаунта ВКонтакте (без журналирования)
            $this->variables['vk'] = account::vk($this->variables['account']);

            if ($this->variables['vk'] instanceof _document) {
                // Инициализирован аккаунт ВКонтакте

                // Инициализация робота для аккаунта ВКонтакте
                $this->vk = vk::init()->user(key: $this->variables['vk']->access['key']);
            } else unset($this->variables['account'], $this->variables['vk']);
        }

        // Инициализация препроцессора представления
        $this->view = new manager;
    }
}
