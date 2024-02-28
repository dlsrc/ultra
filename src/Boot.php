<?php declare(strict_types=1);
/**
 * (c) 2005-2024 Dmitry Lebedev <dl@adios.ru>
 * This source code is part of the Ultra library.
 * Please see the LICENSE file for copyright and licensing information.
 */
namespace Ultra;

/**
 * Обнаружение и автоматическая загрузка классов, интерфейсов, трейтов и перечислений.
 * Задействовать другие автозагрузчики не нужно (но возможно).
 * Список (class map) классов, интерфейсов, трейтов и перечислений хранится в файловом
 * реестре, с возможностью выделения из него маленьких списков — веткок реестра в отдельные
 * файлы.
 * По умолчанию, ветка реестра создается для каждого исполняемого скрипта, но, так же,
 * разбиение реестра на списки можно задавать вручную, например, в точках ветвления алгоритма.
 * В многокомпонентных приложениях у каждого компонента может быть своя ветка реестра.
 * Возможно создавать несколько пространств имен и(или) группировать несколько классов в одном
 * файле, например, когда несколько простых объектов часто (или всегда) используются совместно
 * или выстраиваются в единую композицию.
 * Файлы, папки и пространства имен можно называть руководствуясь логикой приложения, а не
 * искуственными правилами. Например, можно собрать классы и интерфейсы из разных пространств
 * имен в одной папке если они композиционно составляют единый компонент приложения.
 */
final class Boot {
	/**
	 * Файл (только имя) возвращающий полный реестр всех классов и интерфейсов.
	 */
	private const string WHOLE_MAP = '00000000000000000000000000000000.php';

	/**
	 * Экземпляр загрузчика.
	 */
	private static self|null $_boot = null;

	/**
	 * Список папок и файлов в которых будет выполняться поиск классов и интерфейсов.
	 */
	public array  $source;

	/**
	 * Папка ./vendor/ultra.
	 */
	public string $basepath;

	/**
	 * Папка, в которую сохраняются файлы возвращающие реестры классов и интерфейсов.
	 * Можно указать абсолютный путь или путь относительно исходного пути.
	 */
	public string $registry;

	/**
	 * Имя файла реестра для текущей ветки реестра.
	 */
	public string $branch_file;

	/**
	 * Имя идентифицирующее текущую ветку реестра.
	 */
	public string $branch_name;

	/**
	 * Список всех задействованых веток реестра классов.
	 */
	public array $involved;

	/**
	 * Список файлов с измененными ветками реестра, которые нужно перезаписать.
	 */
	public array $modified;

	/**
	 * Список путей и файлов (внутри папок поиска) изъятых из процесса сканирования.
	 */
	public array $excluded;

	/**
	 * Файлы, в которых будет выполняться поиск по классам, должны иметь указанные расширения.
	 * Если список пуст, будут просмотрены все файлы во всех папках. 
	 */
	public array $extension;

	/**
	 * Флаг, позволяющий процессу ждать окончания составления полного реестра классов, начатое
	 * другим процессом.
	 * Актуально для консольных приложений запускаемых по расписанию.
	 * см. Ultra\Registry
	 */
	public bool $wait; 

	/**
	 * Подключение папок библиотек для включения файлов с исходным кодом в реестр классов.
	 * $basepath - Папка с которой начинается поиск файлов с исходным кодом.
	 * Если передано значение NULL, то  начальным будет каталог в котором находится корневая
	 * папка библиотек Ultra. В системах использующих Composer она будет соответствовать папке vendor.
	 * $soource - Список путей к подключаемым библиотекам. Пути нужно указывать относительно
	 * папки $basepath, то есть если $basepath указан как '/', то $source будет содержать список
	 * абсолютных путей.
	 * $excluded - Список папок и файлов, исключаемых из поиска. Пути нужно указываются
	 * относительно $basepath.
	 */
	public static function map(array $source = [], array $excluded = [], string|null $basepath = null): void {
		$ultra_path  = dirname(__DIR__, 2);
		$vendor_path = dirname($ultra_path);

		if (!isset($basepath)) {
			$basepath = $vendor_path;
		}

		if (empty($source)) {
			$source[] = strtr($basepath, '\\', '/').'/';
		}
		else {
			self::_prepareFolders($source, $basepath);
		}

		self::_prepareFolders($excluded, $basepath);

		if (self::$_boot) {
			self::$_boot->attach($source, $excluded);
		}
		else {
			$ultra_path = strtr($ultra_path, '\\', '/').'/';
			self::$_boot = new Boot($ultra_path, $source, $excluded);
			spl_autoload_register(self::$_boot->load(...), true, true);

			self::$_boot->attach([
				$ultra_path.'core/src/',
				$ultra_path.'enum-cases/src/',
				$ultra_path.'enum-dominant/src/',
				$ultra_path.'result/src/',
			]);
		}
	}

