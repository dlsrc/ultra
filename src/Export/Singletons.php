<?php declare(strict_types=1);
/**
 * (c) 2005-2025 Dmitry Lebedev <dl@adios.ru>
 * This source code is part of the Ultra library.
 * Please see the LICENSE file for copyright and licensing information.
 */
namespace Ultra;

use ReflectionClass;
use ReflectionMethod;

trait Singletons {
	private static array $_instance = [];

	final public static function get(): static {
		if (isset(self::$_instance[static::class])) {
			return self::$_instance[static::class]; 
		}

		$rc = new ReflectionClass(static::class);

		foreach ($rc->getMethods(ReflectionMethod::IS_PRIVATE|ReflectionMethod::IS_STATIC) as $rm) {
			if (isset($rm->getAttributes(Initializer::class)[0])) {
				self::$_instance[static::class] = static::{$rm->getName()}();
				return self::$_instance[static::class];
			}
		}

		self::$_instance[static::class] ??= new static;
		return self::$_instance[static::class];
	}
}
