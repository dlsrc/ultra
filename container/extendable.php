<?php declare(strict_types=1);
/**
 * (c) 2005-2023 Dmitry Lebedev <dl@adios.ru>
 * This source code is part of the Ultra package core library.
 * Please see the LICENSE file for copyright and licensing information.
 */
namespace ultra;

/**
 * Интерфейс, состав свойств которого можно расширять свойствами другого контейнера,
 * реализующего интерфейс ultra\Attachable.
 */
interface Extendable {
	public function attach(Attachable $att, bool $new_only = false): void;
	public function getExpectedProperties(): array;
}

/**
 * Наиболее общая реализация интерфейса ultra\Extendable.
 */
trait PropertyCollector {
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
