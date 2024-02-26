<?php declare(strict_types=1);
/**
 * (c) 2005-2024 Dmitry Lebedev <dl@adios.ru>
 * This source code is part of the Ultra library.
 * Please see the LICENSE file for copyright and licensing information.
 */
namespace Ultra\Generic;

/**
 * Реализация интерфейса Ultra\Generic\Immutable.
 */
trait Getter {
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
