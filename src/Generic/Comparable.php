<?php declare(strict_types=1);
/**
 * (c) 2005-2025 Dmitry Lebedev <dl@adios.ru>
 * This source code is part of the Ultra library.
 * Please see the LICENSE file for copyright and licensing information.
 */
namespace Ultra\Generic;

interface Comparable {
	public function isCompatible(Immutable $getter, string $property = '_property', bool $by_vals = false): bool;
	public function isEqual(Comparable & Immutable $with, string $property = '_property', bool $by_vals = false): bool;
}
