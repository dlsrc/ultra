<?php declare(strict_types=1);
/**
 * (c) 2005-2023 Dmitry Lebedev <dl@adios.ru>
 * This source code is part of the Ultra package core library.
 * Please see the LICENSE file for copyright and licensing information.
 */
namespace ultra;

/**
 * Выделенная из класса загрузчика ресурсоемкая, редко исполняемая	процедура создания и
 * обновления главного реестра классов.
 */
final class Registry {
	/**
	 * Флаг пройденной перезагрузки реестра.
	 */
	private static bool $done = false;

	/**
	 * Запросить построение (перезагрузку) реестра классов.
	 */
	public static function build(Boot $b): void {
		if (self::$done) {
			return;
		}

		$key = ftok(__FILE__, 'b');

		if (-1 == $key) {
			return;
		}

		// Не менять последовательность подключения
		include_once __DIR__.'/preference/search.php';
		include_once __DIR__.'/preference/case.php';
		include_once __DIR__.'/preference/backedcase.php';
		include_once __DIR__.'/lang.php';
		include_once __DIR__.'/mode.php';
		include_once __DIR__.'/condition.php';
		include_once __DIR__.'/value/valuable.php';
		include_once __DIR__.'/value/instance.php';
		include_once __DIR__.'/value/suspense.php';
		include_once __DIR__.'/value/wrapper.php';
		include_once __DIR__.'/value/fail.php';
		include_once __DIR__.'/value/substitute.php';
		include_once __DIR__.'/value/value.php';
		include_once __DIR__.'/status.php';
		include_once __DIR__.'/export/setstate_directly.php';
		include_once __DIR__.'/export/callable_state.php';
		include_once __DIR__.'/container/immutable.php';
		include_once __DIR__.'/container/sociable.php';
		include_once __DIR__.'/container/storable.php';
		include_once __DIR__.'/container/container.php';
		include_once __DIR__.'/container/nameless.php';
		include_once __DIR__.'/container/getter.php';
		include_once __DIR__.'/core.php';
		include_once __DIR__.'/log.php';
		include_once __DIR__.'/error.php';
		include_once __DIR__.'/code.php';
		include_once __DIR__.'/io.php';
		include_once __DIR__.'/export/exporter.php';
		include_once __DIR__.'/lang/core.'.Lang::name().'.php';
		include_once __DIR__.'/lang/io.'.Lang::name().'.php';
		include_once __DIR__.'/mutex/mutex.php';

		if (extension_loaded('sysvsem')) {
			include_once __DIR__.'/mutex/mutexsysvsem.php';
			$mtx = SysVSemMutex::get($key);
		}
		elseif (extension_loaded('shmop')) {
			include_once __DIR__.'/mutex/mutexshmop.php';
			$mtx = ShmopMutex::get($key);
		}
		else {
			include_once __DIR__.'/mutex/mutexfile.php';
			$mtx = FileMutex::get($key);
			$mtx->setpath(dirname($b->registry_folder));
		}

		if ($mtx->acquire()) {
			(new Registry)->create($b);
			self::$done = true;
			$mtx->release();
			return;
		}

		if ($b->wait) {
			if ($mtx->acquire(true)) {
				self::$done = true;
				$mtx->release();
			}
		}
	}

	private function __construct() {}

	/**
	 * Поиск нескольких пространств имен в исходном коде.
	 * Разделить исходнвй код на части по пространствам имен, вернуть список частей кода.
	 * Если в коде нет пространств имен или пространство имен одно, список будет состоять из
	 * одного элемента.
	 * $code - строка исходного кода.
	 */
	private function splitNamespaces(string $code): array {
		if (preg_match_all('/\s+namespace\s+([^\W\d](?:[\w\x5C]*\w)?)(\;|\s*\{)/is', $code, $match)) {
			$split = preg_split('/\s+namespace\s+([^\W\d](?:[\w\x5C]*\w)?)(\;|\s*\{)/is', $code);
			$ns = [];

			foreach ($match[1] as $i => $name) {
				if ('' != $name) {
					$name.= '\\';
				}

				if (isset($ns[$name])) {
					$ns[$name].= $split[$i+1];
				}
				else {
					$ns[$name] = $split[$i+1];
				}
			}
		}
		else {
			$ns = ['' => $code];
		}

		return $ns;
	}

	/**
	 * Очистить исходный код от комментариев и сроковых литералов.
	 * Вернуть оставшийся текст кода в виде списка, разделив код по пространствам имен.
	 * $file - файл с исходным кодом.
	 */
	private function splitCode(string $file): array {
		$code = php_strip_whitespace($file);

		$code = preg_replace([
			'/<<<(\x22|\x27)([^\W\d]\w*)\g{1}/u',
			'/<<<([^\W\d]\w*).+\g{1};/isu',
			'/((\x5C){2})+/',
			'/(\x22|\x27).*(?<!\x5C)\g{1}/Uisu',
		], ['<<<\2', '', '', ''], $code);		

		return $this->splitNamespaces($code);
	}

	/**
	 * Построение реестра классов с передачей результата в загрузчик классов.
	 */
	public function create(Boot $b): void {
		$library = array_values($b->code_library);
		$excluded = $b->excluded_folder;
		$extension = $b->extension;

		if (empty($extension)) {
			$extension = '*';
		}
		else {
			$extension = '{'.implode(',', $b->extension).'}';
		}

		$register = [];
		$pattern = '/
		(?: (?: abstract | final | readonly | ) class | interface | trait | enum )
		\s+ (\w+)
		(?:	\s* \: \s+ (?: int | string ) |
			\s* \: \s+ (?: int | string ) \s+ implements \s+ [^\{]* \w |
			\s+ implements \s+ [^\{]* \w |
			\s+ extends \s+ [^\{]* \w |
		) \s+ \{
		/xis';

		ignore_user_abort(true);
		set_time_limit(0);

		for ($i = 0; isset($library[$i]); $i++) {
			if (is_file($library[$i])) {
				$files = [$library[$i]];
			}
			elseif (!$files = glob($library[$i].'*.'.$extension, GLOB_BRACE)) {
				$files = [];
			}

			foreach ($files as $file) {
				if (in_array($file, $excluded)) {
					continue;
				}

				foreach ($this->splitCode($file) as $name => $code) {
					if (preg_match_all($pattern, $code, $match)) {
						foreach ($match[1] as $itc) {
							$itc = $name.$itc;
							
							if (isset($register[$itc])) {
								if ($register[$itc] == $file) {
									continue;
								}

								if (filemtime($register[$itc]) > filemtime($file)) {
									continue;
								}

								while (in_array($file, $register)) {
									$key = array_search($file, $register);
									unset($register[$key]);
								}
							}

							$register[$itc] = $file;
						}
					}
				}
			}

			if (is_file($library[$i])) {
				continue;
			}

			if (!$list = scandir($library[$i])) {
				continue;
			}

			foreach ($list as $val) {
				if (is_dir($library[$i].$val)) {
					if ('.' == $val || '..' == $val || '.git' == $val) {
						continue;
					}

					if (in_array($library[$i].$val.'/', $excluded)) {
						continue;
					}

					$library[] = $library[$i].$val.'/';
				}
			}
		}

		$b->addRegister($register);
	}
}
