'use strict';

/**
 * Бегущая строка
 *
 * @description
 * Простой, но мощный класс для создания бегущих строк. Поддерживает
 * перемещение мышью и прокрутку колесом, полностью настраивается очень гибок
 * для настроек в CSS и подразумевается, что отлично индексируется поисковыми роботами.
 * Имеет свой препроцессор, благодаря которому можно создавать бегущие строки
 * без программирования - с помощью HTML-аттрибутов, а так же возможность
 * изменять параметры (data-hotline-* аттрибуты) на лету. Есть возможность вызывать
 * события при выбранных действиях для того, чтобы пользователь имел возможность
 * дорабатывать функционал без изучения и изменения моего кода
 *
 * @example
 * сonst hotline = new hotline();
 * hotline.step = '-5';
 * hotline.start();
 *
 * @todo
 * 1. Бесконечный режим - элементы не удаляются если видны на экране (будут дубликаты)
 *
 * @copyright WTFPL
 * @author Arsen Mirzaev Tatyano-Muradovich <arsen@mirzaev.sexy>
 */
class hotline {
  // Идентификатор
  #id = 0;

  // Оболочка (instanceof HTMLElement)
  #shell = document.getElementById("hotline");

  // Инстанция горячей строки
  #instance = null;

  // Перемещение
  #transfer = true;

  // Движение
  #move = true;

  // Наблюдатель
  #observer = null;

  // Наблюдатель
  #block = new Set(["events"]);

  // Настраиваемые параметры
  transfer = null;
  move = null;
  delay = 10;
  step = 1;
  hover = true;
  movable = true;
  sticky = false;
  wheel = false;
  delta = null;
  vertical = false;
  observe = false;
  events = new Map([
    ["start", false],
    ["stop", false],
    ["move", false],
    ["move.block", false],
    ["move.unblock", false],
    ["offset", false],
    ["transfer.start", true],
    ["transfer.end", true],
    ["onmousemove", false]
  ]);

  constructor(id, shell) {
    // Запись идентификатора
    if (typeof id === "string" || typeof id === "number") this.#id = id;

    // Запись оболочки
    if (shell instanceof HTMLElement) this.#shell = shell;
  }

