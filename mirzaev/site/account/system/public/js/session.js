'use strict';

class session {
  /**
   * Отправить входной псевдоним на сервер
   *
   * Записывает входной псевдоним в сессию, а так же проверяет существование аккаунта с ним
   *
   * @param {string} login Входной псевдоним
   *
   * @return {object} {(bool) exist, (array) errors}
   */
  static async login(login) {
    // Запрос к серверу
    return await fetch('https://account.mirzaev.sexy/session/login', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/x-www-form-urlencoded',
      },
      body: `login=${login}&remember=1&return=exist,errors`
    })
      .then((response) => response.json())
      .then((data) => {
        return data;
      });
  }
  
  /**
   * Отправить пароль на сервер
   *
   * Записывает пароль в сессию, а так же проверяет его на соответствование требованиям
   *
   * @param {string} password Пароль
   *
   * @return {object} {(bool) verify, (bool) account, (array) errors}
   */
  static async password(password) {
    // Запрос к серверу
    return await fetch('https://account.mirzaev.sexy/session/password', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/x-www-form-urlencoded',
      },
      body: `password=${password}&remember=1&return=verify,account,errors`
    })
      .then((response) => response.json())
      .then((data) => {
        return data;
      });
  }

  /**
   * Отправить ключ приглашения на сервер
   *
   * Записывает ключ приглашения в сессию, а так же проверяет существование приглашения
   *
   * @param {string} invite Ключ приглашения
   *
   * @return {object} {(bool) exist, (array) from, (array) errors}
   */
  static async invite(invite) {
    // Запрос к серверу
    return await fetch("https://account.mirzaev.sexy/session/invite", {
      method: "POST",
      headers: {
        "Content-Type": "application/x-www-form-urlencoded",
      },
      body: `invite=${invite}&remember=1&return=exist,from,errors`,
    })
      .then((response) => response.json())
      .then((data) => {
        if (data.exist === false) {
          // Не найдено приглашение

          // Инициализация категории ошибок
          if (typeof data.errors.session === 'undefined') data.errors.session = [];

          // Запись ошибки
          data.errors.session.push('Не найдено приглашение');
        }

        return data;
      });
  }
}
