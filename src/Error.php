<?php declare(strict_types=1);
/**
 * (c) 2005-2024 Dmitry Lebedev <dl@adios.ru>
 * This source code is part of the Ultra library.
 * Please see the LICENSE file for copyright and licensing information.
 */
namespace Ultra;

/**
 * Ошибка, хранимая в виде объекта в файле журнала.
 */
final readonly class Error implements CallableState, State {
	use SetState;
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
	 * Вернуть текст сообщения об ошибке в контексте её появления: код ошибки, файл и строка вызова ошибки.
	 */
	public function getMessage(): string {
		$eol = Core::get()->eol;
		
		return nl2br(
			'Error code: #'.$this->errno.$eol.
			'Message:    '.$this->message.$eol.
			'File:       '.$this->file.$eol.
			'Line:       '.$this->line
		);
	}

	/**
	 * Зарегистрировать новый объект ошибки в журнале и вернуть его.
	 * $message - сообшение об ошибке;
	 * $type    - Статус ошибки;
	 * $fatal   - для пользовательских ошибок флаг фатальной ошибки, установить в TRUE,
	 * если при возникновении ошибки, согласно логике программы, дальнейшее её выполнение
	 * бессмысленно.$errno
	 */
	public static function log(string $message, Condition $type = Status::User, bool $fatal = false): static {
		$trace = debug_backtrace();

		$bug = [
			'errno' => $type->value,
			'file'  => $trace[0]['file'],
			'line'  => $trace[0]['line'],
		];

		$bug['id'] = Log::makeId($bug);
		$log = Log::get();

		if ($log->exists($bug['id'])) {
			$error = $log->getError($bug['id']);

			if (is_object($error) && $error::class == self::class) {
				return $error;
			}
		}

		$bug['type'] = $type;

		if ($fatal || $type->isFatal()) {
			$bug['fatal'] = true;
		}
		else {
			$bug['fatal'] = false;
		}

		$bug['message'] = $message;
		$bug['context'] = '['.$type->name.' #'.$type->value.']'."\n".self::_prepareContext($trace);

		$error = new Error($bug);
		$log->addError($error, true);

		return $error;
	}

	/**
	 * Пытаться привести интерфейс Ultra\State к объекту Ultra\Error, в случае удачного
	 * преобразования, добавить его в журнал и вернуть новый или тот-же самый интерфейс
	 * Ultra\State.
	 * Если в качестве аргумента был передан объект несущий успешное состояние, то будет
	 * возвращён этот же объект. Если такой объект содержал ссылку на объект его породивший и не
	 * являющийся валидным, то породивший объект будет преобразован в Ultra\Error, если таковым
	 * не являестя, и внесён в журнал событий, но возвращён объект, принятый в качестве аргумента.
	 * В остальных случаях будет возвращён новый или готовый объект Ultra\Error.
	 * 
	 * Данный метод может быть передан в качестве параметра $reject в методы expect(), fetch(),
	 * follow() и recover() интерфейса Ultra\State.
	 */
	public static function from(State $state, string|null $context = null, bool $fatal = false): State {
		if ($state->valid()) {
			if (property_exists($state, 'previous')
			&& isset($state->previous)
			&& ($state->previous instanceof State)
			) {
				$previous = $state->previous;
				$context  = 'Error from Substitute previous object by '.
						    $previous::class.' with status '.
				            '['.$previous->type->name.' #'.$previous->type->value.']';
			}
			else {
				return $state;
			}
		}
		else {
			$previous = $state;
		}

		$log = Log::get();

		if ($previous instanceof self) {
			if ($fatal) {
				$error = new Error([
					'id'      => $previous->id,
					'message' => $previous->message,
					'context' => $previous->context,
					'file'    => $previous->file,
					'line'    => $previous->line,
					'type'    => $previous->type,
					'errno'   => $previous->errno,
					'fatal'   => true,
					'time'    => $previous->time,
					'date'    => $previous->date,
				]);

				$log->addError($error, true);
			}

			return $state;
		}
		elseif (0 == $previous->line || "" == $previous->file) {
			$trace = debug_backtrace();

			$bug = [
				'errno' => $previous->type->value,
				'file'  => $trace[0]['file'],
				'line'  => $trace[0]['line'],
			];
		}
		else {
			$bug = [
				'errno' => $previous->type->value,
				'file'  => $previous->file,
				'line'  => $previous->line,
			];
		}

		$bug['id'] = Log::makeId($bug);
		$log = Log::get();

		if ($log->exists($bug['id'])) {
			$error = $log->getError($bug['id']);

			if (is_object($error) && $error::class == self::class) {
				return $state;
			}
		}

		$bug['type'] = $previous->type;

		if ($fatal || $previous->type->isFatal()) {
			$bug['fatal'] = true;
		}
		else {
			$bug['fatal'] = false;
		}

		$bug['message'] = $previous->message;
		$bug['context'] = $context ?? '['.$previous->type->name.' #'.$previous->type->value.']';
		
		if (is_array($previous->trace)) {
			$bug['context'] = $bug['context']."\n".self::_prepareContext($previous->trace);
		}

		$error = new Error($bug);
		$log->addError($error, true);
		return $state;
	}

	/**
	 * Получить из массива трассировки человекочитаемую строку
	 */
	private static function _prepareContext(array $trace): string {
		if (!isset($trace[1])) {
			if (is_string($trace[0]['file'])) {
				return 'Error before or in the line '.$trace[0]['line'].
				' in file "'.strtr($trace[0]['file'], '\\', '/').'"';
			} else {
				return 'Error trace '.var_export($trace, true);
			}
		}
		
		$context = '<- Error before or in the line '.$trace[0]['line'].
		' in file "'.strtr($trace[0]['file'], '\\', '/').'"'."\n";

		foreach (range(1, \array_key_last($trace)) as $i) {
			if (empty($trace[$i]['args'])) {
				$args = '()';
			}
			else {
				foreach ($trace[$i]['args'] as $id => $arg) {
					if (empty($arg)) {
						if (is_bool($trace[$i]['args'][$id])) {
							$trace[$i]['args'][$id] = 'false';
						}
						elseif (is_null($trace[$i]['args'][$id])) {
							$trace[$i]['args'][$id] = 'null';
						}
						elseif (is_string($trace[$i]['args'][$id])) {
							$trace[$i]['args'][$id] = '""';
						}
						elseif (is_array($trace[$i]['args'][$id])) {
							$trace[$i]['args'][$id] = 'array()';
						}
					}
					elseif (is_bool($trace[$i]['args'][$id])) {
						$trace[$i]['args'][$id] = 'true';
					}
					elseif (is_string($trace[$i]['args'][$id])) {
						$trace[$i]['args'][$id] = '"'.$trace[$i]['args'][$id].'"';
					}
					elseif (is_object($trace[$i]['args'][$id])) {
						if (is_subclass_of($trace[$i]['args'][$id], 'UnitEnum')) {
							$trace[$i]['args'][$id] = $trace[$i]['args'][$id]::class.'::'.$trace[$i]['args'][$id]->name;
						}
						else {
							$trace[$i]['args'][$id] = $trace[$i]['args'][$id]::class;
						}
					}
					elseif (is_array($trace[$i]['args'][$id])) {
						$count = count($trace[$i]['args'][$id]);

						if ($count > 5) {
							$trace[$i]['args'][$id] = 'array(with '.$count.' elements)';
						}
						else {
							$trace[$i]['args'][$id] = str_replace("\n", ' ', var_export($trace[$i]['args'][$id], true));
						}
					}
				}

				$args = '('.implode(', ', $trace[$i]['args']).')';
			}

			$context = "\n\n".'  '.str_replace("\n", "\n".'  ', $context)."\n".'}';

			if (!isset($trace[$i]['class'])) {
				if (in_array($trace[$i]['function'], ['include', 'include_once', 'require', 'require_once'])) {
					$args = strtr($args, '\\', '/');
				}

				$context = $trace[$i]['function'].$args.' {'.$context;
				$call = 'Function call';
			}
			elseif ('__construct' == $trace[$i]['function']) {
				$context = 'new '.$trace[$i]['class'].$args.' {'.$context;
				$call = 'Class instance creation';
			}
			elseif ('->' == $trace[$i]['type']) {
				if (is_subclass_of($trace[$i]['class'], 'UnitEnum')) {
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

			if (isset($trace[$i]['line'])) {
				$context = '// '.$call.' on line '.$trace[$i]['line'].' in file "'.
				(isset($trace[$i]['file']) ? \strtr($trace[$i]['file'], '\\', '/') : 'Unknown file').
				'"'."\n".$context;
			}
			else {
				$context = '// '.$call.' in file "'.
				(isset($trace[$i]['file']) ? \strtr($trace[$i]['file'], '\\', '/') : 'Unknown file').
				'"'."\n".$context;
			}
		}

		return "\n".$context;
	}

	private function __construct(array $error) {
		$this->id      = $error['id'];
		$this->message = $error['message'];
		$this->context = $error['context'];
		$this->file    = strtr($error['file'], '\\', '/');
		$this->line    = $error['line'];
		$this->type    = $error['type'];
		$this->errno   = $error['errno'];
		$this->fatal   = $error['fatal'];
		$this->time    = $error['time'] ?? time();
		$this->date    = $error['date'] ?? date('Y-m-d H:i:s', $this->time);
	}
}
