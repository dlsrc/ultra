<?php declare(strict_types=1);
/**
 * (c) 2005-2026 Dmitry Lebedev <dlsrc.extra@gmail.com>
 * This source code is part of the Ultra library.
 * Please see the LICENSE file for copyright and licensing information.
 */
namespace Ultra\Generic;

/**
 * Точка доступа к безымянным контейнерам свойств.
 */
trait NamelessGetter {
	use Container;

	final public static function get(): static {
		self::$_container[static::class] ??= new static;
		return self::$_container[static::class];
	}
}
