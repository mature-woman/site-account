'use strict';

class session {
  /**
   * Отправить входной псевдоним на сервер
   *
   * Записывает входной псевдоним в сессию, а так же проверяет существование аккаунта с ним
   *
   * @param {string} login Входной
   *
   * @return {object} {(bool) exist, (array) errors}
   */
  static async login(login) {
    // Запрос
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
   * @return {object} {(bool) verify, (array) errors}
   */
  static async password(password) {
    // Запрос
    return await fetch('https://account.mirzaev.sexy/session/password', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/x-www-form-urlencoded',
      },
      body: `password=${password}&return=verify,errors`
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
    // Запрос
    return await fetch("https://account.mirzaev.sexy/session/invite", {
      method: "POST",
      headers: {
        "Content-Type": "application/x-www-form-urlencoded",
      },
      body: `invite=${invite}&remember=1&return=exist,from,errors`,
    })
      .then((response) => response.json())
      .then((data) => {
        return data;
      });
  }
}
