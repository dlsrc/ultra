<?php declare(strict_types=1);
/**
 * (c) 2005-2024 Dmitry Lebedev <dl@adios.ru>
 * This source code is part of the Ultra library.
 * Please see the LICENSE file for copyright and licensing information.
 */
namespace Ultra;

use Closure;
use Error as InternalError;
use ErrorException;
use Throwable;
use Ultra\Generic\Informer;
use Ultra\Generic\Sociable;

final class Core implements Sociable {
	use Singleton;
	use Informer;

	/**
	 * Флаг консольного приложения.
	 */
	public readonly bool $cli;

	/**
	 * Символ конца строки для CLI приложений в зависимости от платформы
	 */
	public readonly string $eol;

	/**
	 * Флаг расширения OPcache.
	 * Если расширение OPcache загружено и активно, флаг устанавливается в TRUE, иначе FALSE.
	 */
	public readonly bool $opcache;

	/**
	 * Объект, управляющий завершающими функциями и инициализируемый по требованию.
	 */
	public private(set) ?Shutdown $shutdown = null {
		get => $this->shutdown ?? ($this->shutdown = new Shutdown);
	}

	/**
	 * Флаг состояния сбоя. Значение по умолчанию FALSE.
	 * Флаг со значением TRUE блокирует экспорт объектов с интерфейсом Ultra\Exportable, для
	 * которых параметр приоритета сохранения установлен в Ultra\Save::NoError.
	 * Сбои не обязательно фиксировать в журнале событий, но о них можно сообщать методу
	 * Ultra\Core->registerFailure().
	 */
	private bool $_failure;

	/**
	 * Путь к исполняемому файлу журнала.
	 * Исполняемый файл PHP, содержащий экспортированный массив объектов ошибок и сообщений.
	 * Массив объектов ошибок можно присвоить переменной, подключив файл журнала директивой
	 * include:
	 * $log = include $this->_logfile;
	 */
	private string $_logfile;

	/**
	 * Частота сверки журнала событий в случае ошибок.
	 * По умолчанию каждый ошибочный случай однократно фиксируется в журнале событий,
	 * но поскольку в приложении для веб раз за разом каждый запрос будет генерировать одно и то
	 * же сообщение или предупреждение, нет необходмиости перечитывать и перезаписывать журнал
	 * при каждом ошибочном запросе и проверять попало оно в журнал или ещё нет.
	 * Параметр $_frequency устанавливает частоту обращения к журналу событий и сверки с ним.
	 * Значение "1" - устанавливается по умолчанию и означает, что каждый запуск приложения
	 * в случае ошибки будет проверять журнал событий и добавлять в него сообщение, если такого
	 * события ещё не фиксировалось.
	 * Это значение можно увеличить от "2" до "1000", тем самым сократив частоту перечитывания
	 * журнала событий.
	 * Не актуально в консольных приложениях и при работе в режиме разработки, когда флаг
	 * \Ultra\Mode::Develop указан в качестве главного. Сверка с журналом событий в этом
	 * случае будет происходить при каждом возникновении ошибки.
	 */
	private int $_frequency;

	/**
	 * Файл шаблона заголовков для генерируемых скриптов и экспортируемых переменных.
	 */
	private string $_header;

	/**
	 * Выполняет сброс кеша байткода указанного скрипта или переменной, если скрипт или
	 * переменная были ранее сохранены и закешированы.
	 */
	public function invalidate(string $file): void {
		if ($this->opcache && is_file($file) && opcache_is_script_cached($file)) {
			opcache_invalidate($file);
		}
	}

	/**
	 * Завершить выполнение программы, приводя последнюю зарегистрированную ошибку к статусу
	 * фатальных. Если ошибки отсутствуют, генерирует новую фатальную.
	 */
	public static function halt(): never {
		if (!$error = Log::get()->last()) {
			Error::log(
				message: 'Unexpected program termination.',
				type: Status::Unknown,
				fatal: true,
			);
		}
		else {
			Error::from(
				state: $error,
				fatal: true,
			);
		}

		exit();
	}

