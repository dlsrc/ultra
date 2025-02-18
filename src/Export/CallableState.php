<?php declare(strict_types=1);
/**
 * (c) 2005-2025 Dmitry Lebedev <dl@adios.ru>
 * This source code is part of the Ultra library.
 * Please see the LICENSE file for copyright and licensing information.
 */
namespace Ultra;

/**
 * Интерфейс объекта способного быть вызванным после экспорта.
 */
interface CallableState {
	public static function __set_state(array $state): static;
}
