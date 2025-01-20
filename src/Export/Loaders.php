<?php declare(strict_types=1);
/**
 * (c) 2005-2024 Dmitry Lebedev <dl@adios.ru>
 * This source code is part of the Ultra library.
 * Please see the LICENSE file for copyright and licensing information.
 */
namespace Ultra;

use Ultra\Generic\Storable;

trait Loaders {
	use Singletons;

	final public static function load(string $file): static {
		if (isset(self::$_instance[static::class])) {
			return self::$_instance[static::class];
		}

		if (is_readable($file)) {
			self::$_instance[static::class] = include $file;

			if (self::$_instance[static::class] instanceof static) {
				return self::$_instance[static::class];
			}

			unlink($file);

			if (is_object(self::$_instance[static::class])) {
				Error::log(Core::message('e_class', $file, get_class(self::$_instance[static::class]), static::class), Status::Domain);
			}
			else {
				Error::log(Core::message('e_type', $file, gettype(self::$_instance[static::class])), Status::Domain);
			}
		}

		self::$_instance[static::class] ??= new static;

		if (self::$_instance[static::class] instanceof Storable) {
			self::$_instance[static::class]->setFilename($file);

			if (self::$_instance[static::class] instanceof Exportable) {
				self::$_instance[static::class]->save(Save::NoError);
			}
		}

		return self::$_instance[static::class];
	}
}
