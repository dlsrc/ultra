<?php declare(strict_types=1);
/**
 * (c) 2005-2024 Dmitry Lebedev <dl@adios.ru>
 * This source code is part of the Ultra library.
 * Please see the LICENSE file for copyright and licensing information.
 */
namespace Ultra;

trait Singleton {
	private static self|null $_instance = null;

	final public static function get(): static {
		self::$_instance ??= new static;
		return self::$_instance;
	}
}
