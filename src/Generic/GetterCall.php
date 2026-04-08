<?php declare(strict_types=1);
/**
 * (c) 2005-2025 Dmitry Lebedev <dlsrc.extra@gmail.com>
 * This source code is part of the Ultra library.
 * Please see the LICENSE file for copyright and licensing information.
 */
namespace Ultra\Generic;

trait GetterCall {
	abstract public function getProperty(string $name): mixed;

	final public function __call(string $name, array $vars): mixed {
		$property = $this->getProperty($name);

		if (null != $property && isset($vars[0])) {
			return str_replace(
				array_map(fn($key) => '{'.$key.'}', array_keys($vars)),
				$vars,
				$property
			);
		}

		return $property;
	} 
}
