<?php declare(strict_types=1);
/**
 * (c) 2005-2024 Dmitry Lebedev <dl@adios.ru>
 * This source code is part of the Ultra library.
 * Please see the LICENSE file for copyright and licensing information.
 */
namespace Ultra\Generic;

use ReflectionClass;
use Ultra\Lang;
use Ultra\Boot;

/**
 * Реализация интерфейса Ultra\Sociable
 */
trait Informer {
	public static function message(string $code, string...$context): string {
		if (empty($context)) {
			return self::_template($code);
		}

		return str_replace(
			array_map(fn($text) => '{'.$text.'}', array_keys($context)),
			$context,
			self::_template($code)
		);
	}

	public static function pattern(string $code): Template {
		$message = self::_template($code);

		return new class($message) implements Template {
			private string $_tpl;

			public function __construct(string $pattern) {
				$this->_tpl = $pattern;
			}

			public function __toString() {
				return $this->_tpl;
			}

			public function replace(string...$context): string {
				if (empty($context)) {
					return $this->_tpl;
				}

				return str_replace(
					array_map(fn($text) => '{'.$text.'}', array_keys($context)),
					$context,
					$this->_tpl
				);
			}
		};
	}

	public static function info(): Immutable|null {
		$lang  = Lang::main();
		$class = self::_langclass($lang);

		if (class_exists($class, false)) {
			if ($info = self::_instance($class)) {
				return $info;
			}
		}
		elseif ($path = Boot::find($class, false)) {
			if ($info = self::_exists($class, true)) {
				return $info;
			}
		}
		elseif ($lang !== ($def = Lang::getDefaultCase())) {
			$def_class = self::_langclass($def);

			if ($def_path = Boot::find($def_class, false)) {
				$path = str_replace($def->name.'.php', $lang->name.'.php', $def_path);
					
				if (is_readable($path)) {
					//include_once $path;

					if ($info = self::_exists($class, true)) {
						return $info;
					}
				}
				else {
					include_once $def_path;

					if ($info = self::_exists($def_class, false)) {
						return $info;
					}
				}
			}
		}

		if ($info = self::_seek()) {
			return $info;
		}

		return null;
	}

	private static function _instance(string $class): Immutable|null {
		if (is_subclass_of($class, Immutable::class)) {
			$class_ref = new ReflectionClass($class);

			if ($class_ref->hasMethod('get')) {
				$method = $class_ref->getMethod('get');

				if ($method->isPublic() && $method->isStatic()) {
					return $class::get();
				}
			}

			if ($class_ref->hasMethod('__construct')) {
				$method = $class_ref->getMethod('__construct');

				if ($method->isPublic()) {
					$param = $method->getParameters();

					if (isset($param[0]) && $param[0]->hasType() && 'array' == $param[0]->getType()->getName()) {
						return new $class([]);
					}
				}
			}
		}

		return null;
	}

	private static function _exists(string $class, bool $autoload): Immutable|null {
		if (class_exists($class, $autoload)) {
			return self::_instance($class);
		}

		return null;
	}

	private static function _seek(): Immutable|null {
		$lang = Lang::main();
		$def  = Lang::getDefaultCase();

		foreach (Lang::cases() as $case) {
			if ($case == $lang || $case == $def) {
				continue;
			}

			$class = self::_langclass($case);

			if ($path = Boot::find($class, false)) {
				include_once $path;

				if ($info = self::_exists($class, false)) {
					return $info;
				}
			}
		}

		return null;
	}

	private static function _template(string $code): string {
		if (!$container = self::info()) {
			return '';
		}

		if (!$message = $container->$code) {
			return '';
		}

		return $message;
	}

	private static function _langclass(Lang $lang): string {
		if (false === ($pos = strrpos(self::class, '\\'))) {
			return 'Lang\\'.$lang->name.'\\'.self::class;
		}
		else {
			return substr(self::class, 0, $pos).'\\Lang\\'.$lang->name.strrchr(self::class, '\\');
		}
	}
}
