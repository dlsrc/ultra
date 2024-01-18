<?php declare(strict_types=1);
/**
 * (c) 2005-2023 Dmitry Lebedev <dl@adios.ru>
 * This source code is part of the Ultra package core library.
 * Please see the LICENSE file for copyright and licensing information.
 */
namespace ultra;

/**
 * Мьютекс на основе семафоров модуля поддержки функций межпроцессного взаимодействия System V.
 */
final class SysVSemMutex extends Mutex {
	protected function create(): void {
		$this->sem = sem_get($this->key, 1, 0666, true);
		$this->status = false;
	}

	public function acquire(bool $blocking=false): bool {
		if (!$this->status && $this->sem) {
			if ($blocking) {
				if (sem_acquire($this->sem, false)) {
					$this->status = true;
				}
			}
			else {
				if (sem_acquire($this->sem, true)) {
					$this->status = true;
				}
			}
		}

		return $this->status;
	}

	public function release(): bool {
		if ($this->status) {
			if (sem_release($this->sem)) {
				$this->status = false;
				return true;
			}
		}

		return false;
	}

	public function remove(): bool {
		if ($this->status) {
			if (sem_remove($this->sem)) {
				$this->status = false;
				self::drop($this->key);
				return true;
			}
		}

		return false;
	}
}
