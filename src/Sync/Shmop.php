<?php declare(strict_types=1);
/**
 * (c) 2005-2025 Dmitry Lebedev <dl@adios.ru>
 * This source code is part of the Ultra library.
 * Please see the LICENSE file for copyright and licensing information.
 */
namespace Ultra\Sync;

/**
 * Эмулятор мьютекса на основе доступа к сегментам разделяемой памяти. Использовать, если
 * недоступны функции межпроцессного взаимодействия System V.
 */
final class Shmop extends Mutex {
	protected function create(): void {
		$this->status = false;

		if (!$this->sem = shmop_open($this->key, 'c', 0666, 1)) {
			return;
		}

		$data = shmop_read($this->sem, 0, 1);

		if ('a' != $data && 'f' != $data) {
			if (1 == shmop_write($this->sem, 'a', 0)) {
				$this->status = true;
			}
		}
	}

	public function acquire(bool $blocking=false): bool {
		if ($this->status) {
			return true;
		}

		if (!$this->sem) {
			return false;
		}

		if ($blocking) {
			set_time_limit(0);

			while ('a' == shmop_read($this->sem, 0, 1)) {
				sleep(1);
			}

			if (1 == shmop_write($this->sem, 'a', 0)) {
				$this->status = true;
			}
		}
		elseif ('f' == shmop_read($this->sem, 0, 1)) {
			if (1 == shmop_write($this->sem, 'a', 0)) {
				$this->status = true;
			}
		}

		return $this->status;
	}

	public function release(): bool {
		if ($this->status) {
			if (1 == shmop_write($this->sem, 'f', 0)) {
				$this->status = false;
				return true;
			}
		}

		return false;
	}

	public function remove(): bool {
		if ($this->status) {
			if (shmop_delete($this->sem)) {
				$this->status = false;
				self::drop($this->key);
				return true;
			}
		}

		return false;
	}
}
