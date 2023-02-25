"use strict";

class account {
    static async initialization() {
        // Запрос
        return fetch('https://auth.mirzaev.sexy/account/initialization', {
            method: 'GET'
        });
    }

    static authentication() {
        // Инициализация аккаунта
        alert(1);
        this.initialization()
            .then(
                (response) => {
                    alert(2);
                }
            );

        return true;
    }

    static deauthentication() {
    }
}
