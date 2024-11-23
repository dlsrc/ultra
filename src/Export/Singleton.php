<?php declare(strict_types=1);
/**
 * (c) 2005-2024 Dmitry Lebedev <dl@adios.ru>
 * This source code is part of the Ultra library.
 * Please see the LICENSE file for copyright and licensing information.
 */
namespace Ultra;

use ReflectionClass;
use ReflectionMethod;

trait Singleton {
	private static self|null $_instance = null;

	final public static function get(): static {
		if (isset(self::$_instance)) {
			return self::$_instance; 
		}

		$rc = new ReflectionClass(static::class);

		foreach ($rc->getMethods(ReflectionMethod::IS_PRIVATE|ReflectionMethod::IS_STATIC) as $rm) {
			if (isset($rm->getAttributes(Initializer::class)[0])) {
				self::$_instance = static::{$rm->getName()}();
				return self::$_instance;
			}
		}

		self::$_instance = new static;
		return self::$_instance;
	}
}
