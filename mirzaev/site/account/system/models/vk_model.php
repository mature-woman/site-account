<?php

declare(strict_types=1);

namespace mirzaev\site\account\models;

// Файлы проекта
use mirzaev\site\account\models\account_model as account;

// Фреймворк ArangoDB
use mirzaev\arangodb\collection,
    mirzaev\arangodb\document;

// Фреймворк ВКонтакте
use mirzaev\vk\robots\user as robot;

// Библиотека для ArangoDB
use ArangoDBClient\Document as _document;

// Библиотека браузера
use GuzzleHttp\Client as browser;

// Встроенные библиотеки
use exception;
use stdClass;

/**
 * Модель аккаунта ВКонтакте
 *
 * @package mirzaev\site\account\models
 * @author Arsen Mirzaev Tatyano-Muradovich <arsen@mirzaev.sexy>
 */
final class vk_model extends core
{
    /**
     * Коллекция
     */
    public const COLLECTION = 'vk';

    /**
     * Инициализация
     *
     * @param string $response Ответ сервера ВКонтакте с данными аккаунта
     * @param array &$errors Журнал ошибок
     *
     * @return ?_document Инстанция аккаунта ВКонтакте, если удалось создать
     */
    public static function initialization(string $response = '', array &$errors = []): ?_document
    {
        try {
            if (collection::init(static::$db->session, self::COLLECTION)) {
                // Инициализирована коллекция

                // Инициализация данных аккаунта ВКонтакте
                $data = json_decode($response);

                if ($account = collection::search(static::$db->session, sprintf(
                    <<<AQL
                            FOR d IN %s
                            FILTER d.id == $data->user_id
                            RETURN d
                        AQL,
                    self::COLLECTION
                ))) {
                    // Найден аккаунт ВКонтакте

                    return $account;
                } else {
                    // Не найден аккаунт ВКонтакте

                    return self::create($response, $errors);
                }
            } else throw new exception('Не удалось инициализировать коллекцию');
        } catch (exception $e) {
            // Запись в журнал ошибок
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
     * Создание
     *
     * @param string $response Ответ сервера ВКонтакте с данными аккаунта
     * @param array &$errors Журнал ошибок
     *
     * @return ?_document Инстанция аккаунта ВКонтакте, если удалось создать
     */
    public static function create(string $response = '', array &$errors = []): ?_document
    {
        try {
            if (collection::init(static::$db->session, self::COLLECTION)) {
                // Инициализирована коллекция

                // Запись аккаунта в базу данных
                $_id = document::write(static::$db->session, self::COLLECTION);

                if ($account = collection::search(static::$db->session, sprintf(
                    <<<AQL
                            FOR d IN %s
                            FILTER d._id == '$_id'
                            RETURN d
                        AQL,
                    self::COLLECTION
                ))) {
                    // Найден созданный аккаунт ВКонтакте

                    if (document::update(static::$db->session, $account)) {
                        // Записано обновление

                        // Запись данных об аккаунте ВКонтакте и возврат (bool)
                        return self::update($account, json_decode($response), $errors);
                    }
                }
                throw new exception('Не удалось создать аккаунт ВКонтакте');
            } else throw new exception('Не удалось инициализировать коллекцию');
        } catch (exception $e) {
            // Запись в журнал ошибок
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
     * Запросить ключ
     *
     * @param string $code Код полученный от ВКонтакте
     * @param array &$errors Журнал ошибок
     *
     * @return ?string Тело ответа, если получен код ответа 200
     */
    public static function key(string $code = '', array &$errors = []): ?string
    {
        try {
            // Инициализация браузера
            $browser = new browser();

            // Запрос
            $response = $browser->request('GET', "https://oauth.vk.com/access_token?client_id=51447080&client_secret=KYlk0nGELW0A9ds7NQi6&redirect_uri=https://mirzaev.sexy/account/vk/connect&code=$code");

            if ($response->getStatusCode() === 200) {
                // Ответ сервера: 200

                return (string) $response->getBody();
            } else throw new exception('Не удалось получить ключ ВКонтакте (' . $response->getStatusCode() . ')');
        } catch (exception $e) {
            // Запись в журнал ошибок
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
     * Поиск связанного аккаунта
     *
     * @param _document $vk Инстанция аккаунта ВКонтакте
     * @param array &$errors Журнал ошибок
     *
     * @return ?_document Инстанция аккаунта, если удалось найти
     */
    public static function account(_document $vk, array &$errors = []): ?_document
    {
        try {
            if (
                collection::init(static::$db->session, self::COLLECTION)
                && collection::init(static::$db->session, account::COLLECTION)
                && collection::init(static::$db->session, account::COLLECTION . '_edge_' . self::COLLECTION, true)
            ) {
                // Инициализированы коллекции

                if ($account = collection::search(static::$db->session, sprintf(
                    <<<AQL
                        FOR document IN %s
                        LET edge = (
                            FOR edge IN %s
                            FILTER edge._to == '%s'
                            SORT edge._key DESC
                            LIMIT 1
                            RETURN edge
                        )
                        FILTER document._id == edge[0]._from
                        LIMIT 1
                        RETURN document
                    AQL,
                    account::COLLECTION,
                    account::COLLECTION . '_edge_' . self::COLLECTION,
                    $vk->getId()
                ))) {
                    // Найден аккаунт

                    return $account;
                } else throw new exception('Не удалось найти аккаунт');
            } else throw new exception('Не удалось инициализировать коллекцию');
        } catch (exception $e) {
            // Запись в журнал ошибок
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
     * Запрос данных аккаунта ВКонтакте с серверов ВКонтакте
     *
     * @param robot $vk Инстанция аккаунта ВКонтакте
     * @param array &$errors Журнал ошибок
     *
     * @return ?stdClass Данные аккаунта ВКонтакте, если получены
     */
    public static function parse(robot $vk, array &$errors = []): ?stdClass
    {
        try {
            // Запрос к API-серверу ВКонтакте
            $response = $vk->user->get(fields: [
                'activities',
                'about',
                // 'blacklisted',
                // 'blacklisted_by_me',
                'books',
                'bdate',
                'can_be_invited_group',
                'can_post',
                'can_see_all_posts',
                'can_see_audio',
                'can_send_friend_request',
                'can_write_private_message',
                'career',
                'common_count',
                'connections',
                'contacts',
                'city',
                'country',
                'crop_photo',
                'domain',
                'education',
                'exports',
                'followers_count',
                'friend_status',
                'has_photo',
                'has_mobile',
                'home_town',
                'photo_50',
                'photo_100',
                'photo_200',
                'photo_200_orig',
                'photo_400_orig',
                'photo_max',
                'photo_max_orig',
                'sex',
                'site',
                'schools',
                'screen_name',
                'status',
                'verified',
                'games',
                'interests',
                'is_favorite',
                'is_friend',
                'is_hidden_from_feed',
                'last_seen',
                'maiden_name',
                'military',
                'movies',
                'music',
                'nickname',
                'occupation',
                'online',
                'personal',
                'photo_id',
                'quotes',
                'relation',
                'relatives',
                'timezone',
                'tv',
                'universities'
            ])[0];

            if (!empty($response)) {
                // Получен ответ

                return $response;
            }
        } catch (exception $e) {
            // Запись в журнал ошибок
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
     * Обновление данных аккаунта ВКонтакте
     *
     * Все файлы (аватар, например) будут скачаны на сервер
     *
     * @param _document $vk Инстанция аккаунта ВКонтакте
     * @param stdClass $data Информация об аккаунте (self::parse() или json_decode())
     * @param array &$errors Журнал ошибок
     *
     * @return ?_document Инстанция аккаунта ВКонтакте, если удалось обновить
     */
    public static function update(_document $vk, stdClass $data, array &$errors = []): ?_document
    {
        try {
            if (collection::init(static::$db->session, self::COLLECTION)) {
                // Инициализирована коллекция

                if (empty($vk->id) and isset($data->user_id) || isset($data->id)) {
                    // Получен идентификатор

                    // Запись
                    $vk->id = $data->user_id ?? $data->id;

                    // Удаление из списка необработанных
                    unset($data->user_id, $data->id);
                } else if (empty($vk->id)) throw new exception('Не удалось найти идентификатор аккаунта ВКонтакте');

                if (isset($data->access_token, $data->expires_in)) {
                    // Получен ключ

                    // Запись
                    $vk->access = [
                        'key' => $data->access_token,
                        'expires' => $data->expires_in
                    ];

                    // Удаление из списка необработанных
                    unset($data->access_token, $data->expires_in);
                }

                // Инициализация браузера
                $browser = new browser();

                // Инициализация директории с обложкой
                if (!file_exists($path = INDEX . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . $vk->id . DIRECTORY_SEPARATOR . 'cover' . DIRECTORY_SEPARATOR))
                    mkdir($path, 0775, true);

                if (isset($data->photo_50)) {
                    // Получено изображение 50x50

                    if ($browser->get($data->photo_50, ['sink' => $file = "$path/50x50.jpg"])->getStatusCode() === 200)
                        $vk->cover =
                            ($vk->cover ?? []) +
                            [
                                '50x50' => ($vk->cover['50x50'] ?? []) +
                                    [
                                        'source' => $data->photo_50,
                                        'public' => "/storage/$vk->id/cover/50x50.jpg",
                                        'local' => $file,
                                    ]
                            ];
                    else throw new exception('Не удалось получить изображение 50x50 с серверов ВКонтакте');

                    // Удаление из списка необработанных
                    unset($data->photo_50);
                }

                // Инициализация директории с обложкой
                if (!file_exists($path = INDEX . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . $vk->id . DIRECTORY_SEPARATOR . 'cover' . DIRECTORY_SEPARATOR))
                    mkdir($path, 0775, true);

                if (isset($data->photo_100)) {
                    // Получено изображение 100x100

                    if ($browser->get($data->photo_100, ['sink' => $file = "$path/100x100.jpg"])->getStatusCode() === 200)
                        $vk->cover =
                            ($vk->cover ?? []) +
                            [
                                '100x100' => ($vk->cover['100x100'] ?? []) +
                                    [
                                        'source' => $data->photo_100,
                                        'public' => "/storage/$vk->id/cover/100x100.jpg",
                                        'local' => $file,
                                    ]
                            ];
                    else throw new exception('Не удалось получить изображение 100x100 с серверов ВКонтакте');

                    // Удаление из списка необработанных
                    unset($data->photo_100);
                }

                // Инициализация директории с обложкой
                if (!file_exists($path = INDEX . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . $vk->id . DIRECTORY_SEPARATOR . 'cover' . DIRECTORY_SEPARATOR))
                    mkdir($path, 0775, true);

                if (isset($data->photo_200)) {
                    // Получено изображение 200x200

                    if ($browser->get($data->photo_200, ['sink' => $file = "$path/200x200.jpg"])->getStatusCode() === 200)
                        $vk->cover =
                            ($vk->cover ?? []) +
                            [
                                '200x200' => ($vk->cover['200x200'] ?? []) +
                                    [
                                        'source' => $data->photo_200,
                                        'public' => "/storage/$vk->id/cover/200x200.jpg",
                                        'local' => $file,
                                    ]
                            ];
                    else throw new exception('Не удалось получить изображение 200x200 с серверов ВКонтакте');

                    // Удаление из списка необработанных
                    unset($data->photo_200);
                }

                if (isset($data->photo_200_orig)) {
                    // Получено изображение 200x

                    if ($browser->get($data->photo_200_orig, ['sink' => $file = "$path/200x.jpg"])->getStatusCode() === 200)
                        $vk->cover =
                            ($vk->cover ?? []) +
                            [
                                '200x' => ($vk->cover['200x'] ?? []) +
                                    [
                                        'source' => $data->photo_200_orig,
                                        'public' => "/storage/$vk->id/cover/200x.jpg",
                                        'local' => $file,
                                    ]
                            ];
                    else throw new exception('Не удалось получить изображение 200x с серверов ВКонтакте');

                    // Удаление из списка необработанных
                    unset($data->photo_200_orig);
                }

                if (isset($data->photo_400_orig)) {
                    // Получено изображение 400x

                    if ($browser->get($data->photo_400_orig, ['sink' => $file = "$path/400x.jpg"])->getStatusCode() === 200)
                        $vk->cover =
                            ($vk->cover ?? []) +
                            [
                                '400x' => ($vk->cover['400x'] ?? []) +
                                    [
                                        'source' => $data->photo_400_orig,
                                        'public' => "/storage/$vk->id/cover/400x.jpg",
                                        'local' => $file,
                                    ]
                            ];
                    else throw new exception('Не удалось получить изображение 400x с серверов ВКонтакте');

                    // Удаление из списка необработанных
                    unset($data->photo_400_orig);
                }

                if (isset($data->photo_max)) {
                    // Получено изображение MAXxMAX

                    if ($browser->get($data->photo_max, ['sink' => $file = "$path/MAXxMAX.jpg"])->getStatusCode() === 200)
                        $vk->cover =
                            ($vk->cover ?? []) +
                            [
                                'MAXxMAX' => ($vk->cover['MAXxMAX'] ?? []) +
                                    [
                                        'source' => $data->photo_max,
                                        'public' => "/storage/$vk->id/cover/MAXxMAX.jpg",
                                        'local' => $file,
                                    ]
                            ];
                    else throw new exception('Не удалось получить изображение MAXxMAX с серверов ВКонтакте');

                    // Удаление из списка необработанных
                    unset($data->photo_max);
                }

                if (isset($data->photo_max_orig)) {
                    // Получено изображение MAXx

                    if ($browser->get($data->photo_max_orig, ['sink' => $file = "$path/MAXx.jpg"])->getStatusCode() === 200)
                        $vk->cover =
                            ($vk->cover ?? []) +
                            [
                                'MAXx' => ($vk->cover['MAXx'] ?? []) +
                                    [
                                        'source' => $data->photo_max_orig,
                                        'public' => "/storage/$vk->id/cover/MAXx.jpg",
                                        'local' => $file,
                                    ]
                            ];
                    else throw new exception('Не удалось получить изображение MAXx с серверов ВКонтакте');

                    // Удаление из списка необработанных
                    unset($data->photo_max_orig);
                }

                if (isset($data->crop_photo)) {
                    // Получено изображение MAXx

                    if ($browser->get($data->photo_max_orig, ['sink' => $file = "$path/MAXx.jpg"])->getStatusCode() === 200)
                        $vk->cover =
                            ($vk->cover ?? []) +
                            [
                                'MAXx' => ($vk->cover['MAXx'] ?? []) +
                                    [
                                        'source' => $data->photo_max_orig,
                                        'public' => "/storage/$vk->id/cover/MAXx.jpg",
                                        'local' => $file,
                                    ]
                            ];
                    else throw new exception('Не удалось получить изображение MAXx с серверов ВКонтакте');

                    // Удаление из списка необработанных
                    unset($data->photo_max_orig);
                }

                // Перебор оставшихся параметров
                foreach ($data as $key => $value) $vk->{$key} = $value;

                if (document::update(static::$db->session, $vk)) {
                    // Записано обновление

                    return $vk;
                } else throw new exception('Не удалось записать данные аккаунта ВКонтакте');
            } else throw new exception('Не удалось инициализировать коллекцию');
        } catch (exception $e) {
            // Запись в журнал ошибок
            $errors[] = [
                'text' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'stack' => $e->getTrace()
            ];
        }

        return null;
    }
}
