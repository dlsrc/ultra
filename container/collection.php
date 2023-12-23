<?php declare(strict_types=1);
/**
 * (c) 2005-2023 Dmitry Lebedev <dl@adios.ru>
 * This source code is part of the Ultra package core library.
 * Please see the LICENSE file for copyright and licensing information.
 */
namespace ultra;

abstract class Collection implements Immutable, Extendable, Exportable, ImportableNamed {
	use ContainerName;
	use NamedContainer;
	use PropertyGetter;
	use PropertyCollector;
	use OwnExport;

	protected function __construct(array $state = [], string $name = '') {
		$this->_save = Save::Nothing;

		if (empty($state)) {
			if ('' == $name) {
				$this->_name = \get_class($this);
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

			foreach ($state as $name => $value) {
				if (\property_exists($this, $name)) {
					$this->$name = $value;
				}
			}
		}
	}
}
