<?php declare(strict_types=1);
/**
 * (c) 2005-2025 Dmitry Lebedev <dl@adios.ru>
 * This source code is part of the Ultra library.
 * Please see the LICENSE file for copyright and licensing information.
 */
namespace Ultra;

use Ultra\Generic\Container;
use Ultra\Generic\Getter;
use Ultra\Generic\Immutable;

abstract class Kit implements Immutable {
	use Container;
	use Getter;

	public function __construct(array $state) {
		if (!isset($state['_property'])) {
			$this->initialize();
		}
		else {
			$this->_property = $state['_property'];
		}
	}
}
