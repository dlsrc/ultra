<?php declare(strict_types=1);
/**
 * (c) 2005-2024 Dmitry Lebedev <dl@adios.ru>
 * This source code is part of the Ultra library.
 * Please see the LICENSE file for copyright and licensing information.
 */
namespace Ultra\Container;

use Ultra\Export\Exportable;
use Ultra\Export\Replica;
use Ultra\Export\Save;
use Ultra\Generic\Called;
use Ultra\Generic\Getter;
use Ultra\Generic\Immutable;
use Ultra\Generic\ImportableNamed;
use Ultra\Generic\Name;
use Ultra\Generic\Named;

abstract class Dictionary implements Named, Exportable, Storable, Immutable, ImportableNamed {
	use Getter;
	use Name;
	use Named;
	use Replica;

	protected function __construct(array $state = [], string $name = '') {
		$this->_save = Save::Nothing;

		if (empty($state)) {
			if ('' == $name) {
				$this->_name = get_class($this);
			}
			else {
				$this->_name = $name;
			}

			$this->_file = '';
			$this->initialize();
		}
		else {
			$this->_name = $state['_name'];
			$this->_file = $state['_file'];
			$this->_property = $state['_property'];
		}
	}
}
