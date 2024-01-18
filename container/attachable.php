<?php declare(strict_types=1);
/**
 * (c) 2005-2023 Dmitry Lebedev <dl@adios.ru>
 * This source code is part of the Ultra package core library.
 * Please see the LICENSE file for copyright and licensing information.
 */
namespace ultra;

/**
 * Интерфейс контейнера, чьи свойства способны присоединяться к свойствам другого,
 * расширяемого контейнера (см. ultra\Extendable).
 */
interface Attachable {
	public function getState(Extendable $ext): array;
}

/**
 * Общая реализация интерфейса ultra\Attachable.
 */
trait PropertyKit {
	final public function getState(Extendable $ext): array {
		$vars = get_object_vars($this);
		$expect = $ext->getExpectedProperties();

		if (empty($expect)) {
			$expect = array_keys($vars);
		}

		foreach(array_keys($vars) as $name) {
			if (!is_array($vars[$name]) || empty($vars[$name]) || !in_array($name, $expect)) {
				unset($vars[$name]);
			}
		}

		return $vars;
	}
}
