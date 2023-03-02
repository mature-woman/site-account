'use strict';

/**
  * Демпфер
  *
  * @param {function} func Функция
  * @param {number} timeout Таймер (ms)
  *
  * @return {void}
  */
function damper(func, timeout = 300) {
  // Инициализация таймера
  let timer;

  return (...args) => {
    // Деинициализация таймера
    clearTimeout(timer);

    // Вызов функции (вход в рекурсию)
    timer = setTimeout(() => {
      func.apply(this, args);
    }, timeout);
  };
}

// Вызов события "Инициализирован демпфер"
document.dispatchEvent(
  new CustomEvent("damper.initialized", {
    detail: { damper }
  })
);
