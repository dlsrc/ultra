<?php declare(strict_types=1);
/**
 * (c) 2005-2023 Dmitry Lebedev <dl@adios.ru>
 * This source code is part of the Ultra package core library.
 * Please see the LICENSE file for copyright and licensing information.
 */
namespace ultra;

final class Log {
	private static self|null $_log = null;
	
	private array $_error;
	private array|null $_saved;
	private string $_last;
	private string $_added;

	public static function get(): self {
		self::$_log ??= new Log;
		return self::$_log;
	}

	public static function makeId(array $trace): string {
		$errno = $trace['errno'] ?? '';
		$file  = $trace['file']  ?? '';
		$line  = $trace['line']  ?? '';
		return \md5($errno.'::'.$file.'::'.$line);
	}

	public function __destruct() {
		if (empty($this->_error)) {
			return;
		}

		$core = Core::get();
		$file = $core->getLogfile();

		if (\is_file($file)) {
			if (!$core->logable() || !\is_writable($file)) {
				return;
			}

			if (!$this->loaded($file)) {
				return;
			}

			if (!$this->refresh()) {
				if (!Mode::Develop->current()) {
					return;
				}
			}
		}
		else {
			$this->_saved = $this->_error;
		}

		$mode = Mode::Develop->prefer();

		(new Exporter($file))->save(
			$this->_saved,
			Core::message(
				'src_header',
				'Error log: '.$file,
				\date('Y'),
				\date('Y-m-d H:i:s'),
				\PHP_MAJOR_VERSION.'.'.\PHP_MINOR_VERSION
			)
		);

		$mode->prefer();
	}

	public function getKeys(): array {
		return \array_keys($this->_error);
	}

	public function exists(string $id): bool {
		return isset($this->_error[$id]);
	}

	public function size(): int {
		return \count($this->_error);
	}

	public function addError(Error $e, bool $replace = false): void {
		if (!isset($this->_error[$e->id]) || $replace) {
			$this->_error[$e->id] = $e;
			$this->_added = $e->id;
		}

		$this->_last = $e->id;
		Core::get()->catchError($e);
	}

	public function getError(string $id): Error|null {
		if (isset($this->_error[$id])) {
			return $this->_error[$id];
		}

		return null;
	}

	public function getType(string $id): Condition {
		if (isset($this->_error[$id])) {
			return $this->_error[$id]->type;
		}

		return Status::Success;
	}

	public function lastId(bool $added = false): string {
		if ($added) {
			return $this->_added;
		}

		return $this->_last;
	}

	public function last(bool $added = false): Error|null {
		if ($added) {
			if (!isset($this->_error[$this->_added])) {
				return null;
			}

			return $this->_error[$this->_added];
		}

		if (!isset($this->_error[$this->_last])) {
			return null;
		}

		return $this->_error[$this->_last];
	}

	public function lastType(bool $added = false): Condition {
		if (!$error = $this->last($added)) {
			return Status::Success;
		}

		return $error->type;
	}

	public function lastMessage(bool $added = false): string {
		if (!$error = $this->last($added)) {
			return '';
		}

		return $error->message;
	}

	public function getSavedKeys(): array {
		if (!$this->loaded(Core::get()->getLogfile())) {
			return [];
		}

		$this->refresh();
		return \array_keys($this->_saved);
	}

	private function loaded($file): bool {
		if (\is_null($this->_saved)) {
			if (!\is_readable($file)) {
				return false;
			}

			$log = @include $file;

			if (!\is_array($log) || empty($log)) {
				$this->_saved = $this->_error;
			}
			else {
				$this->_saved = $log;
			}
		}

		return true;
	}

	private function refresh(): bool {
		$new = false;

		foreach (\array_keys($this->_error) as $id) {
			if (!isset($this->_saved[$id])
				|| (Mode::Develop->current()
					&& $this->_saved[$id]->context != $this->_error[$id]->context
				)
			) {
				$this->_saved[$id] = $this->_error[$id];
				$new = true;
			}
		}

		return $new;
	}

	private function __construct() {
		$this->_error = [];
		$this->_saved = null;
		$this->_added  = '';
		$this->_last   = '';
	}
}