	/**
	 * Получить путь к исходной папке загрузчика.
	 */
	public static function path(): string {
		if (self::$_boot) {
			return self::$_boot->basepath;
		}

		//return strtr(dirname(realpath($_SERVER['SCRIPT_FILENAME'])), '\\', '/');
		return strtr(dirname(__DIR__, 2), '\\', '/').'/';
	}

	/**
	 * Переключиться на другую ветку реестра классов.
	 * $name - строка с условным наименованием ветки.
	 * $load - флаг немедленной загрузки всех классов перечисленных в ветке. По умолчанию
	 * FALSE - классы загружаются по требованию (__autoload()).
	 */
	public static function branch(string $name, bool $load = false): void {
		if (self::$_boot) {
			self::$_boot->changeBranch($name);

			//if ($load) {
				// TODO: Необходимо до возврата из веток карт памяти
				// добавлять блок с включениями файлов в правильном порядке.
				// Порядок подключений файлов важен.
				//self::$_boot->includeBranch();
			//}
		}
	}

	/**
	 * Изменить список расширений для файлов поиска.
	 */
	public static function extension(string ...$extension): void {
		if (self::$_boot) {
			if (empty($extension)) {
				self::$_boot->extension = [];
			}
			else {
				self::$_boot->extension = $extension;
			}
		}
	}

	/**
	 * Найти файл класса.
	 * Вернет полный путь до файла класса.
	 * $class  - Имя класса.
	 * $remake - Создать заново реестр классов, если класс не найден
	 */
	public static function find(string $class, bool $remake = true): string {
		if (self::$_boot) {
			return self::$_boot->getClassPath($class, $remake);
		}

		return '';
	}

	/**
	 * Изменить флаг ожидания процессом окончания процедуры поиска начатое другим процессом.
	 * $wait  - флаг ожидания.
	 * TRUE  - процесс дождется окончания поиска классов.
	 * FALSE - процесс бросит исключение.
	 */
	public static function wait(bool $wait = true): void {
		if (self::$_boot) {
			self::$_boot->wait = $wait;
		}
	}

	/**
	 * $basepath - исходная папка, от которой строятся относительные пути ко всем
	 * директориям. По умолчанию, если папка специально не задана, то исходной папкой считается
	 * папка исполняемого скрипта.
	 * $registry - путь до папки реестров с результатами поиска.
	 * $source - список всех папок, в которых выпоняется поиск классов, интерфейсов,
	 * типажей и перечмслений. Поиск — рекурсивный. Если список папок пуст, то поиск ведётся
	 * начиная с папки $basepath.
	 * $excluded - список папок исключенных из поиска
	 */
	private function __construct(
		string $basepath,
		array $source,
		array $excluded,
	) {
		$this->basepath     = $basepath;
		$this->registry     = $basepath.'.reg/';
		$this->source       = $source;
		$this->branch_file  = '';
		$this->branch_name  = '';
		$this->involved     = [];
		$this->modified     = [];
		$this->excluded     = $excluded;
		$this->excluded[]   = $this->registry;
		$this->extension    = ['php'];
		$this->wait         = false;
		$this->changeBranch($_SERVER['SCRIPT_FILENAME']);
	}

	/**
	 * Перезаписывает файлы реестра при необходимости.
	 */
	public function __destruct() {
		if (empty($this->modified)) {
			return;
		}

		if (!IO::isdir($this->registry)) {
			return;
		}

		$mode = Mode::Develop->setMain();

		$e = new Exporter;
		$p = Core::pattern('src_header');
		$date = date('Y');
		$last = date('Y-m-d H:i:s');

		foreach ($this->modified as $file => $branch) {
			if (self::WHOLE_MAP == $file) {
				continue;
			}

			ksort($this->involved[$file]);

			$e->setFilename($this->registry.$file);

			$e->save(
				$this->involved[$file],
				$p->replace(
					$branch,
					$date,
					$last,
					PHP_MAJOR_VERSION.'.'.PHP_MINOR_VERSION
				)
			);
		}

		$mode->setMain();
	}

