<?php declare(strict_types=1);
/**
 * (c) 2005-2023 Dmitry Lebedev <dl@adios.ru>
 * This source code is part of the Ultra package core library.
 * Please see the LICENSE file for copyright and licensing information.
 */
namespace ultra;

use Closure;

/**
 * Типаж для имплементации интерфейса \ultra\Valuable в классах, служащих обёрткой валидным
 * значениям любого типа.
 */
trait Wrapper {
	/**
	 * Поле с действующим значением, которое нужно обернуть в интерфейс Valuable.
	 * Классу использующему типаж нужно в консрукторе заполнить это поле значением.
	 */
	private mixed $_value;

	/**
	 * Так как действительный результат всегда истинный, то возвращается всегда TRUE
	 * (см. ковариантность).
	 */
	public function valid(): true {
		return true;
	}

	/**
	 * Так как действительный результат всегда готов к использованию, то возвращается всегда
	 * сам объект (см. ковариантность).
	 */
	public function call(mixed $default = null): self {
		return $this;
	}

	public function unwrap(mixed $default = null): mixed {
		return $this->_value;
	}

	public function expect(Closure|null $reject = null): mixed {
		return $this->_value;
	}

	/**
	 * Так как код из $reject для действующего результата никогда не будет выполнен, то NULL
	 * никогда не будет возвращен (см. ковариантность).
	 * В данном контексте метод будет синонимом Valuable::follow().
	 */
	public function fetch(Closure|null $resolve = null, Closure|null $reject = null): self {
		return $this->follow($resolve);
	}

	public function follow(Closure|null $resolve = null, Closure|null $reject = null): self {
		if (null === $resolve) {
			return $this;
		}

		return $this->commit($resolve);
	}

	public function commit(Closure $resolve): self {
		if (null === ($result = $resolve($this))) {
			return $this;
		}

		if (is_object($result) && ($result instanceof Valuable)) {
			return $result;
		}

		return new Value($result);
	}

	/**
	 * Поскольку результат действующий, восстановление после ошибки не требуется.
	 * Сразу возвращается сам интерфейс.
	 */
	public function recover(Closure $reject): self {
		return $this;
	}
}
