'use strict';

class account {
  static async initialization() {
    // Запрос
    return await fetch('https://account.mirzaev.sexy/account/initialization', {
      method: 'POST',
    });
  }

  static deauthentication() {
  }

  static registration() {
    alert(228);
  }
}