	/**
	 * Добавить каталоги поиска и исключений в существующие списки
	 */
	public function attach(array $source, array $excluded = []): void {
		if (!empty($source)) {
			$this->source = array_unique(array_merge($this->source, $source));
		}

		if (!empty($excluded)) {
			$this->excluded = array_unique(array_merge($this->excluded, $excluded));
		}
	}

	/**
	 * Записать свежий реестр классов, переданный из Ultra\Registry->rehash(Boot $b).
	 */
	public function addRegister(array $register): void {
		$this->involved[self::WHOLE_MAP] = $register;
		$this->modified[self::WHOLE_MAP] = Core::message('h_registry');

		ksort($this->involved[self::WHOLE_MAP]);

		$mode = Mode::Develop->setMain();

		(new Exporter($this->registry.self::WHOLE_MAP))->save(
			$this->involved[self::WHOLE_MAP],
			Core::pattern('src_header')->replace(
				$this->modified[self::WHOLE_MAP],
				date('Y'),
				date('Y-m-d H:i:s'),
				PHP_MAJOR_VERSION.'.'.PHP_MINOR_VERSION
			)
		);

		$mode->setMain();
	}

	/**
	 * Подключить файл и проверить доступность класса, интерфейса, трейта или перечисления
	 * (проверка без использования автозагрузчика).
	 * $class - имя класса, интерфейса, трейта или перечисления. Вернет TRUE если класс
	 * доступен, FALSE - если нет.
	 */
	private function isClass(string $class): bool {
		if (file_exists($this->involved[$this->branch_file][$class])) {
			include_once $this->involved[$this->branch_file][$class];

			if (class_exists($class, false)) {
				return true;
			}

			if (interface_exists($class, false)) {
				return true;
			}

			if (trait_exists($class, false)) {
				return true;
			}

			if (enum_exists($class, false)) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Проверить попадание класса в текущую ветку реестра.
	 * Если класс отсутствует в текущей ветке, но находится в общем списке классов, он будет
	 * включен в текущую ветку. Файл измененной ветки будет перезаписан в деструкторе.
	 * $class - имя класса, интерфейса, трейта или перечисления. Вернет TRUE если класс
	 * зарегистрирован в текущей ветке, FALSE - если класс нигде не обнаружен.
	 */
	private function isRegistered(string $class): bool {
		if (isset($this->involved[self::WHOLE_MAP][$class])) {
			$this->involved[$this->branch_file][$class] = $this->involved[self::WHOLE_MAP][$class];

			if ($this->isClass($class)) {
				$this->modified[$this->branch_file] ??= $this->branch_name;
				return true;
			}
		}

		return false;
	}

	/**
	 * Запросить полную перезапись реестра.
	 * см. Ultra\Registry
	 */
	private function reboot(): void {
		include_once __DIR__.'/registry.php';
		Registry::build($this);
	}

	/**
	 * Загрузить указанный класс.
	 * Функция автозагрузчика (см. Ultra\Boot::map()).
	 * $class - имя класса, интерфейса, трейта или перечисления.
	 */
	public function load(string $class): void {
		if (isset($this->involved[$this->branch_file][$class])) {
			if ($this->isClass($class)) {
				// Класс успешно загружен из текущей ветки реестра
				return;
			}

			$this->involved[$this->branch_file] = [];
			$this->modified[$this->branch_file] ??= $this->branch_name;
		}

		if (!isset($this->involved[self::WHOLE_MAP])) {
			if (is_readable($this->registry.self::WHOLE_MAP)) {
				$this->involved[self::WHOLE_MAP] = include $this->registry.self::WHOLE_MAP;

				if (!is_array($this->involved[self::WHOLE_MAP])) {
					$this->involved[self::WHOLE_MAP] = [];
				}
			}
			else {
				$this->involved[self::WHOLE_MAP] = [];
			}
		}

		if ($this->isRegistered($class)) {
			// Класс успешно загружен после подключения к главной ветке реестра
			return;
		}

		if (in_array(self::WHOLE_MAP, $this->modified)) {
			// Класс до сих пор не загружен и нет возможности перезагрузки реестра.
			if ($this->noOtherAutoloaders()) {
				$this->errorLoad($class);
			}

			return;
		}

		$this->reboot();

		if ($this->isRegistered($class)) {
			// После обновления реестра класс успешно загружен.
			return;
		}

		if ($this->noOtherAutoloaders()) {
			$this->errorLoad($class);
		}
	}

	/**
	 * Убедиться в отсутствии других функций автозагрузки.
	 */
	private function noOtherAutoloaders(): bool {
		if ($list = spl_autoload_functions()) {
			if (1 == count($list)) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Объявляет фатальную ошибку, которая приводит к завершению выполнения программы.
	 * $class - имя ненайденного класса, интерфейса, трейта или перечисления.
	 */
	private function errorLoad(string $class): never {
		Error::log(Core::message('e_load', $class), Status::Noclass, true);
	}

	/**
	 * Смена ветки реестра.
	 * Вызывается из Ultra\Boot::branch().
	 * $name - строка с условным наименованием ветки
	 */
	public function changeBranch(string $name): void {
		$this->branch_name = $name;
		$this->branch_file = md5($name).'.php';

		if (is_readable($this->registry.$this->branch_file)) {
			$this->involved[$this->branch_file] = include $this->registry.$this->branch_file;

			if (!is_array($this->involved[$this->branch_file])) {
				$this->involved[$this->branch_file] = [];
			}
		}
		else {
			$this->involved[$this->branch_file] = [];
		}
	}

	/**
	 * Подключить все файлы перечисленные в ветке
	 * Вызывается из Ultra\Boot::branch().
	 */
	//public function includeBranch(): void {
		// Метод больше не имеет смысла.
		// Останется заглушка до завершения описанного
		// в методе Boot::branch().
		//return;
		/*foreach ($this->involved[$this->branch_file] as $file) {
			if (file_exists($file)) {
				include_once $file;
			}
		}*/
	//}

	/**
	 * Вернуть полный путь к файлу указанного класса или пустую строку, если класс не загружен.
	 * Вызывается из Ultra\Boot::find().
	 * $class - имя искомого класса, интерфейса, трейта или перечисления.
	 * $remake - флаг перегрузки реестра
	 */
	public function getClassPath(string $class, bool $remake): string {
		if (isset($this->involved[$this->branch_file][$class])
		&& file_exists($this->involved[$this->branch_file][$class])
		) {
			return $this->involved[$this->branch_file][$class];
		}

		if (!isset($this->involved[self::WHOLE_MAP])) {
			if (is_readable($this->registry.self::WHOLE_MAP)) {
				$this->involved[self::WHOLE_MAP] = include $this->registry.self::WHOLE_MAP;

				if (!is_array($this->involved[self::WHOLE_MAP])) {
					$this->involved[self::WHOLE_MAP] = [];
				}
			}
			else {
				$this->involved[self::WHOLE_MAP] = [];

				if (!$remake && !isset($this->modified[self::WHOLE_MAP])) {
					$remake = true;
				}
			}
		}

		if (isset($this->involved[self::WHOLE_MAP][$class])
		&& file_exists($this->involved[self::WHOLE_MAP][$class])
		) {
			return $this->involved[self::WHOLE_MAP][$class];
		}

		if (isset($this->modified[self::WHOLE_MAP])) {
			return '';
		}

		if (!$remake) {
			return '';
		}

		$this->reboot();

		if (isset($this->involved[self::WHOLE_MAP][$class])) {
			return $this->involved[self::WHOLE_MAP][$class];
		}

		return '';
	}

	private static function _prepareFolders(array &$folders, string $basepath): void {
		foreach ($folders as $id => $folder) {
			if (str_contains($folder, '..')) {
				unset($folders[$id]);
				continue;
			}

			if (str_starts_with($folder, './')) {
				$folder = mb_substr($folder, 1);
			}
			elseif (!str_starts_with($folder, '/')) {
				$folder = '/'.$folder;
			}

			$folder = str_replace(['\\', '//'], ['/', '/'], $basepath.$folder);

			if (file_exists($folder)) {
				$folders[$id] = $folder.'/';
			}
			else {
				unset($folders[$id]);
			}
		}
	}
}
