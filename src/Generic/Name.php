<?php declare(strict_types=1);
/**
 * (c) 2005-2025 Dmitry Lebedev <dlsrc.extra@gmail.com>
 * This source code is part of the Ultra library.
 * Please see the LICENSE file for copyright and licensing information.
 */
namespace Ultra\Generic;

/**
 * Общая реализация интерфейса Ultra\Generic\Called.
 */
trait Name {
	private string $_name;

	public function getName(): string {
		return $this->_name;
	}
}
