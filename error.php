<?php declare(strict_types=1);
/**
 * (c) 2005-2023 Dmitry Lebedev <dl@adios.ru>
 * This source code is part of the Ultra package core library.
 * Please see the LICENSE file for copyright and licensing information.
 */
namespace ultra;

/**
 * Ошибка, хранимая в виде объекта в файле журнала.
 */
final readonly class Error implements CallableState, Valuable {
	use SetStateCall;
	use Suspense;

	/**
	 * Идентификатор ошибки на основе её сигнатуры.
	 */
	public string $id;

	/**
	 * Сообщение об ошибке.
	 */
	public string $message; 

	/**
	 * Описание контекста возникновения ошибки.
	 */
	public string $context;

	/**
	 * Файл в котором произошла ошибка или выброшено исключение.
	 */
	public string $file;

	/**
	 * Строка файла в котором произошла ошибка или выброшено исключение.
	 */
	public int $line;

	/**
	 * Тип пользовательской ошибки.
	 */
	public Condition $type;

	/**
	 * Числовой код ошибки.
	 */
	public int $errno;

	/**
	 * Флаг фатальной ошибки.
	 */
	public bool $fatal;

	/**
	 * Отметка времени последнего появления ошибки.
	 */
	public int $time;

	/**
	 * Дата первого появления ошибки в формате "YYYY-mm-dd HH:ii:ss".
	 */
	public string $date;

	/**
	 * Зарегистрировать новый объект ошибки в журнале и вернуть его.
	 * $message - сообшение об ошибке;
	 * $type    - Статус ошибки;
	 * $fatal   - для пользовательских ошибок флаг фатальной ошибки, установить в TRUE,
	 * если при возникновении ошибки, согласно логике программы, дальнейшее её выполнение
	 * бессмысленно.
	 */
	public static function log(string $message, Condition $type = Status::User, bool $fatal = false): static {
		$trace = \debug_backtrace();

		$state = [
			'errno' => $type->value,
			'file'  => $trace[0]['file'],
			'line'  => $trace[0]['line'],
		];

		$state['id'] = Log::makeId($state);
		$log = Log::get();

		if ($log->exists($state['id'])) {
			$error = $log->getError($state['id']);

			if (\is_object($error) && $error::class == self::class) {
				return $error;
			}
		}

		$state['type'] = $type;

		if ($fatal || $type->isFatal()) {
			$state['fatal'] = true;
		}
		else {
			$state['fatal'] = false;
		}

		$state['message'] = $message;
		$state['context'] = '['.$type->name.' #'.$type->value.']'.
		\PHP_EOL.self::_prepareContext($trace);

		$error = new Error($state);
		$log->addError($error, true);

		return $error;
	}

	/**
	 * Пытаться преобразовать интерфейс ultra\Valuable в объект ultra\Error, в случае
	 * преобразования добавить его в журнал и вернуть новый или тот-же самый интерфейс
	 * ultra\Valuable.
	 * Если в качестве аргумента был передан объект несущий валидное значение, то будет
	 * возвращён этот же объект. Если такой объект содержал ссылку на объект его породивший и не
	 * являющийся валидным, то породивший объект будет преобразован в ultra\Error, если таковым
	 * не являестя, и внесён в журнал событий, но возвращён будет исходный объект.
	 * В остальных случаях будет возвращён новый или готовый объект ultra\Error.
	 * 
	 * Данный метод может быть передан в качестве параметра $reject в методы expect(), fetch(),
	 * follow() и recover() интерфейса ultra\Valuable.
	 */
	public static function from(Valuable $value, string|null $context = null, bool $fatal = false): Valuable {
		if ($value->valid()) {
			if (\property_exists($value, 'previous')
			&& isset($value->previous)
			&& ($value->previous instanceof Valuable)
			) {
				$previous = $value->previous;
				$context  = 'Error from Substitute previous object by '.
						    $previous::class.' with status '.
				            '['.$previous->type->name.' #'.$previous->type->value.']';
			}
			else {
				return $value;
			}
		}
		else {
			$previous = $value;
		}

		$log = Log::get();

		if ($previous::class == self::class) {
			if ($fatal) {
				$error = clone $previous;
				$log->addError($error, true);
			}

			return $value;
		}
		elseif (0 == $previous->line || "" == $previous->file) {
			$trace = \debug_backtrace();

			$state = [
				'errno' => $previous->type->value,
				'file'  => $trace[0]['file'],
				'line'  => $trace[0]['line'],
			];
		}
		else {
			$state = [
				'errno' => $previous->type->value,
				'file'  => $previous->file,
				'line'  => $previous->line,
			];
		}

		$state['id'] = Log::makeId($state);
		$log = Log::get();

		if ($log->exists($state['id'])) {
			$error = $log->getError($state['id']);

			if (\is_object($error) && $error::class == self::class) {
				return $value;
			}
		}

		$state['type'] = $previous->type;

		if ($fatal || $previous->type->isFatal()) {
			$state['fatal'] = true;
		}
		else {
			$state['fatal'] = false;
		}

		$state['message'] = $previous->message;
		$state['context'] = $context ?? '['.$previous->type->name.' #'.$previous->type->value.']';
		
		if (\is_array($previous->trace)) {
			$state['context'] = $state['context'].\PHP_EOL.self::_prepareContext($previous->trace);
		}

		$error = new Error($state);
		$log->addError($error, true);
		return $value;
	}

	/**
	 * Получить из массива трассировки человекочитаемую строку
	 */
	private static function _prepareContext(array $trace): string {
		if (!isset($trace[1])) {
			if (\is_string($trace[0]['file'])) {
				return 'Error before or in the line '.$trace[0]['line'].
				' in file "'.\strtr($trace[0]['file'], '\\', '/').'"';
			} else {
				return 'Error trace '.\var_export($trace, true);
			}
		}
		
		$context = '<- Error before or in the line '.$trace[0]['line'].
		' in file "'.\strtr($trace[0]['file'], '\\', '/').'"'.\PHP_EOL;

		foreach (\range(1, \array_key_last($trace)) as $i) {
			if (empty($trace[$i]['args'])) {
				$args = '()';
			}
			else {
				foreach ($trace[$i]['args'] as $id => $arg) {
					if (empty($arg)) {
						if (\is_bool($trace[$i]['args'][$id])) {
							$trace[$i]['args'][$id] = 'false';
						}
						elseif (\is_null($trace[$i]['args'][$id])) {
							$trace[$i]['args'][$id] = 'null';
						}
						elseif (\is_string($trace[$i]['args'][$id])) {
							$trace[$i]['args'][$id] = '""';
						}
					}
					elseif (\is_bool($trace[$i]['args'][$id])) {
						$trace[$i]['args'][$id] = 'true';
					}
					elseif (\is_string($trace[$i]['args'][$id])) {
						$trace[$i]['args'][$id] = '"'.$trace[$i]['args'][$id].'"';
					}
					elseif (\is_object($trace[$i]['args'][$id])) {
						if (\is_subclass_of($trace[$i]['args'][$id], 'UnitEnum')) {
							$trace[$i]['args'][$id] = $trace[$i]['args'][$id]::class.'::'.$trace[$i]['args'][$id]->name;
						}
						else {
							$trace[$i]['args'][$id] = $trace[$i]['args'][$id]::class;
						}
					}
				}

				$args = '('.\implode(', ', $trace[$i]['args']).')';
			}

			$context = \PHP_EOL.\PHP_EOL.'  '.\str_replace(\PHP_EOL, \PHP_EOL.'  ', $context).\PHP_EOL.'}';

			if (!isset($trace[$i]['class'])) {
				if (\in_array($trace[$i]['function'], ['include', 'include_once', 'require', 'require_once'])) {
					$args = \strtr($args, '\\', '/');
				}

				$context = $trace[$i]['function'].$args.' {'.$context;
				$call = 'Function call';
			}
			elseif ('__construct' == $trace[$i]['function']) {
				$context = 'new '.$trace[$i]['class'].$args.' {'.$context;
				$call = 'Class instance creation';
			}
			elseif ('->' == $trace[$i]['type']) {
				if (\is_subclass_of($trace[$i]['class'], 'UnitEnum')) {
					$object = ' Enum';
				}
				else {
					$object = ' Object';
				}

				$context = $trace[$i]['class'].$object.$trace[$i]['type'].$trace[$i]['function'].$args.' {'.$context;
				$call = 'Method call';
			}
			else {
				$context = $trace[$i]['class'].$trace[$i]['type'].$trace[$i]['function'].$args.' {'.$context;
				$call = 'Static call';
			}

			$context = '// '.$call.' on line '.$trace[$i]['line'].' in file "'.
			(isset($trace[$i]['file']) ? \strtr($trace[$i]['file'], '\\', '/') : 'Unknown file').
			'"'.\PHP_EOL.$context;
		}

		return \PHP_EOL.$context;
	}

	/**
	 * Клонировать ошибку, как фатальную.
	 */
	public function __clone(): void {
		$this->id      = clone $this->id;
		$this->message = clone $this->message;
		$this->context = clone $this->context;
		$this->file    = clone $this->file;
		$this->line    = clone $this->line;
		$this->type    = clone $this->type;
		$this->errno   = clone $this->errno;
		$this->fatal   = true;
		$this->time    = clone $this->time;
		$this->date    = clone $this->date;
	}

	private function __construct(array $state) {
		$this->id      = $state['id'];
		$this->message = $state['message'];
		$this->context = $state['context'];
		$this->file    = \strtr($state['file'], '\\', '/');
		$this->line    = $state['line'];
		$this->type    = $state['type'];
		$this->errno   = $state['errno'];
		$this->fatal   = $state['fatal'];
		$this->time    = $state['time'] ?? \time();
		$this->date    = $state['date'] ?? \date('Y-m-d H:i:s', $this->time);
	}
}
