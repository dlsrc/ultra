<?php declare(strict_types=1);
/**
 * (c) 2005-2024 Dmitry Lebedev <dl@adios.ru>
 * This source code is part of the Ultra library.
 * Please see the LICENSE file for copyright and licensing information.
 */
namespace Ultra\Generic;

/**
 * Общая реализация интерфейса Ultra\Container\Named.
 */
trait Name {
	private string $_name;

	public function getName(): string {
		return $this->_name;
	}
}