	/**
	 * Начать прослушивание, перехват и обработку ошибок и исключений.
	 * При вызове без аргументов включает перехват и обработку ошибок, используя собственные
	 * обработчики $this->_errorHandler(...) и $this->_exceptionHandler(...), цель которых
	 * привести все ошибки к единому формату логируемых объектов Ultra\Error, записать их
	 * в журнал событий и выполнить остановку исполнения в случае фатального сбоя.
	 * В метод можно передать в качестве аргументов соответствующие замыкания, которые будут
	 * выполнять обработку ошибок вместо используемых по умолчанию.
	 * Так же, можно отменить обработку любого из источников ошибок передав в качестве
	 * аргумента NULL.
	 */
	public function listen(
		Closure|null|true $error_handler     = true,
		Closure|null|true $exception_handler = true,
	): self {
		if (true === $error_handler) {
			set_error_handler($this->_errorHandler(...));
		}
		else {
			$prev = set_error_handler($error_handler);

			if (is_null($error_handler) && !is_null($prev)) {
				error_clear_last();
			}
		}

		if (true === $exception_handler) {
			set_exception_handler($this->_exceptionHandler(...));
		}
		else {
			set_exception_handler($exception_handler);
		}

		return $this;
	}

	/**
	 * Прекратить прослушивание и перехват ошибок и исключений.
	 * Аналогично вызову $core->listen(NULL, NULL);.
	 */
	public function stopListen(): self {
		$prev = set_error_handler(null);
		set_exception_handler(null);
		
		if (!is_null($prev)) {
			error_clear_last();
		}

		return $this;
	}

	/**
	 * Получение шаблона заголовков для экспортируемых файлов исходного кода.
	 */
	public function srcHeader(string $file = ''): string {
		if (!$this->setHeader($file)) {
			return '';
		}

		if (!$header = file_get_contents($this->_header)) {
			return '';
		}

		return $header;
	}

	/**
	 * Установка файла, содержашего шаблон заголовков для экспортируемых файлов исходного кода.
	 */
	public function setHeader(string $file): self {
		if ('' == $file) {
			if ('' == $this->_header) {
				return $this;
			}
		}
		elseif (!is_readable($file)) {
			if ('' == $this->_header) {
				return $this;
			}
		}
		else {
			$this->_header = $file;
		}

		return $this;
	}

	/**
	 * Получение пути к файлу журнала событий.
	 */
	public function getLogfile(): string {
		if ('' != $this->_logfile) {
			return $this->_logfile;
		}

		return dirname(realpath($_SERVER['SCRIPT_FILENAME'])).'/ultra_log.php';
	}

	/**
	 * Установка имени файла журнала событий.
	 */
	public function logfile(string $file): self {
		if ('' != $file) {
			$this->_logfile = $file;
		}

		return $this;
	}

	/**
	 * Установить частоту заполнения журнала событий.
	 * $frequency - целое число от 1 до 1000.
	 * По умолчанию значение равно "1". При возникновении какого-либо события кахдый раз
	 * выпоняется сверка с журналом.
	 * Если установить значение в "100", то сверка будет выполняется в одном случае из ста.
	 * 
	 * Для веб-приложения имеет смысл установить частоту сверки с журналом отличную от "1".
	 * При достаточно активной посещаемости нет смысла бесконечно перечитывать файл журнала
	 * сообщений об ошибках, так как вероятность появления какой-либо новой незафиксированной
	 * ранее ошибки при высокой частоте запросов стремиться к нулю.
	 * Рекомендуется установить значение частоты фиксации ошибок равное среднему количеству
	 * запросов к приложению в течении 10-20 минут, но не более 1000.
	 */
	 public function frequency(int $frequency): self {
		if ($this->cli) {
			return $this;
		}

		if ($frequency < 1) {
			$frequency = 1;
		}
		elseif ($frequency > 1000) {
			$frequency = 1000;
		}

		$this->_frequency = $frequency;
		return $this;
	}

