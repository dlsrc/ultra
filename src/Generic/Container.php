<?php declare(strict_types=1);
/**
 * (c) 2005-2025 Dmitry Lebedev <dl@adios.ru>
 * This source code is part of the Ultra library.
 * Please see the LICENSE file for copyright and licensing information.
 */
namespace Ultra\Generic;

/**
 * Обобщенная реализация основных статических методов для операций с контейнерами свойств.
 * Предполагается, что трейт должен быть задействован в абстракрных классах.
 * Анонимное обращение к контейнеру методами Ultra\Generic\Container::drop(),
 * Ultra\Generic\Container::exists() и Ultra\Generic\Container::open() должно выполняться
 * конкретным расширяющим классом, но не из абстракции.
 */
trait Container {
	/**
	 * Мультитон - пул содержащий объекты контейнеров свойств.
	 * Контейнеры, могут быть помещены в пул по имени или анонимно по типу.
	 */
	private static array $_container = [];

	/**
	 * Добавить объект в пул контейнеров.
	 * $container - добавляемый объект.
	 * $name      - имя контейнера, по умолчанию пустая строка, будет добавлен безымянный
	 *              контейнер на основе типа объекта.
	 * $update    - флаг перезаписи контейнера, по умолчанию контейнер перезаписывается.
	 */
	final public static function add(self $container, string $name = '', bool $update = true): bool {
		if ('' == $name) {
			$name = get_class($container);
		}

		if ($update || !isset(self::$_container[$name])) {
			self::$_container[$name] = $container;
			return true;
		}

		return false;
	}

	/**
	 * Удалить контейнер из списка.
	 * $name - имя контейнера, по умолчанию пустая строка.
	 * Анонимное удаление выполняется по классу объекта конкретно этим же классом, при этом
	 * именованные контейнеры того же класса не удаляются.
	 */
	final public static function drop(string $name = ''): void {
		if ('' == $name) {
			$name = static::class;
		}

		unset(self::$_container[$name]);
	}

	/**
	 * Проверить существование контейнера в списке по имени. Вернуть TRUE если контейнер
	 * существыет, FALSE - если контейнер с указанным именем отсутствует в списке.
	 * $name - имя контейнера, по умолчанию пустая строка.
	 * Анонимно проверяется наличие объекта того же класса и этим же классом. И только если
	 * объект был включен в список тоже анонимно, метод вернет TRUE.
	 */
	final public static function exists(string $name = ''): bool {
		if ('' == $name) {
			$name = static::class;
		}

		return isset(self::$_container[$name]);
	}

	/**
	 * Открыть контейнер по имени. Вернуть объект с указанным именем из пула контейнеров. Если
	 * объект отсутствует вернуть NULL.
	 * $name - имя контейнера, по умолчанию пустая строка.
	 * Анонимное открытие контейнера возможно конкретным классом, если контейнер того же класса
	 * был ранее помещен в пул так же анонимно.
	 */
	final public static function open(string $name = ''): static|null {
		if ('' == $name) {
			$name = static::class;
		}

		if (isset(self::$_container[$name])) {
			return self::$_container[$name];
		}

		return null;
	}
}
