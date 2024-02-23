<?php declare(strict_types=1);
/**
 * (c) 2005-2024 Dmitry Lebedev <dl@adios.ru>
 * This source code is part of the Ultra library.
 * Please see the LICENSE file for copyright and licensing information.
 */
namespace Ultra\Generic;

/**
 * Интерфейс контейнера, значения свойств которого можно менять.
 */
interface Mutable extends Immutable {
	public function __set(string $name, mixed $value): void;
	public function clean(): void;
}
