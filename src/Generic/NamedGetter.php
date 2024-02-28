<?php declare(strict_types=1);
/**
 * (c) 2005-2024 Dmitry Lebedev <dl@adios.ru>
 * This source code is part of the Ultra library.
 * Please see the LICENSE file for copyright and licensing information.
 */
namespace Ultra\Generic;

/**
 * Точка доступа к именованным контейнерам свойств.
 */
trait NamedGetter {
	use Container;

	final public static function get(string $name = ''): static {
		if ('' == $name) {
			$name = static::class;
		}

		if (!isset(self::$_container[$name])) {
			if (is_subclass_of(static::class, Called::class)) {
				self::$_container[$name] = new static([], $name);
			}
			else {
				self::$_container[$name] = new static;
			}
		}

		return self::$_container[$name];
	}
}
