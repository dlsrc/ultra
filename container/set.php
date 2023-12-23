<?php declare(strict_types=1);
/**
 * (c) 2005-2023 Dmitry Lebedev <dl@adios.ru>
 * This source code is part of the Ultra package core library.
 * Please see the LICENSE file for copyright and licensing information.
 */
namespace ultra;

abstract class Set implements Mutable, Exportable, NamelessImportable {
	use NamelessContainer;
	use PropertySetter;
	use OwnExport;

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
