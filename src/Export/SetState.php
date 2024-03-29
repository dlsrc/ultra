<?php declare(strict_types=1);
/**
 * (c) 2005-2024 Dmitry Lebedev <dl@adios.ru>
 * This source code is part of the Ultra library.
 * Please see the LICENSE file for copyright and licensing information.
 */
namespace Ultra;

/**
 * Реализация по умолчанию для интерфейса Ultra\CallableState.
 */
trait SetState {
	final public static function __set_state(array $state): static {
		return new static($state);
	}
}
