<?php declare(strict_types=1);
/**
 * (c) 2005-2024 Dmitry Lebedev <dl@adios.ru>
 * This source code is part of the Ultra library.
 * Please see the LICENSE file for copyright and licensing information.
 */
namespace Ultra;

use Ultra\Generic\Getter as ImmutableImpl;
use Ultra\Generic\Immutable;
use Ultra\Generic\NamelessGetter;

abstract class Getter implements Immutable {
	use NamelessGetter;
	use ImmutableImpl;

	protected function __construct() {
		$this->initialize();
	}
}
