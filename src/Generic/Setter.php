<?php declare(strict_types=1);
/**
 * (c) 2005-2024 Dmitry Lebedev <dl@adios.ru>
 * This source code is part of the Ultra library.
 * Please see the LICENSE file for copyright and licensing information.
 */
namespace Ultra\Generic;

/**
 * Реализация интерфейса Ultra\Generic\Mutable.
 */
trait Setter {
	use Getter;

	final public function __set(string $name, mixed $value): void {
		if (isset($this->_property[$name])) {
			$this->_property[$name] = $value;
		}
	}

	final public function clean(): void {
		$this->initialize();
	}
}
