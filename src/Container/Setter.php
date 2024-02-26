<?php declare(strict_types=1);
/**
 * (c) 2005-2024 Dmitry Lebedev <dl@adios.ru>
 * This source code is part of the Ultra library.
 * Please see the LICENSE file for copyright and licensing information.
 */
namespace Ultra;

use Ultra\Generic\Mutable;
use Ultra\Generic\NamelessGetter;
use Ultra\Generic\Setter as MutableImpl;

abstract class Setter implements Mutable {
	use NamelessGetter;
	use MutableImpl;

	protected function __construct() {
		$this->initialize();
	}
}
