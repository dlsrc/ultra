<?php declare(strict_types=1);
/**
 * (c) 2005-2023 Dmitry Lebedev <dl@adios.ru>
 * This source code is part of the Ultra package core library.
 * Please see the LICENSE file for copyright and licensing information.
 */
namespace ultra;

/**
 * Интерфейс контейнера, значения свойств которого запрещено менять.
 */
interface Immutable {
	public function __get(string $name): mixed;
	public function __isset(string $name): bool;
}

/**
 * Реализация интерфейса ultra\Immutable.
 */
trait PropertyGetter {
	abstract protected function initialize(): void;
	protected array $_property = [];

	final public function __get(string $name): mixed {
		if (isset($this->_property[$name])) {
			return $this->_property[$name];
		}

		return null;
	}

	final public function __isset(string $name): bool {
		return isset($this->_property[$name]);
	}
}

trait PropertyGetterCall {
	final public function __call(string $name, array $vars): mixed {
		if (isset($this->_property[$name])) {
			if (isset($vars[0])) {
				return str_replace(
					array_map(fn($key) => '{'.$key.'}', array_keys($vars)),
					$vars,
					$this->_property[$name]
				);
			}
			
			return $this->_property[$name];
		}

		return null;
	} 
}