	public function logable(): bool {
		if (
			1 == $this->_frequency
			|| mt_rand(1, $this->_frequency) == $this->_frequency
			|| Mode::Develop()
			|| $this->cli
		) {
			return true;
		}

		return false;
	}

	public function catchError(Error $e): void {
		$this->_failure  = true;

		if ($e->fatal) {
			$this->shutdown->fatal = $e;
			exit();
		}
	}

	public function registerFailure(State $value): void {
		if (!$this->_failure) {
			$this->_failure = !$value->valid();
		}
	}

	public function isFailure(): bool {
		return $this->_failure;
	}

	/**
	 * Проверить наличие ошибок или ошибок указанных типов
	 */
	public function isError(Condition ...$type): bool {
		if (!$this->_failure) {
			return false; // Если нет отказов, то нет и ошибок.
		}

		$log = Log::get();
		
		if (0 == $log->size()) {
			return false;
		}

		if (empty($type)) {
			return true;
		}

		foreach ($log->getKeys() as $id) {
			if (in_array($log->getType($id), $type)) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Проверить наличие ошибок или ошибок кроме указанных типов
	 */
	public function isErrorExcept(Condition ...$type): bool {
		if (!$this->_failure) {
			return false; // Если нет отказов, то нет и ошибок.
		}

		$log = Log::get();
		
		if (0 == $log->size()) {
			return false;
		}

		if (empty($type)) {
			return true;
		}

		foreach ($log->getKeys() as $id) {
			if (!in_array($log->getType($id), $type)) {
				return true;
			}
		}

		return false;
	}

	#[Initializer]
	private static function _init(): self {
		$core = new Core;
		error_reporting(E_ALL);

		if (!$e = error_get_last()) {
			return $core;
		}

		$e['type']    ??= E_WARNING;
		$e['message'] ??= 'Unknown last error before starting core error listener.';
		$e['file']    ??= 'External source';
		$e['line']    ??= 0;

		$type = Status::tryFrom($e['type']) ?? Status::Warning;

		Error::from(
			state: new Fail(
				type:    $type,
				message: $e['message'],
				file:    $e['file'],
				line:    $e['line'],
			),
			context: '['.$type->name.' #'.$e['type'].']',
		);

		error_clear_last();

		return $core;
	}

	private function __construct() {
		if ('cli' == PHP_SAPI) {
			$this->cli = true;
			$this->eol = PHP_EOL;
			$this->opcache = ('1' == ini_get('opcache.enable_cli'));
		}
		else {
			$this->cli = false;
			$this->eol = "\n";
			$this->opcache = ('1' == ini_get('opcache.enable'));
		}

		$this->_logfile   = '';
		$this->_frequency = 1;
		$this->_header    = dirname(__DIR__).'/header.txt';
		$this->_failure   = false;
	}

	/**
	 * Обработчик ошибок.
	 */
	private function _errorHandler(
		int $errno, string $errstr, string $errfile, int $errline
	): bool {
		$type = Status::tryFrom($errno) ?? Status::UserWarning;

		Error::from(
			state: new Fail(
				type:    $type,
				message: $errstr,
				file:    $errfile,
				line:    $errline,
			),
			context: '['.$type->name.' #'.$errno.']',
		);

		return true;
	}

	/**
	 * Обработчик исключений.
	 */
	private function _exceptionHandler(Throwable $ex): void {
		if ($ex instanceof ErrorException) {
			$code = (int) $ex->getSeverity();
		}
		else {
			$code = (int) $ex->getCode();
		}

		if (0 == $code) {
			if ($ex instanceof InternalError) {
				$code = E_ERROR;
			}
			else {
				$code = E_WARNING;
			}
		}

		$type = Status::tryFrom($code) ?? Status::Exception;

		Error::from(
			state: new Fail(
				type:    $type,
				message: $ex->getMessage(),
				file:    $ex->getFile(),
				line:    $ex->getLine(),
				trace:   $ex->getTrace(),
			),
			context: '['.$ex::class.' #'.$code.']',
		);
	}
}
