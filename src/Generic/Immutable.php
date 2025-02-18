<?php declare(strict_types=1);
/**
 * (c) 2005-2025 Dmitry Lebedev <dl@adios.ru>
 * This source code is part of the Ultra library.
 * Please see the LICENSE file for copyright and licensing information.
 */
namespace Ultra\Generic;

/**
 * Интерфейс контейнера, значения свойств которого запрещено менять.
 */
interface Immutable {
	public function __get(string $name): mixed;
	public function __isset(string $name): bool;
}
