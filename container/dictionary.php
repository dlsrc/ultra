<?php declare(strict_types=1);
/**
 * (c) 2005-2023 Dmitry Lebedev <dl@adios.ru>
 * This source code is part of the Ultra package core library.
 * Please see the LICENSE file for copyright and licensing information.
 */
namespace ultra;

abstract class Dictionary implements CallableState, Exportable, Immutable, ImportableNamed, Named, Storable {
	use ContainerName;
	use NamedContainer;
	use PropertyGetter;
	use OwnExport;

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
