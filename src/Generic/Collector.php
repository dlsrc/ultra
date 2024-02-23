<?php declare(strict_types=1);
/**
 * (c) 2005-2024 Dmitry Lebedev <dl@adios.ru>
 * This source code is part of the Ultra library.
 * Please see the LICENSE file for copyright and licensing information.
 */
namespace Ultra\Generic;

/**
 * Реализация интерфейса Ultra\Kit\Extendable.
 */
trait Collector {
	abstract protected function getAttachedPropertyHandler(string $property): callable|null;

	final public function attach(Attachable $att, bool $new_only = false): void {
		if ($this instanceof Extendable) {
			$state = $att->getState($this);

			if (empty($state)) {
				return;
			}
		}
		else {
			return;
		}

		foreach ($state as $name => $value) {
			if ($handler = $this->getAttachedPropertyHandler($name)) {
				$this->$name = $handler($this->$name, $value);
				continue;
			}

			if (!$new_only) {
				foreach ($value as $k => $v) {
					$this->$name[$k] = $v;
				}

				continue;
			}

			foreach ($value as $k => $v) {
				$this->$name[$k] ??= $v;
			}
		}
	}

	public function getExpectedProperties(): array {
		return [];
	}
}
