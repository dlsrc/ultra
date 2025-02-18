<?php declare(strict_types=1);
/**
 * (c) 2005-2025 Dmitry Lebedev <dl@adios.ru>
 * This source code is part of the Ultra library.
 * Please see the LICENSE file for copyright and licensing information.
 */
namespace Ultra\Generic;

/**
 * Интерфейс, состав свойств которого можно расширять свойствами другого контейнера,
 * реализующего интерфейс Ultra\Generic\Attachable.
 */
interface Extendable {
	public function attach(Attachable $att, bool $new_only = false): void;
	public function getExpectedProperties(): array;
}
