<?php declare(strict_types=1);
/**
 * (c) 2005-2025 Dmitry Lebedev <dl@adios.ru>
 * This source code is part of the Ultra library.
 * Please see the LICENSE file for copyright and licensing information.
 */
namespace Ultra;

trait Finder {
	use Singleton;

	final public static function find(string $file, bool $log = false): static|null {
		if (isset(self::$_instance)) {
			return self::$_instance;
		}

		if (!is_readable($file)) {
			if ($log) {
				Error::log('File "'.$file.'" not found or not readable.', Code::Nofile);
			}

			return null;
		}

		self::$_instance = include $file;

		if (self::$_instance instanceof static) {
			return self::$_instance;
		}

		if (is_object(self::$_instance)) {
			$message = 'Unexpected object type '.get_class(self::$_instance).', expected '.static::class.'.';
		}
		else {
			$message = 'Unexpected type: '.gettype(self::$_instance).', expected '.static::class.'.';
		}

		Error::log($message, Status::Noobject);
		unset(self::$_instance);
		return null;
	}
}
