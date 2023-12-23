<?php declare(strict_types=1);
/**
 * (c) 2005-2023 Dmitry Lebedev <dl@adios.ru>
 * This source code is part of the Ultra package core library.
 * Please see the LICENSE file for copyright and licensing information.
 */
namespace ultra;

/**
 * Типаж для прямой имплементации интерфейса ultra\Valuable в классы ошибочных, неожиданных
 * и неопределённых состояний.
 */
trait Suspense {
	/**
	 * Так как ошибочный результат всегда ложный, то возвращается всегда FALSE
	 * (см. ковариантность).
	 */
	public function valid(): false {
		return false;
	}

	public function call(mixed $default = null): self|null {
		if (null === $default) {
			return null;
		}
		
		if (is_object($default) && ($default instanceof Valuable)) {
			return $default;
		}

		return new Substitute($default, $this);
	}

	/**
	 * При ошибке всегда возвращается значение по умолчанию.
	 */
	public function unwrap(mixed $default = null): mixed {
		return $default;
	}

	public function expect(\Closure|null $reject = null): mixed {
		if (null === $reject) {
			return null;
		}

		return $reject($this);
	}

	public function fetch(\Closure|null $resolve = null, \Closure|null $reject = null): self|null {
		if (null === $reject && null === ($result = $reject($this))) {
			return null;
		}

		if (is_object($result) && ($result instanceof Valuable)) {
			return $result;
		}

		return new Substitute($result, $this);
	}

	public function follow(\Closure|null $resolve = null, \Closure|null $reject = null): self {
		if (null === $reject) {
			return $this;
		}

		return $this->recover($reject);
	}

	/**
	 * Поскольку результат ошибочный, совершать любые действия с ним бессмысленно.
	 * Сразу возвращается сам интерфейс.
	 */
	public function commit(\Closure $resolve): self {
		return $this;
	}

	public function recover(\Closure $reject): self {
		if (null === ($result = $reject($this))) {
			return $this;
		}

		if (is_object($result) && ($result instanceof Valuable)) {
			return $result;
		}

		return new Substitute($result, $this);
	}
}
