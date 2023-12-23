<?php declare(strict_types=1);
/**
 * (c) 2005-2023 Dmitry Lebedev <dl@adios.ru>
 * This source code is part of the Ultra package core library.
 * Please see the LICENSE file for copyright and licensing information.
 */
namespace ultra;

abstract class Kit implements Immutable {
	use PropertyContainer;
	use PropertyGetter;

	public function __construct(array $state) {
		if (!isset($state['_property'])) {
			$this->initialize();
		}
		else {
			$this->_property = $state['_property'];
		}
	}
}
