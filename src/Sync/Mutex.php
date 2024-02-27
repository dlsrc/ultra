<?php declare(strict_types=1);
/**
 * (c) 2005-2024 Dmitry Lebedev <dl@adios.ru>
 * This source code is part of the Ultra library.
 * Please see the LICENSE file for copyright and licensing information.
 */
namespace Ultra\Sync;

use Ultra\Code;
use Ultra\Core;
use Ultra\Error;
use Ultra\IO;
use Ultra\Instance;
use Ultra\State;
use Ultra\Status;

/**
 * Мьютексы на основе эсклюзивно захваченных семафорах, когда \SyncMutex неприменимы.
 */
abstract class Mutex implements State {
	use Instance;

	/**
	 * Создать экземпляр мьютекса (см. Ultra\Mutex::__construct()).
	 */
	abstract protected function create(): void;

	/**
	 * Захватить семафор.
	 * Вернёт TRUE в случае успеха или FALSE, если семафор уже занят.
	 * blocking - флаг блокировки исполнения до захвата семафора текущим процессом.
	 * Эмуляция поведения функции sem_acquire():
	 * Если флаг установлен в TRUE, процесс будет дожидаться возможности захватить семафор,
	 * в противном случае сразу вернется FALSE.
	 */
	abstract public function acquire(bool $blocking=false): bool;

	/**
	 * Освободить семафор.
	 */
	abstract public function release(): bool;

	/**
	 * Удалить семафор.
	 */
	abstract public function remove(): bool;

	/**
	 * Пул семафоров.
	 */
	private static array $semaphore = [];

	/**
	 * Идентификатор семафора.
	 */
	protected int $key;

	/**
	 * Объект экземпляр или имя файла семафора.
	 */
	protected object|string|false $sem;

	/**
	 * Флаг состояния мьютекса относительно текущего процесса
	 * TRUE  - мьютекс эксклюзивно захвачн текущим процессом
	 * FALSE - мьютекс свободен или захвачен другим процессом
	 */
	protected bool $status;

	/**
	 * Получение объекта семафора на основании файлового пути и идентификатора проекта.
	 */
	public static function make(string $filename, string $project_id, bool $danger = false): static|Error {
		if (!file_exists($filename)) {
			return Error::log(IO::message('e_file', $filename), Code::Nofile);
		}

		$key = ftok($filename, $project_id);

		if (-1 == $key) {
			return Error::log(Core::message('e_ftok'), Status::User);
		}

		if (extension_loaded('sysvsem')) {
			return SysVSem::get($key);
		}
		elseif (extension_loaded('shmop')) {
			return Shmop::get($key);
		}
		else {
			$mtx = File::get($key);
			$mtx->setpath(dirname(__DIR__, 3));

			if ($danger) {
				if (!$mtx->pathExists()) {
					$mtx->setpath(dirname(__DIR__, 3), true);

					if (!$mtx->pathExists()) {
						return Error::log(
							Core::message('e_ext', 'process (sysvsem, shmop)'),
							Status::Ext
						);
					}
				}
			}

			return $mtx;
		}
	}

	/**
	 * Получение объекта семафора по ключу System V IPC.
	 */
	public static function get(int $key): static {
		self::$semaphore[$key] ??= new static($key);
		return self::$semaphore[$key];
	}

	/**
	 * Полная принудительная очистка пула семафоров.
	 * Перед выбрасыванием фатальной ошибки необходимо вручную освободить захваченные семафоры
	 * не полагаясь на деструкторы.
	 */
	public static function clean() {
		foreach (self::$semaphore as $sem) {
			$sem->release();
			$sem->remove();
		}
	}

	/**
	 * Удаление объекта семафора из пула.
	 */
	protected static function drop(int $key) {
		unset(self::$semaphore[$key]);
	}

	/**
	 * Защищенный конструктор.
	 */
	protected function __construct(int $key) {
		$this->key = $key;
		$this->create();
	}

	/**
	 * Освобождение семафора при окончании работы процесса.
	 */
	public function __destruct() {
		$this->release();
	}

	/**
	 * Проверить захват семафора текущим процессом.
	 */
	public function isAcquire(): bool {
		return $this->status;
	}
}
