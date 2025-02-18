<?php declare(strict_types=1);
/**
 * (c) 2005-2025 Dmitry Lebedev <dl@adios.ru>
 * This source code is part of the Ultra library.
 * Please see the LICENSE file for copyright and licensing information.
 */
namespace Ultra\Generic;

/**
 * Интерфейс контейнера, чьи свойства способны присоединяться к свойствам другого,
 * расширяемого контейнера (см. Ultra\Generic\Extendable).
 */
interface Attachable {
	public function getState(Extendable $ext): array;
}
