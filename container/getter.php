<?php declare(strict_types=1);
/**
 * (c) 2005-2023 Dmitry Lebedev <dl@adios.ru>
 * This source code is part of the Ultra package core library.
 * Please see the LICENSE file for copyright and licensing information.
 */
namespace ultra;

abstract class Getter implements Immutable {
	use NamelessContainerGetter;
	use PropertyGetter;

	protected function __construct() {
		$this->initialize();
	}
}
