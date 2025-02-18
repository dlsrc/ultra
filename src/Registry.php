<?php declare(strict_types=1);
/**
 * (c) 2005-2025 Dmitry Lebedev <dl@adios.ru>
 * This source code is part of the Ultra library.
 * Please see the LICENSE file for copyright and licensing information.
 */
namespace Ultra;

use Throwable;

/**
 * Выделенная из класса загрузчика ресурсоемкая, редко исполняемая	процедура создания и
 * обновления главного реестра классов.
 */
final class Registry {
	/**
	 * Флаг пройденной перезагрузки реестра.
	 */
	private static bool $_done = false;

	/**
	 * Запросить построение (перезагрузку) реестра классов.
	 */
	public static function build(Boot $b): void {
		if (self::$_done) {
			return;
		}
/*
		new Registry->create($b);
		self::$_done = true;
		return;
*/
		try {
			self::loadCore($b->basepath);

			$key = ftok(__FILE__, 'b');

			if (-1 == $key) {
				return;
			}
	
			if (extension_loaded('sysvsem')) {
				$mtx = namespace\Sync\SysVSem::get($key);
			}
			elseif (extension_loaded('shmop')) {
				$mtx = namespace\Sync\Shmop::get($key);
			}
			else {
				$mtx = namespace\Sync\File::get($key);
				$mtx->setpath(dirname($b->registry));
			}
		}
		catch (Throwable $e) {
			exit($e->getMessage());
		}

		if ($mtx->acquire()) {
			new Registry()->create($b);
			self::$_done = true;
			$mtx->release();
			return;
		}

		if ($b->wait && Core::get()->cli) {
			if ($mtx->acquire(true)) {
				self::$_done = true;
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
		$library = array_values($b->source);
		$excluded = $b->excluded;
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

	private static function loadCore(string $ultra_path): void {
		include_once $ultra_path.'/result/src/State.php';
		include_once $ultra_path.'/enum-cases/src/Cases.php';
	  //  include_once $ultra_path.'/enum-cases/src/CaseFinder.php';
		include_once $ultra_path.'/enum-dominant/src/Dominant.php';
		include_once $ultra_path.'/enum-dominant/src/DominantCase.php';
		include_once $ultra_path.'/enum-dominant/src/BackedDominant.php';
		include_once $ultra_path.'/enum-dominant/src/BackedDominantCase.php';
	  //  include_once $ultra_path.'/core/src/Boot.php';
		include_once $ultra_path.'/core/src/Export/CallableState.php';
		include_once $ultra_path.'/core/src/Export/Initializer.php';
		include_once $ultra_path.'/core/src/Export/Singleton.php';
	  //  include_once $ultra_path.'/chars/src/Key.php';
	  //  include_once $ultra_path.'/chars/src/Transform.php';
	  //  include_once $ultra_path.'/chars/src/Translit.php';
		include_once $ultra_path.'/result/src/Condition.php';
		include_once $ultra_path.'/core/src/Code.php';
		include_once $ultra_path.'/core/src/Generic/Storable.php';
	  //  include_once $ultra_path.'/core/src/Export/Exportable.php';
	  //  include_once $ultra_path.'/core/src/Generic/Attachable.php';
	  //  include_once $ultra_path.'/core/src/Generic/Called.php';
	  //  include_once $ultra_path.'/core/src/Generic/Collector.php';
	  //  include_once $ultra_path.'/core/src/Generic/Comparable.php';
	  //  include_once $ultra_path.'/core/src/Generic/Comparison.php';
	  //  include_once $ultra_path.'/core/src/Generic/Component.php';
		include_once $ultra_path.'/core/src/Generic/Container.php';
	  //  include_once $ultra_path.'/core/src/Generic/Extendable.php';
		include_once $ultra_path.'/core/src/Generic/Filename.php';
		include_once $ultra_path.'/core/src/Generic/Getter.php';
	  //  include_once $ultra_path.'/core/src/Generic/GetterCall.php';
		include_once $ultra_path.'/core/src/Generic/Immutable.php';
	  //  include_once $ultra_path.'/core/src/Generic/ImportableNamed.php';
	  //  include_once $ultra_path.'/core/src/Generic/ImportableNameless.php';
		include_once $ultra_path.'/core/src/Generic/Informer.php';
	  //  include_once $ultra_path.'/core/src/Generic/Mutable.php';
	  //  include_once $ultra_path.'/core/src/Generic/Name.php';
	  //  include_once $ultra_path.'/core/src/Generic/NamedGetter.php';
	  //  include_once $ultra_path.'/core/src/Generic/Named.php';
		include_once $ultra_path.'/core/src/Generic/NamelessGetter.php';
	  //  include_once $ultra_path.'/core/src/Generic/Nameless.php';
	  //  include_once $ultra_path.'/core/src/Generic/Setter.php';
		include_once $ultra_path.'/core/src/Generic/Sociable.php';
		include_once $ultra_path.'/core/src/Generic/Template.php';
		include_once $ultra_path.'/core/src/Export/SetState.php';
		include_once $ultra_path.'/core/src/Export/SetStateDirectly.php';
		include_once $ultra_path.'/core/src/Shutdown.php';
	  //  include_once $ultra_path.'/core/src/Export/Replica.php';
		include_once $ultra_path.'/core/src/Core.php';
		include_once $ultra_path.'/result/src/Suspense.php';
		include_once $ultra_path.'/core/src/Error.php';
		include_once $ultra_path.'/core/src/Export/Exporter.php';
	  //  include_once $ultra_path.'/result/src/Fail.php';
		include_once $ultra_path.'/core/src/IO.php';
		include_once $ultra_path.'/result/src/Valid.php';
		include_once $ultra_path.'/result/src/Instance.php';
		include_once $ultra_path.'/core/src/Lang.php';
		include_once $ultra_path.'/core/src/Log.php';
		include_once $ultra_path.'/core/src/Mode.php';
	  //  include_once $ultra_path.'/core/src/Registry.php';
	  //  include_once $ultra_path.'/result/src/Wrapper.php';
	  //  include_once $ultra_path.'/result/src/Result.php';
	  //  include_once $ultra_path.'/core/src/Export/Save.php';
		include_once $ultra_path.'/result/src/Status.php';
	  //  include_once $ultra_path.'/result/src/Substitute.php';
		include_once $ultra_path.'/core/src/Sync/Mutex.php';
		include_once $ultra_path.'/core/src/Sync/File.php';
		include_once $ultra_path.'/core/src/Sync/Shmop.php';
		include_once $ultra_path.'/core/src/Sync/SysVSem.php';
	  //  include_once $ultra_path.'/core/src/Container/Collection.php';
	  //  include_once $ultra_path.'/core/src/Container/Dictionary.php';
		include_once $ultra_path.'/core/src/Container/Getter.php';
	  //  include_once $ultra_path.'/core/src/Container/Kit.php';
	  //  include_once $ultra_path.'/core/src/Container/Set.php';
	  //  include_once $ultra_path.'/core/src/Container/Setter.php';
	  //  include_once $ultra_path.'/core/src/Container/Volume.php';
		include_once $ultra_path.'/core/src/Lang/'.Lang::getMainName().'/Core.php';
		include_once $ultra_path.'/core/src/Lang/'.Lang::getMainName().'/IO.php';
	}
}
