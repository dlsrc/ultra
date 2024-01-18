<?php declare(strict_types=1);
/**
 * (c) 2005-2023 Dmitry Lebedev <dl@adios.ru>
 * This source code is part of the Ultra package core library.
 * Please see the LICENSE file for copyright and licensing information.
 */
namespace ultra;

trait NamelessContainerGetter {
	use PropertyContainer;

	final public static function get(): static {
		self::$_container[static::class] ??= new static;
		return self::$_container[static::class];
	}
}

interface NamelessImportable {
	public static function load(string $file): static;
	public static function find(string $file): static|null;
}

trait NamelessContainer {
	use NamelessContainerGetter;

	final public static function load(string $file): static {
		if (isset(self::$_container[static::class])) {
			return self::$_container[static::class];
		}

		if (is_readable($file)) {
			self::$_container[static::class] = @include $file;

			if (self::$_container[static::class] instanceof static) {
				return self::$_container[static::class];
			}

			if (!is_object(self::$_container[static::class])) {
				unlink($file);
				Error::log(
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
				Error::log(
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

	final public static function find(string $file): static|null {
		if (isset(self::$_container[static::class])) {
			return self::$_container[static::class];
		}

		if (!is_readable($file)) {
			return null;
		}

		self::$_container[static::class] = @include $file;

		if (self::$_container[static::class] instanceof static) {
			return self::$_container[static::class];
		}

		unset(self::$_container[static::class]);
		return null;
	}
}