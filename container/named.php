<?php declare(strict_types=1);
/**
 * (c) 2005-2023 Dmitry Lebedev <dl@adios.ru>
 * This source code is part of the Ultra package core library.
 * Please see the LICENSE file for copyright and licensing information.
 */
namespace ultra;

/**
 * Интерфейс именованного контейнера.
 */
interface Named {
	public function getName(): string;
}

/**
 * Общая реализация интерфейса ultra\Named.
 */
trait ContainerName {
	private string $_name;

	public function getName(): string {
		return $this->_name;
	}
}

trait NamedContainerGetter {
	use PropertyContainer;

	final public static function get(string $name = ''): static {
		if ('' == $name) {
			$name = static::class;
		}

		if (!isset(self::$_container[$name])) {
			if (\is_subclass_of(static::class, 'ultra\\Named')) {
				self::$_container[$name] = new static([], $name);
			}
			else {
				self::$_container[$name] = new static;
			}
		}

		return self::$_container[$name];
	}
}

interface ImportableNamed {
	public static function load(string $file, string $name = ''): static;
	public static function find(string $file, string $name = ''): static|null;
}

trait NamedContainer {
	use NamedContainerGetter;
	
	final public static function load(string $file, string $name = ''): static {
		if ('' == $name) {
			$name = static::class;
		}

		if (isset(self::$_container[$name])) {
			return self::$_container[$name];
		}

		if (\is_readable($file)) {
			self::$_container[$name] = @include $file;

			if (self::$_container[$name] instanceof static) {
				return self::$_container[$name];
			}

			if (!\is_object(self::$_container[$name])) {
				\unlink($file);
				Error::log(
					Core::message('e_type', $file, \gettype(self::$_container[$name])),
					Status::Domain
				);
			}
			else {
				\unlink($file);
				Error::log(
					Core::message(
						'e_class',
						$file,
						\get_class(self::$_container[$name]),
						static::class
					),
					Status::Domain
				);
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

	final public static function find(string $file, string $name = ''): static|null {
		if ('' == $name) {
			$name = static::class;
		}

		if (isset(self::$_container[$name])) {
			return self::$_container[$name];
		}

		if (!\is_readable($file)) {
			return null;
		}

		self::$_container[$name] = @include $file;

		if (self::$_container[$name] instanceof static) {
			return self::$_container[$name];
		}

		unset(self::$_container[$name]);
		return null;
	}
}
