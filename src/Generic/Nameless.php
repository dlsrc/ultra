<?php declare(strict_types=1);
/**
 * (c) 2005-2024 Dmitry Lebedev <dl@adios.ru>
 * This source code is part of the Ultra library.
 * Please see the LICENSE file for copyright and licensing information.
 */
namespace Ultra\Generic;

use Ultra\Code;
use Ultra\Core;
use Ultra\Error;
use Ultra\Result\Fail;
use Ultra\Result\State;
use Ultra\Result\Status;
use Ultra\Export\Save;
use Ultra\Export\Exportable;

/**
 * Реализация интерфейса Ultra\Kit\ImportableNameless
 */
trait Nameless {
	use NamelessGetter;

	final public static function load(string $file): State {
		if (isset(self::$_container[static::class])) {
			return self::$_container[static::class];
		}

		if (is_readable($file)) {
			self::$_container[static::class] = include $file;

			if (self::$_container[static::class] instanceof static) {
				return self::$_container[static::class];
			}

			if (!is_object(self::$_container[static::class])) {
				unlink($file);
				return Error::log(
					Core::message(
						'e_type',
						$file,
						gettype(self::$_container[static::class])
					),
					Status::Domain
				);
			}
			else {
				unlink($file);
				return Error::log(
					Core::message(
						'e_class',
						$file,
						get_class(self::$_container[static::class]),
						static::class
					),
					Status::Domain
				);
			}
		}

		self::$_container[static::class] = new static;

		if (self::$_container[static::class] instanceof Storable) {
			self::$_container[static::class]->setFilename($file);

			if (self::$_container[static::class] instanceof Exportable) {
				self::$_container[static::class]->save(Save::NoError);
			}
		}

		return self::$_container[static::class];
	}

	final public static function find(string $file): State {
		if (isset(self::$_container[static::class])) {
			return self::$_container[static::class];
		}

		if (!is_readable($file)) {
			return new Fail(Code::Nofile, 'File "'.$file.'" not found or not readable.', __FILE__, __LINE__);
		}

		self::$_container[static::class] = include $file;

		if (self::$_container[static::class] instanceof static) {
			return self::$_container[static::class];
		}

		unset(self::$_container[static::class]);
		return new Fail(Status::Noobject, 'Unexpected object type.', __FILE__, __LINE__);
	}

	final public function refind(): State {
		$name = get_class($this);
		self::drop($name);

		if (!$refind = self::find($this->_file)) {
			self::add($this, $name);
			return $this;
		}

		return $refind;
	}
}