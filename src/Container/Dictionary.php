<?php declare(strict_types=1);
/**
 * (c) 2005-2024 Dmitry Lebedev <dl@adios.ru>
 * This source code is part of the Ultra library.
 * Please see the LICENSE file for copyright and licensing information.
 */
namespace Ultra;

use Ultra\Generic\Called;
use Ultra\Generic\Getter;
use Ultra\Generic\Immutable;
use Ultra\Generic\ImportableNamed;
use Ultra\Generic\Name;
use Ultra\Generic\Named;
use Ultra\Generic\Storable;

abstract class Dictionary implements Called, Exportable, Storable, Immutable, ImportableNamed {
	use Getter;
	use Name;
	use Named;
	use Replica;

	protected function __construct(array $state = [], string $name = '') {
		if (empty($state)) {
			if ('' == $name) {
				$this->_name = get_class($this);
			}
			else {
				$this->_name = $name;
			}

			$this->initialize();
		}
		else {
			$this->_name = $state['_name'];
			$this->_file = $state['_file'];
			$this->_property = $state['_property'];
		}
	}
}
