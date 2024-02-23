<?php declare(strict_types=1);
/**
 * (c) 2005-2024 Dmitry Lebedev <dl@adios.ru>
 * This source code is part of the Ultra library.
 * Please see the LICENSE file for copyright and licensing information.
 */
namespace Ultra\Generic;

trait GetterCall {
	final public function __call(string $name, array $vars): mixed {
		if (isset($this->_property[$name])) {
			if (isset($vars[0])) {
				return str_replace(
					array_map(fn($key) => '{'.$key.'}', array_keys($vars)),
					$vars,
					$this->_property[$name]
				);
			}
			
			return $this->_property[$name];
		}

		return null;
	} 
}
