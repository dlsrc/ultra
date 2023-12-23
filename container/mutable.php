<?php declare(strict_types=1);
/**
 * (c) 2005-2023 Dmitry Lebedev <dl@adios.ru>
 * This source code is part of the Ultra package core library.
 * Please see the LICENSE file for copyright and licensing information.
 */
namespace ultra;

/**
 * Интерфейс контейнера, значения свойств которого можно менять.
 */
interface Mutable extends Immutable {
	public function __set(string $name, mixed $value): void;
	public function clean(): void;
}

/**
 * Реализация интерфейса ultra\Mutable.
 */
trait PropertySetter {
	use PropertyGetter;

	final public function __set(string $name, mixed $value): void {
		if (isset($this->_property[$name])) {
			$this->_property[$name] = $value;
		}
	}

	final public function clean(): void {
		$this->initialize();
	}
}
