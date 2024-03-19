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
use Ultra\Exportable;
use Ultra\Instance;
//use Ultra\Fail;
use Ultra\Save;
//use Ultra\State;
use Ultra\Status;

/**
 * Реализация интерфейса Ultra\Generic\ImportableNamed
 * Типаж Ultra\Instance добавляет поддержку интерфейса Ultra\State
 */
trait Named {
	use Instance;
	use NamedGetter;
	
	final public static function load(string $file, string $name = ''): static {
		if ('' == $name) {
			$name = static::class;
		}

		if (isset(self::$_container[$name])) {
			return self::$_container[$name];
		}

		if (is_readable($file)) {
			self::$_container[$name] = include $file;

			if (self::$_container[$name] instanceof static) {
				return self::$_container[$name];
			}

			if (!is_object(self::$_container[$name])) {
				unlink($file);
				//return Error::log(Core::message('e_type', $file, gettype(self::$_container[$name])), Status::Domain);
				Error::log(Core::message('e_type', $file, gettype(self::$_container[$name])), Status::Domain);
			}
			else {
				unlink($file);
				//return Error::log(Core::message('e_class', $file, get_class(self::$_container[$name]), static::class), Status::Domain);
				Error::log(Core::message('e_class', $file, get_class(self::$_container[$name]), static::class), Status::Domain);
			}
		}

		self::$_container[$name] = new static([], $name);

		if (self::$_container[$name] instanceof Storable) {
			self::$_container[$name]->setFilename($file);

			if (self::$_container[$name] instanceof Exportable) {
				self::$_container[$name]->save(Save::NoError);
			}
		}

		return self::$_container[$name];
	}

	final public static function find(string $file, string $name = '', bool $nolog = false): static|null {
		if ('' == $name) {
			$name = static::class;
		}

		if (isset(self::$_container[$name])) {
			return self::$_container[$name];
		}

		if (!is_readable($file)) {
			//return new Fail(Code::Nofile, 'File "'.$file.'" not found or not readable.', __FILE__, __LINE__);
			if (!$nolog) {
				Error::log('File "'.$file.'" not found or not readable.', Code::Nofile);
			}

			return null;
		}

		self::$_container[$name] = include $file;

		if (self::$_container[$name] instanceof static) {
			return self::$_container[$name];
		}

		if (is_object(self::$_container[$name])) {
			$message = 'Unexpected object type '.self::$_container[$name]::class.', expected '.static::class.'.';
		}
		else {
			$message = 'Unexpected type: '.gettype(self::$_container[$name]).', expected '.static::class.'.';
		}

		//return new Fail(Status::Noobject, 'Unexpected object type.', __FILE__, __LINE__);
		Error::log($message, Status::Noobject);
		unset(self::$_container[$name]);
		return null;
	}

	final public function refind(): self {
		$name = $this->getName();
		self::drop($name);

		if (!$refind = self::find($this->_file, $name, true)) {
			self::add($this, $name);
			return $this;
		}

		return $refind;
	}
}
