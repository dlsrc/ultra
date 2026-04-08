<?php declare(strict_types=1);
/**
 * (c) 2005-2026 Dmitry Lebedev <dlsrc.extra@gmail.com>
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