  start() {
    if (this.#instance === null) {
      // Нет запущенной инстанции бегущей строки

      // Инициализация ссылки на ядро
      const _this = this;

      // Запуск движения
      this.#instance = setInterval(function () {
        if (_this.#shell.childElementCount > 1) {
          // Найдено содержимое бегущей строки (2 и более)

          // Инициализация буфера для временных данных
          let buffer;

          // Инициализация данных первого элемента в строке
          const first = {
            element: (buffer = _this.#shell.firstElementChild),
            coords: buffer.getBoundingClientRect()
          };

          if (_this.vertical) {
            // Вертикальная бегущая строка

            // Инициализация сдвига у первого элемента (движение)
            first.offset = isNaN(
              (buffer = parseFloat(first.element.style.marginTop))
            )
              ? 0
              : buffer;

            // Инициализация отступа до второго элемента у первого элемента (разделение)
            first.separator = isNaN(
              (buffer = parseFloat(
                getComputedStyle(first.element).marginBottom
              ))
            )
              ? 0
              : buffer;

            // Инициализация крайнего с конца ребра первого элемента в строке
            first.end = first.coords.y + first.coords.height + first.separator;
          } else {
            // Горизонтальная бегущая строка

            // Инициализация отступа у первого элемента (движение)
            first.offset = isNaN(
              (buffer = parseFloat(first.element.style.marginLeft))
            )
              ? 0
              : buffer;

            // Инициализация отступа до второго элемента у первого элемента (разделение)
            first.separator = isNaN(
              (buffer = parseFloat(getComputedStyle(first.element).marginRight))
            )
              ? 0
              : buffer;

            // Инициализация крайнего с конца ребра первого элемента в строке
            first.end = first.coords.x + first.coords.width + first.separator;
          }

          if (
            (_this.vertical &&
              Math.round(first.end) < _this.#shell.offsetTop) ||
            (!_this.vertical && Math.round(first.end) < _this.#shell.offsetLeft)
          ) {
            // Элемент (вместе с отступом до второго элемента) вышел из области видимости (строки)

            if (
              (_this.transfer === null && _this.#transfer) ||
              _this.transfer === true
            ) {
              // Перенос разрешен

              if (_this.vertical) {
                // Вертикальная бегущая строка

                // Удаление отступов (движения)
                first.element.style.marginTop = null;
              } else {
                // Горизонтальная бегущая строка

                // Удаление отступов (движения)
                first.element.style.marginLeft = null;
              }

              // Копирование первого элемента в конец строки
              _this.#shell.appendChild(first.element);

              if (_this.events.get("transfer.end")) {
                // Запрошен вызов события: "перемещение в конец"

                // Вызов события: "перемещение в конец"
                document.dispatchEvent(
                  new CustomEvent(`hotline.${_this.#id}.transfer.end`, {
                    detail: {
                      element: first.element,
                      offset: -(
                        (_this.vertical
                          ? first.coords.height
                          : first.coords.width) + first.separator
                      )
                    }
                  })
                );
              }
            }
          } else if (
            (_this.vertical &&
              Math.round(first.coords.y) > _this.#shell.offsetTop) ||
            (!_this.vertical &&
              Math.round(first.coords.x) > _this.#shell.offsetLeft)
          ) {
            // Передняя (движущая) граница первого элемента вышла из области видимости

            if (
              (_this.transfer === null && _this.#transfer) ||
              _this.transfer === true
            ) {
              // Перенос разрешен

              // Инициализация отступа у последнего элемента (разделение)
              const separator =
                (buffer = isNaN(
                  (buffer = parseFloat(
                    getComputedStyle(_this.#shell.lastElementChild)[
                    _this.vertical ? "marginBottom" : "marginRight"
                    ]
                  ))
                )
                  ? 0
                  : buffer) === 0
                  ? first.separator
                  : buffer;

              // Инициализация координат первого элемента в строке
              const coords = _this.#shell.lastElementChild.getBoundingClientRect();

              if (_this.vertical) {
                // Вертикальная бегущая строка

                // Удаление отступов (движения)
                _this.#shell.lastElementChild.style.marginTop =
                  -coords.height - separator + "px";
              } else {
                // Горизонтальная бегущая строка

                // Удаление отступов (движения)
                _this.#shell.lastElementChild.style.marginLeft =
                  -coords.width - separator + "px";
              }

              // Копирование последнего элемента в начало строки
              _this.#shell.insertBefore(
                _this.#shell.lastElementChild,
                first.element
              );

              // Удаление отступов у второго элемента в строке (движения)
              _this.#shell.children[1].style[
                _this.vertical ? "marginTop" : "marginLeft"
              ] = null;

              if (_this.events.get("transfer.start")) {
                // Запрошен вызов события: "перемещение в начало"

                // Вызов события: "перемещение в начало"
                document.dispatchEvent(
                  new CustomEvent(`hotline.${_this.#id}.transfer.start`, {
                    detail: {
                      element: _this.#shell.lastElementChild,
                      offset:
                        (_this.vertical ? coords.height : coords.width) +
                        separator
                    }
                  })
                );
              }
            }
          } else {
            // Элемент в области видимости

            if ((_this.move === null && _this.#move) || _this.move === true) {
              // Движение разрешено

              // Запись новых координат сдвига
              const offset = first.offset + _this.step;

              // Запись сдвига (движение)
              _this.offset(offset);

              if (_this.events.get("move")) {
                // Запрошен вызов события: "движение"

                // Вызов события: "движение"
                document.dispatchEvent(
                  new CustomEvent(`hotline.${_this.#id}.move`, {
                    detail: {
                      from: first.offset,
                      to: offset
                    }
                  })
                );
              }
            }
          }
        }
      }, _this.delay);

      if (this.hover) {
        // Запрошена возможность останавливать бегущую строку

        // Инициализация сдвига
        let offset = 0;

        // Инициализация слушателя события при перемещении элемента в бегущей строке
        const listener = function (e) {
          // Увеличение сдвига
          offset += e.detail.offset ?? 0;
        };

        // Инициализация обработчика наведения курсора (остановка движения)
        this.#shell.onmouseover = function (e) {
          // Курсор наведён на бегущую строку

          // Блокировка движения
          _this.#move = false;

          if (_this.events.get("move.block")) {
            // Запрошен вызов события: "блокировка движения"

            // Вызов события: "блокировка движения"
            document.dispatchEvent(
              new CustomEvent(`hotline.${_this.#id}.move.block`)
            );
          }

          if (_this.movable) {
            // Запрошена возможность двигать бегущую строку

            _this.#shell.onmousedown = function (onmousedown) {
              // Курсор активирован

              // Инициализация слушателей события перемещения элемента в бегущей строке
              document.addEventListener(
                `hotline.${_this.#id}.transfer.start`,
                listener
              );
              document.addEventListener(
                `hotline.${_this.#id}.transfer.end`,
                listener
              );

              // Инициализация буфера для временных данных
              let buffer;

              // Инициализация данных первого элемента в строке
              const first = {
                offset: isNaN(
                  (buffer = parseFloat(
                    _this.vertical
                      ? _this.#shell.firstElementChild.style.marginTop
                      : _this.#shell.firstElementChild.style.marginLeft
                  ))
                )
                  ? 0
                  : buffer
              };

              document.onmousemove = function (onmousemove) {
                // Курсор движется

                if (_this.vertical) {
                  // Вертикальная бегущая строка

                  // Инициализация буфера местоположения
                  const from = _this.#shell.firstElementChild.style.marginTop;
                  const to = onmousemove.pageY - (onmousedown.pageY + offset - first.offset);

                  // Движение
                  _this.#shell.firstElementChild.style.marginTop = to +
                    "px";

                  if (_this.events.get("onmousemove")) {
                    // Запрошен вызов события: "перемещение мышью"

                    // Вызов события: "перемещение мышью"
                    document.dispatchEvent(
                      new CustomEvent(`hotline.${_this.#id}.onmousemove`, {
                        detail: { from, to }
                      })
                    );
                  }
                } else {
                  // Горизонтальная бегущая строка

                  // Инициализация буфера местоположения
                  const from = _this.#shell.firstElementChild.style.marginLeft;
                  const to = onmousemove.pageX - (onmousedown.pageX + offset - first.offset);

                  // Движение
                  _this.#shell.firstElementChild.style.marginLeft = to + "px";

                  if (_this.events.get("onmousemove")) {
                    // Запрошен вызов события: "перемещение мышью"

                    // Вызов события: "перемещение мышью"
                    document.dispatchEvent(
                      new CustomEvent(`hotline.${_this.#id}.onmousemove`, {
                        detail: { from, to }
                      })
                    );
                  }
                }

                // Запись курсора
                _this.#shell.style.cursor = "grabbing";
              };
            };

            // Перещапись событий браузера (чтобы не дёргалось)
            _this.#shell.ondragstart = null;

            _this.#shell.onmouseup = function () {
              // Курсор деактивирован

              // Остановка обработки движения
              document.onmousemove = null;

              // Сброс сдвига
              offset = 0;

              document.removeEventListener(
                `hotline.${_this.#id}.transfer.start`,
                listener
              );
              document.removeEventListener(
                `hotline.${_this.#id}.transfer.end`,
                listener
              );

              // Восстановление курсора
              _this.#shell.style.cursor = null;
            };
          }
        };

        // Инициализация обработчика отведения курсора (остановка движения)
        this.#shell.onmouseleave = function (onmouseleave) {
          // Курсор отведён от бегущей строки

          if (!_this.sticky) {
            // Отключено прилипание

            // Остановка обработки движения
            document.onmousemove = null;

            document.removeEventListener(
              `hotline.${_this.#id}.transfer.start`,
              listener
            );
            document.removeEventListener(
              `hotline.${_this.#id}.transfer.end`,
              listener
            );

            // Восстановление курсора
            _this.#shell.style.cursor = null;
          }

          // Сброс сдвига
          offset = 0;

          // Разблокировка движения
          _this.#move = true;

          if (_this.events.get("move.unblock")) {
            // Запрошен вызов события: "разблокировка движения"

            // Вызов события: "разблокировка движения"
            document.dispatchEvent(
              new CustomEvent(`hotline.${_this.#id}.move.unblock`)
            );
          }
        };
      }

      if (this.wheel) {
        // Запрошена возможность прокручивать колесом мыши

        // Инициализация обработчика наведения курсора (остановка движения)
        this.#shell.onwheel = function (e) {
          // Курсор наведён на бегущую

          // Инициализация буфера для временных данных
          let buffer;

          // Перемещение
          _this.offset(
            (isNaN(
              (buffer = parseFloat(
                _this.#shell.firstElementChild.style[
                _this.vertical ? "marginTop" : "marginLeft"
                ]
              ))
            )
              ? 0
              : buffer) +
            (_this.delta === null
              ? e.wheelDelta
              : e.wheelDelta > 0
                ? _this.delta
                : -_this.delta)
          );
        };
      }
    }

    if (this.observe) {
      // Запрошено наблюдение за изменениями аттрибутов элемента бегущей строки

      if (this.#observer === null) {
        // Отсутствует наблюдатель

        // Инициализация ссылки на ядро
        const _this = this;

        // Инициализация наблюдателя
        this.#observer = new MutationObserver(function (mutations) {
          for (const mutation of mutations) {
            if (mutation.type === "attributes") {
              // Запись параметра в инстанцию бегущей строки
              _this.write(mutation.attributeName);
            }
          }

          // Перезапуск бегущей строки
          _this.restart();
        });

        // Активация наблюдения
        this.#observer.observe(this.#shell, {
          attributes: true
        });
      }
    } else if (this.#observer instanceof MutationObserver) {
      // Запрошено отключение наблюдения

      // Деактивация наблюдения
      this.#observer.disconnect();

      // Удаление наблюдателя
      this.#observer = null;
    }

    if (this.events.get("start")) {
      // Запрошен вызов события: "запуск"

      // Вызов события: "запуск"
      document.dispatchEvent(new CustomEvent(`hotline.${this.#id}.start`));
    }
  }

  stop() {
    // Остановка бегущей строки
    clearInterval(this.#instance);

    // Удаление инстанции интервала
    this.#instance = null;

    if (this.events.get("stop")) {
      // Запрошен вызов события: "остановка"

      // Вызов события: "остановка"
      document.dispatchEvent(new CustomEvent(`hotline.${this.#id}.stop`));
    }
  }

  restart() {
    // Остановка бегущей строки
    this.stop();

    // Запуск бегущей строки
    this.start();
  }

  write(attribute) {
    // Инициализация названия параметра
    const parameter = (/^data-hotline-(\w+)$/.exec(attribute) ?? [, null])[1];

    if (typeof parameter === "string") {
      // Параметр найден

      // Проверка на разрешение изменения
      if (this.#block.has(parameter)) return;

      // Инициализация значения параметра
      const value = this.#shell.getAttribute(attribute);

      // Инициализация буфера для временных данных
      let buffer;

      // Запись параметра
      this[parameter] = isNaN((buffer = parseFloat(value)))
        ? value === "true"
          ? true
          : value === "false"
            ? false
            : value
        : buffer;
    }
  }

  offset(value) {
    // Запись отступа
    this.#shell.firstElementChild.style[
      this.vertical ? "marginTop" : "marginLeft"
    ] = value + "px";

    if (this.events.get("offset")) {
      // Запрошен вызов события: "сдвиг"

      // Вызов события: "сдвиг"
      document.dispatchEvent(
        new CustomEvent(`hotline.${this.#id}.offset`, {
          detail: {
            to: value
          }
        })
      );
    }
  }

  static preprocessing(event = false) {
    // Инициализация счётчиков инстанций горячей строки
    const success = new Set();
    let error = 0;

    for (const element of document.querySelectorAll('*[data-hotline="true"]')) {
      // Перебор бегущих строк

      if (typeof element.id === "string") {
        // Найден идентификатор

        // Инициализация инстанции бегущей строки
        const hotline = new this(element.id, element);

        for (const attribute of element.getAttributeNames()) {
          // Перебор аттрибутов

          // Запись параметра в инстанцию бегущей строки
          hotline.write(attribute);
        }

        // Запуск бегущей строки
        hotline.start();

        // Запись инстанции бегущей строки в элемент
        element.hotline = hotline;

        // Запись в счётчик успешных инициализаций
        success.add(hotline);
      } else ++error;
    }

    if (event) {
      // Запрошен вызов события: "предварительная подготовка"

      // Вызов события: "предварительная подготовка"
      document.dispatchEvent(
        new CustomEvent(`hotline.preprocessed`, {
          detail: {
            success,
            error
          }
        })
      );
    }
  }
}

document.dispatchEvent(
  new CustomEvent("hotline.loaded", {
    detail: { hotline }
  })
);
