<?php declare(strict_types=1);
/**
 * (c) 2005-2024 Dmitry Lebedev <dl@adios.ru>
 * This source code is part of the Ultra library.
 * Please see the LICENSE file for copyright and licensing information.
 */
namespace Ultra;

trait Finders {
	use Singletons;

	final public static function find(string $file, bool $log = false): static|null {
		if (isset(self::$_instance[static::class])) {
			return self::$_instance[static::class];
		}

		if (!is_readable($file)) {
			if ($log) {
				Error::log('File "'.$file.'" not found or not readable.', Code::Nofile);
			}

			return null;
		}

		self::$_instance[static::class] = include $file;

		if (self::$_instance[static::class] instanceof static) {
			return self::$_instance[static::class];
		}

		if (is_object(self::$_instance[static::class])) {
			$message = 'Unexpected object type '.get_class(self::$_instance[static::class]).', expected '.static::class.'.';
		}
		else {
			$message = 'Unexpected type: '.gettype(self::$_instance[static::class]).', expected '.static::class.'.';
		}

		Error::log($message, Status::Noobject);
		unset(self::$_instance[static::class]);
		return null;
	}
}
