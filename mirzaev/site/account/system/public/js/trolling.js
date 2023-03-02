"use strict";

class troller {
    static what = {
        enable() {
            document.body.onmouseleave = function () {
                // if (Math.random() > 0.90) {
                // 10%

                troller.what.start();
                // }
            };

            document.body.onmouseenter = function () {
                troller.what.end();
            };
        },
        disable() {
            document.body.onmouseleave = document.body.onmouseenter = undefined;
        },
        start() {
            // Отображение изображения
            document.getElementById('what_image').classList.add('active');

            // Инициализация элемента со звуком
            const what_sound = document.getElementById('what_sound');

            // Воспроизведение звука
            what_sound.currentTime = 0;
            what_sound.play();
        },
        end() {
            // Сокрытие изображения
            document.getElementById('what_image').classList.remove('active');

            // Остановка звука
            document.getElementById('what_sound').pause();
        },
        single(event = 'onmouseleave') {
            if (typeof event === 'string') {
                // Получены обязательные входные параметры
                // Отображение изображения
                document.getElementById('what_image').classList.add('active');

                // Инициализация элемента со звуком
                const what_sound = document.getElementById('what_sound');

                // Воспроизведение звука
                what_sound.currentTime = 0;
                what_sound.play();

                document.body[event] = function () {
                    troller.what.end();

                    document.body[event] = undefined;
                };
            }
        }
    }

    static vk() {
        setInterval(function () {
            const sound = document.getElementById('sound_vk');

            if (Math.random() > 0.95) {
                // 5%

                // Воспроизведение звука
                sound.currentTime = 0;
                sound.play();
            }
        }, 85000);
    }

    static whatsapp() {
        setInterval(function () {
            const sound = document.getElementById('sound_whatsup');

            if (Math.random() > 0.97) {
                // 3%

                // Воспроизведение звука
                sound.currentTime = 0;
                sound.play();
            }
        }, 125000);
    }

    static iphone() {
        setInterval(function () {
            const sound = document.getElementById('sound_iphone');

            if (Math.random() > 0.98) {
                // 2%

                // Воспроизведение звука
                sound.currentTime = 0;
                sound.play();
            }
        }, 265000);
    }
}

if (Math.random() > 0.90) {
    // 10%

    troller.what.enable();
}

if (Math.random() > 0.90) {
    // 10%

    troller.vk();
}


if (Math.random() > 0.90) {
    // 10%

    troller.whatsapp();
}

if (Math.random() > 0.90) {
    // 10%

    troller.iphone();
}
