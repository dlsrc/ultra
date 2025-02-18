<?php declare(strict_types=1);
/**
 * (c) 2005-2025 Dmitry Lebedev <dl@adios.ru>
 * This source code is part of the Ultra library.
 * Please see the LICENSE file for copyright and licensing information.
 */
namespace Ultra\Generic;

use Ultra\Code;
use Ultra\Core;
use Ultra\Error;
use Ultra\Exportable;
//use Ultra\Fail;
use Ultra\Instance;
use Ultra\Save;
//use Ultra\State;
use Ultra\Status;

/**
 * Реализация интерфейса Ultra\Generic\ImportableNameless
 * Типаж Ultra\Instance добавляет поддержку интерфейса Ultra\State
 */
trait Nameless {
	use Instance;
	use NamelessGetter;

	final public static function load(string $file): static {
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
				//return Error::log(Core::message('e_type', $file, gettype(self::$_container[static::class])), Status::Domain);
				Error::log(Core::message('e_type', $file, gettype(self::$_container[static::class])), Status::Domain);
			}
			else {
				unlink($file);
				//return Error::log(Core::message('e_class', $file, get_class(self::$_container[static::class]), static::class), Status::Domain);
				Error::log(Core::message('e_class', $file, get_class(self::$_container[static::class]), static::class), Status::Domain);
			}
		}

		self::$_container[static::class] ??= new static;

		if (self::$_container[static::class] instanceof Storable) {
			self::$_container[static::class]->setFilename($file);

			if (self::$_container[static::class] instanceof Exportable) {
				self::$_container[static::class]->save(Save::NoError);
			}
		}

		return self::$_container[static::class];
	}

	final public static function find(string $file, bool $log = false): static|null {
		if (isset(self::$_container[static::class])) {
			return self::$_container[static::class];
		}

		if (!is_readable($file)) {
			//return new Fail(Code::Nofile, 'File "'.$file.'" not found or not readable.', __FILE__, __LINE__);
			if ($log) {
				Error::log('File "'.$file.'" not found or not readable.', Code::Nofile);
			}

			return null;
		}

		self::$_container[static::class] = include $file;

		if (self::$_container[static::class] instanceof static) {
			return self::$_container[static::class];
		}

		if (is_object(self::$_container[$name])) {
			$message = 'Unexpected object type '.self::$_container[$name]::class.', expected '.static::class.'.';
		}
		else {
			$message = 'Unexpected type: '.gettype(self::$_container[$name]).', expected '.static::class.'.';
		}

		//return new Fail(Status::Noobject, $message, __FILE__, __LINE__);
		Error::log($message, Status::Noobject);
		unset(self::$_container[static::class]);
		return null;
	}

	final public function refind(): self {
		$name = get_class($this);
		self::drop($name);

		if (!$refind = self::find($this->_file, true)) {
			self::add($this, $name);
			return $this;
		}

		return $refind;
	}
}