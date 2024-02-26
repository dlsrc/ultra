<?php declare(strict_types=1);
/**
 * (c) 2005-2024 Dmitry Lebedev <dl@adios.ru>
 * This source code is part of the Ultra library.
 * Please see the LICENSE file for copyright and licensing information.
 */
namespace Ultra;

use Ultra\Generic\Attachable;
use Ultra\Generic\Collector;
use Ultra\Generic\Extendable;
use Ultra\Generic\Getter;
use Ultra\Generic\Immutable;
use Ultra\Generic\Kit;
use Ultra\Generic\NamelessGetter;

abstract class Volume implements Immutable, Attachable, Extendable {
	use Collector;
	use Getter;
	use Kit;
	use NamelessGetter;

	protected function __construct() {
		$this->initialize();
	}
}
