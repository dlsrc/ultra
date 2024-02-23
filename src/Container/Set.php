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
use Ultra\Generic\ImportableNameless;
use Ultra\Generic\Mutable;
use Ultra\Generic\Nameless;
use Ultra\Generic\Setter;

abstract class Set implements Mutable, Exportable, Storable, ImportableNameless {
	use Setter;
	use Replica;
	use Nameless;

	protected function __construct(array $state = []) {
		$this->_save = Save::Nothing;

		if (empty($state)) {
			$this->_file = '';
			$this->initialize();
		}
		else {
			$this->_file = $state['_file'];
			$this->_property = $state['_property'];
		}
	}
}
