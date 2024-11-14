<?php declare(strict_types=1);
/**
 * (c) 2005-2024 Dmitry Lebedev <dl@adios.ru>
 * This source code is part of the Ultra library.
 * Please see the LICENSE file for copyright and licensing information.
 */
namespace Ultra;

use Ultra\Generic\Storable;

trait Loader {
	use Singleton;

	final public static function load(string $file): static {
		if (isset(self::$_instance)) {
			return self::$_instance;
		}

		if (is_readable($file)) {
			self::$_instance = include $file;

			if (self::$_instance instanceof static) {
				return self::$_instance;
			}

			unlink($file);

			if (is_object(self::$_instance)) {
				Error::log(Core::message('e_class', $file, get_class(self::$_instance), static::class), Status::Domain);
			}
			else {
				Error::log(Core::message('e_type', $file, gettype(self::$_instance)), Status::Domain);
			}
		}

		self::$_instance = new static;

		if (self::$_instance instanceof Storable) {
			self::$_instance->setFilename($file);

			if (self::$_instance instanceof Exportable) {
				self::$_instance->save(Save::NoError);
			}
		}

		return self::$_instance;
	}
}
