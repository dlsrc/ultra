<?php declare(strict_types=1);
/**
 * (c) 2005-2023 Dmitry Lebedev <dl@adios.ru>
 * This source code is part of the Ultra package core library.
 * Please see the LICENSE file for copyright and licensing information.
 */
namespace ultra;

/**
 * Стандартная обёртка для валидных значений любых типов.
 * Класс использует для имплементации интерфейса \ultra\Valuable типаж ultra\Wrapper.
 */
final class Value implements Valuable {
	use Wrapper;

	public function __construct(mixed $value) {
		$this->_value = $value;
	}
}
