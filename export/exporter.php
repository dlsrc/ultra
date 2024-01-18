<?php declare(strict_types=1);
/**
 * (c) 2005-2023 Dmitry Lebedev <dl@adios.ru>
 * This source code is part of the Ultra package core library.
 * Please see the LICENSE file for copyright and licensing information.
 */
namespace ultra;

/**
 * Создает файл и помещает в него исходный код PHP.
 */
final class Exporter implements Storable {
	use Filename;

	public function __construct(string $file = '') {
		$this->_file = $file;
	}

	/**
	 * Сохраняет переменную в файл как код возврата этой переменной:
	 * <?php return (mixed) $variable;
	 * 
	 * Код, помещенный в файл, возвращает значение экспортированной переменной и может быть
	 * использован при включении файла оператором include:
	 * $var = include 'filename.php';
	 * 
	 * Переменная может быть любого типа, кроме resource. Если необходимо в интерпретируемом
	 * коде возвращать объекты через оператор new, не реализуя метод __set_state(), конструкторы
	 * в классах таких объектов должены соответствовать сигнатуре метода __set_state(), т.е.
	 * содержать единстренный параметр типа array, и должен помечаться атрибутом
	 * ultra\SetStateDirectly.
	 * 
	 * $variable - переменная PHP;
	 * $header   - строка с заголовочным комментарием;
	 * $strict   - флаг, указывающий что сохраняемый код будет использоваться в режиме строгого
	 * соответствия;
	 */
	public function save(mixed $variable, string $header = '', bool $strict = true): bool {
		if ('' == $this->_file) {
			return false;
		}

		if (!IO::indir($this->_file)) {
			return false;
		}

		if (!$code = $this->_makeCode($variable, $header, $strict)) {
			return false;
		}

		if (IO::fw($this->_file, $code) < 0) {
			return false;
		}

		Core::get()->invalidate($this->_file);
		return true;
	}

	/**
	 * Сохраняет исходный код PHP в файл.
	 * 
	 * $code   - строка, являющаяся валидным исходным кодом PHP;
	 * $header - строка с заголовочным комментарием;
	 * $strict - флаг, указывающий что сохраняемый код будет использоваться в режиме строгого
	 * соответствия;
	 */
	public function put(string $code, string $header = '', bool $strict = true): bool {
		if ('' == $this->_file) {
			return false;
		}

		if (!IO::indir($this->_file)) {
			return false;
		}

		if (str_contains($code, 'declare(strict_types=1);')) {
			$strict = false;
		}

		if (IO::fw($this->_file, $this->_prepareCode($code, $header, $strict)) < 0) {
			return false;
		}

		Core::get()->invalidate($this->_file);
		return true;
	}

	/**
	 * Подготовка строки комментария для заголовка файла
	 */
	private function _prepareHeader(string $header): string {
		if (!str_starts_with($header, '/*')) {
			$header = '/*'."\n".$header;
		}

		if (!str_ends_with($header, '*/')) {
			$header = $header."\n".'*/';
		}

		return $header;
	}

	/**
	 * Подготовить переменную к сохранению
	 */
	private function _makeCode(mixed $variable, string $header, bool $strict): string|null {
		if ($code = $this->_optimize(var_export($variable, true), $this->_storable($variable))) {
			if (Mode::Product->current() || '' == $header) {
				$code = '<?php'.$this->_declare($strict)."\n".'return '.$code.';'."\n";
			}
			else {
				$code = '<?php'.$this->_declare($strict)."\n".$this->_prepareHeader($header)."\n".
				'return '.$code.';'."\n";
			}
		}

		return $code;
	}

	/**
	 * Подготовить исходный код к сохранению
	 */
	private function _prepareCode(string $code, string $header, bool $strict): string {
		if (str_starts_with($code, '<?php')) {
			return $code;
		}
		
		if (Mode::Product->current() || '' == $header) {
			return '<?php'.$this->_declare($strict)."\n".$code."\n";
		}

		return '<?php'.$this->_declare($strict)."\n".$this->_prepareHeader($header)."\n".$code."\n";
	}

	/**
	 * Включить для сохраняемого исходного кода режим строгой типизации
	 */
	private function _declare(bool $strict): string {
		if ($strict) {
			return ' declare(strict_types=1);';
		}

		return '';
	}

	/**
	 * Оптимизировать исходный код перед сохранением
	 */
	private function _optimize(string $code, bool $storable): string|null {
		$seek = '/(\x5C?[^\W\d](?:[\w\x5C]*\w)?)::__set_state/is';
		$direct = [];
		$nocall = [];

		if (preg_match_all($seek, $code, $match)) {
			$match = array_unique($match[1]);

			foreach ($match as $name) {
				switch ($this->_isExportable($name)) {
				case 2:
					$direct[] = $name;
					break;
				case 0:
					$nocall[] = $name;
					break;
				}
			}
		}

		if (!empty($nocall)) {
			// TODO: Сообщение о невозможности экспорта переменной.
			return null;
		}

		if (!empty($direct)) {
			$seek = '/('.\implode('|', \array_map(fn(string $text) => preg_quote($text), $direct)).')::__set_state/is';
			$code = preg_replace($seek, 'new $1', $code);
		}

		if ($storable) {
			$code = preg_replace(
				'/\'_file\'\s*\=>\s*\'[^\']+\'\,/', '\'_file\' => __FILE__,', $code
			);
		}

		if (Mode::Develop->current()) {
			return $code;
		}

		return preg_replace(
			['/\s+\=\>\s+/', '/\s*\(\n\s+/', '/\,\n\s*\)/', '/\,\n\s+/'],
			['=>', '(', ')', ','],
			$code
		);
	}
	
	/**
	 * Выявляет интерфейс ultra\Storable в экспортируемой переменной.
	 */
	private function _storable(mixed $variable): bool {
		if (is_object($variable) && ($variable instanceof Storable)) {
			return true;
		}

		return false;
	}

	/**
	 * Проверить, можно ли экспортировать класс без метода __set_state(). Сигнатура
	 * конструктора в таком классе должна совпадать с методом	__set_state(). Сам конструктор
	 * должен быть помечен атрибутом ultra\SetStateDirectly.
	 */
	private function _isSetStateDirectly(\ReflectionClass $r): bool {
		$a = $r->getAttributes(\ultra\SetStateDirectly::class);

		if (empty($a)) {
			return false;
		}

		$dc = $a[0]->newInstance();
		return $dc->isCallable($r);
	}

	/**
	 * Проверить, является ли класс, экспортируемым.
	 * В классе должен присутствовать метод __set_state()
	 * 
	 * Возвращает:
	 * 0 - класс невозможно экспортировать;
	 * 1 - класс экспортируется с использованием метода __set_state()
	 * 2 - класс экспортируется напрямую через вызов конструктора
	 */
	private function _isExportable(string $class): int {
		$r = new \ReflectionClass($class);

		if ($this->_isSetStateDirectly($r)) {
			return 2;
		}

		if (!$r->hasMethod('__set_state')) {
			return 0;
		}

		return 1;
	}
}
