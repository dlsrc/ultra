<?php declare(strict_types=1);
/**
 * (c) 2005-2023 Dmitry Lebedev <dl@adios.ru>
 * This source code is part of the Ultra package core library.
 * Please see the LICENSE file for copyright and licensing information.
 */
namespace ultra;

/**
 * Класс заменяющий стандартную обёртку.
 * Обёртка порождается другим экземпляром интерфейса Valuable, для замены ошибочных или пустых
 * и недействительных значений значениями по умолчанию, либо в случае ошибки результатом её
 * обработки.
 * Экземпляры класса содержат в себе ссылку на породжающий интерфейс.
 * Класс использует для имплементации интерфейса \ultra\Valuable типаж ultra\Wrapper.
 */
final class Substitute implements Valuable {
	use Wrapper;

	/**
	 * Аргумент $previous должен содержать объект интерфейса Valuable, который инстанцировал
	 * экземпляр класса.
	 */
	public readonly Valuable $previous;

	public function __construct(mixed $value, Valuable $previous) {
		$this->_value = $value;
		$this->previous = $previous;
	}
}
