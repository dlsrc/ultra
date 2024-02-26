<?php declare(strict_types=1);
/**
 * (c) 2005-2024 Dmitry Lebedev <dl@adios.ru>
 * This source code is part of the Ultra library.
 * Please see the LICENSE file for copyright and licensing information.
 */
namespace Ultra\Sync;

use Ultra\Code;
use Ultra\Error;
use Ultra\Exporter;
use Ultra\IO;

/**
 * Файловый эмулятор мьютекса. Использовать, если недоступны функции межпроцессного
 * взаимодействия System V и недоступно расширение доступа к сегментам разделяемой памяти.
 */
final class File extends Mutex {
	protected function create(): void {
		$this->setpath(__DIR__);
	}

	/**
	 * Установить или изменить путь до папки /.semaphore.
	 * По умолчанию, устанавливается путь до папки текущего файла.
	 */
	public function setpath(string $path, bool $check = false): void {
		$path = \strtr($path, '\\', '/');

		if (!str_ends_with($path, '/')) {
			$path.= '/';
		}

		if ($check) {
			$this->sem = $path.$this->key.'.php';
		}
		else {
			$this->sem = $path.'.mutex/'.$this->key.'.php';
		}

		$this->status = false;
	}

	/**
	 * Проверить существование пути да файла семафора.
	 * Проверка нужна для семафоров, путь к файлам которых, должен существовать,
	 * а создание папки приводит к ошибке или непредсказуемому результату (например, в Ultra\IO::isdir()).
	 */
	public function pathExists(): bool {
		return is_dir(substr($this->sem, 0, strrpos($this->sem, '/')));
	}

	/**
	 * Захватить семафор.
	 * Вернуть TRUE в случае успеха или FALSE, если семафор уже занят.
	 * blocking - флаг блокировки исполнения до захвата семафора текущим процессом.
	 * Эмуляция поведения функции sem_acquire():
	 * Если флаг установлен в TRUE, процесс будет дожидаться возможности захватить семафор,
	 * в противном случае сразу вернется FALSE.
	 */
	public function acquire(bool $blocking=false): bool {
		if ($this->status) {
			return true;
		}

		if (!file_exists($this->sem)) {
			if ((new Exporter($this->sem))->save(true, '', false)) {
				$this->status = true;
			}
		}
		else {
			$busy = include $this->sem;

			if ($blocking) {
				set_time_limit(0);

				while ($busy) {
					sleep(1);
					$busy = include $this->sem;
				}
			}
			elseif ($busy) {
				return false;
			}

			if ((new Exporter($this->sem))->save(true, '', false)) {
				$this->status = true;
			}
		}

		return $this->status;
	}

	/**
	 * Освободить семафор.
	 */
	public function release(): bool {
		if ($this->status) {
			if ((new Exporter($this->sem))->save(false, '', false)) {
				$this->status = false;
				return true;
			}
		}

		return false;
	}

	/**
	 * Удалить ыайл семафора.
	 */
	public function remove(): bool {
		if ($this->status) {
			if (!unlink($this->sem)) {
				Error::log(IO::message('e_unlink', $this->sem), Code::Unlink);
				return false;
			}

			$this->status = false;
			self::drop($this->key);
			return true;
		}

		return false;
	}
}
