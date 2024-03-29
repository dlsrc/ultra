<?php declare(strict_types=1);
/**
 * (c) 2005-2024 Dmitry Lebedev <dl@adios.ru>
 * This source code is part of the Ultra library.
 * Please see the LICENSE file for copyright and licensing information.
 */
namespace Ultra\Generic;

/**
 * Реализация интерфейса Ultra\Generic\Comparable.
 */
trait Comparison {
	final public function isCompatible(Immutable $getter, string $property = '_property', bool $by_vals = false): bool {
		if (!property_exists($this, $property)) {
			return false;
		}

		if ($by_vals) {
			foreach ($this->$property as $name => $value) {
				if (!isset($getter->$name)) {
					return false;
				}

				if ($getter->$name !== $value) {
					return false;
				}
			}
		}
		else {
			foreach (array_keys($this->$property) as $name) {
				if (!isset($getter->$name)) {
					return false;
				}
			}
		}

		return true;
	}

	public function isEqual(Comparable & Immutable $with, string $property = '_property', bool $by_vals = false): bool {
		if (!$this->isCompatible($with, $property, $by_vals)) {
			return false;
		}

		if (!$with->isCompatible($this, $property, $by_vals)) {
			return false;
		}

		return true;
	}
}
