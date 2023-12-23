<?php declare(strict_types=1);
/**
 * (c) 2005-2023 Dmitry Lebedev <dl@adios.ru>
 * This source code is part of the Ultra package core library.
 * Please see the LICENSE file for copyright and licensing information.
 */
namespace ultra;

/**
 * Интерфейс объекта способного быть вызванным после экспорта.
 */
interface CallableState {
	public static function __set_state(array $state): static;
}

/**
 * Основная реализация интерфейса ultra\CallableState.
 */
trait SetStateCall {
	final public static function __set_state(array $state): static {
		return new static($state);
	}
}
