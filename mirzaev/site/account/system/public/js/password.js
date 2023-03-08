'use strict';

class password {
  /**
   * Сгенерировать
   *
   * @param {number} length Длина (количество слов в мнемоническом, либо символов в классическом)
   *
   * @return {object} {(string) password, (array) errors}
   */
  static async generate(length = 12, type = "classic") {
    // Запрос к серверу
    return await fetch("https://account.mirzaev.sexy/api/generate/password", {
      method: "POST",
      headers: {
        "Content-Type": "application/x-www-form-urlencoded",
      },
      body: `length=${length}&type=${type}&return=password,errors`,
    })
      .then((response) => response.json())
      .then((data) => {
        return data;
      });
  }
}
