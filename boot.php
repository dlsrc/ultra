<?php declare(strict_types=1);
/**
 * (c) 2005-2023 Dmitry Lebedev <dl@adios.ru>
 * This source code is part of the Ultra package core library.
 * Please see the LICENSE file for copyright and licensing information.
 */
namespace ultra;

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
	public array  $code_library;

	/**
	 * Исходная папка, относительно которой будут выполняться все действия загрузчика.
	 */
	public string $initial_path;

	/**
	 * Папка, в которую сохраняются файлы возвращающие реестры классов и интерфейсов.
	 * Можно указать абсолютный путь или путь относительно исходного пути.
	 */
	public string $registry_folder;

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
	public array $involved_branch;

	/**
	 * Список файлов с измененными ветками реестра, которые нужно перезаписать.
	 */
	public array $modified_branch;

	/**
	 * Список путей и файлов (внутри папок поиска) изъятых из процесса сканирования.
	 */
	public array $excluded_folder;

	/**
	 * Файлы, в которых будет выполняться поиск по классам, должны иметь указанные расширения.
	 * Если список пуст, будут просмотрены все файлы во всех папках. 
	 */
	public array $extension;

	/**
	 * Флаг, позволяющий процессу ждать окончания составления полного реестра классов, начатое
	 * другим процессом.
	 * Актуально для консольных приложений запускаемых по расписанию.
	 * см. ultra\Registry
	 */
	public bool $wait; 

	/**
	 * Метод инициализации и настройки загрузчика.
	 * 
	 * $initial_path - исходная папка, от которой строятся относительные пути ко всем
	 * директориям. По умолчанию, если папка специально не задана, то исходной папкой считается
	 * папка исполняемого скрипта.
	 * $registry_folder - путь до папки реестров с результатами поиска.
	 * $code_library - список всех папок, в которых выпоняется поиск классов, интерфейсов,
	 * типажей и перечмслений. Поиск — рекурсивный. Если список папок пуст, то поиск ведётся
	 * начиная с папки $initial_path.
	 * $excluded_folder - список папок исключенных из поиска
	 */
	public static function start(
		string $initial_path    = '',
		string $registry_folder = '',
		array  $code_library    = [],
		array  $excluded_folder = [],
	): void {
		if (self::$_boot) {
			return;
		}

		if ('' == $initial_path) {
			$initial_path = \strtr(\dirname(\realpath($_SERVER['SCRIPT_FILENAME'])), '\\', '/');
		}
		else {
			$initial_path = \strtr(\realpath($initial_path), '\\', '/');
		}

		$fn_abs = function (string $dir) use ($initial_path): string {
			if ('' == $dir) {
				return '';
			}

			if (\str_starts_with($dir, './') || \str_starts_with($dir, '*/')) {
				$dir = \mb_substr($dir, 2);
			}

			if ('' == $dir || '.' == $dir || '*' == $dir) {
				return $initial_path;
			}

			if ($count = \substr_count($dir, '../')) {
				$dir = \strtr(\dirname($initial_path, $count), '\\', '/').'/'.\str_replace('../', '', $dir);

				if (\file_exists($dir)) {
					return $dir;
				}
				else {
					return '';
				}
			}

			if (!\str_starts_with($dir, '/') && \file_exists($initial_path.'/'.$dir)) {
				return $initial_path.'/'.$dir;
			}

			if (\file_exists($dir)) {
				return \strtr(\realpath($dir), '\\', '/');
			}

			return '';
		};

		$fn_filter = function(array &$dir) use ($fn_abs): void {
			foreach (\array_keys($dir) as $id) {
				$dir[$id] = $fn_abs($dir[$id]);

				if ('' == $dir[$id]) {
					unset($dir[$id]);
					continue;
				}

				if (\is_dir($dir[$id]) && !\str_ends_with($dir[$id], '/')) {
					$dir[$id] = $dir[$id].'/';
				}
			}
		};

		if (empty($code_library)) {
			$code_library[] = $initial_path;
		}

		$fn_filter($code_library);

		if (empty($code_library) || !\in_array(\strtr(__DIR__, '\\', '/').'/', $code_library)) {
			$code_library[] = \strtr(__DIR__, '\\', '/').'/';
		}

		$fn_filter($excluded_folder);

		if ('' == $registry_folder) {
			$registry_folder = $initial_path.'/.reg/';
		}
		else {
			$registry_folder = $fn_abs($registry_folder);

			if ('' == $registry_folder) {
				$registry_folder = $initial_path.'/.reg/';
			}
			else {
				$registry_folder = $registry_folder.'/.reg/';
			}
		}

		self::$_boot = new Boot($initial_path, $registry_folder, $code_library, $excluded_folder);

		\spl_autoload_register([self::$_boot, 'load'], true, true);
	}

	/**
	 * Получить путь к исходной папке загрузчика.
	 */
	public static function path(): string {
		if (self::$_boot) {
			return self::$_boot->initial_path;
		}

		return \strtr(\dirname(\realpath($_SERVER['SCRIPT_FILENAME'])), '\\', '/');
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

			if ($load) {
				self::$_boot->includeBranch();
			}
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
	 * $initial_path - исходная папка, от которой строятся относительные пути ко всем
	 * директориям. По умолчанию, если папка специально не задана, то исходной папкой считается
	 * папка исполняемого скрипта.
	 * $registry_folder - путь до папки реестров с результатами поиска.
	 * $code_library - список всех папок, в которых выпоняется поиск классов, интерфейсов,
	 * типажей и перечмслений. Поиск — рекурсивный. Если список папок пуст, то поиск ведётся
	 * начиная с папки $initial_path.
	 * $excluded_folder - список папок исключенных из поиска
	 */
	private function __construct(
		string $initial_path,
		string $registry_folder,
		array $code_library,
		array $excluded_folder,
	) {
		$this->initial_path      = $initial_path;
		$this->registry_folder   = $registry_folder;
		$this->code_library      = $code_library;
		$this->branch_file       = '';
		$this->branch_name       = '';
		$this->involved_branch   = [];
		$this->modified_branch   = [];
		$this->excluded_folder   = $excluded_folder;
		$this->excluded_folder[] = $this->registry_folder;
		$this->extension         = ['php'];
		$this->wait              = false;
		$this->changeBranch($_SERVER['SCRIPT_FILENAME']);
	}

	/**
	 * Перезаписывает файлы реестра при необходимости.
	 */
	public function __destruct() {
		if (empty($this->modified_branch)) {
			return;
		}

		if (!IO::isdir($this->registry_folder)) {
			return;
		}

		$mode = Mode::Develop->prefer();

		$e = new Exporter;
		$p = Core::pattern('src_header');
		$date = \date('Y');
		$last = \date('Y-m-d H:i:s');

		foreach ($this->modified_branch as $file => $branch) {
			if (self::WHOLE_MAP == $file) {
				continue;
			}

			\ksort($this->involved_branch[$file]);

			$e->setFilename($this->registry_folder.$file);

			$e->save(
				$this->involved_branch[$file],
				$p->replace(
					$branch,
					$date,
					$last,
					\PHP_MAJOR_VERSION.'.'.\PHP_MINOR_VERSION
				)
			);
		}

		$mode->prefer();
	}

	/**
	 * Записать свежий реестр классов, переданный из ultra\Registry->rehash(Boot $b).
	 */
	public function addRegister(array $register): void {
		$this->involved_branch[self::WHOLE_MAP] = $register;
		$this->modified_branch[self::WHOLE_MAP] = Core::message('h_registry');

		\ksort($this->involved_branch[self::WHOLE_MAP]);

		$mode = Mode::Develop->prefer();

		(new Exporter($this->registry_folder.self::WHOLE_MAP))->save(
			$this->involved_branch[self::WHOLE_MAP],
			Core::pattern('src_header')->replace(
				$this->modified_branch[self::WHOLE_MAP],
				\date('Y'),
				\date('Y-m-d H:i:s'),
				\PHP_MAJOR_VERSION.'.'.\PHP_MINOR_VERSION
			)
		);

		$mode->prefer();
	}

	/**
	 * Подключить файл и проверить доступность класса, интерфейса, трейта или перечисления
	 * (проверка без использования автозагрузчика).
	 * $class - имя класса, интерфейса, трейта или перечисления. Вернет TRUE если класс
	 * доступен, FALSE - если нет.
	 */
	private function isClass(string $class): bool {
		if (\file_exists($this->involved_branch[$this->branch_file][$class])) {
			include_once $this->involved_branch[$this->branch_file][$class];

			if (\class_exists($class, false)) {
				return true;
			}

			if (\interface_exists($class, false)) {
				return true;
			}

			if (\trait_exists($class, false)) {
				return true;
			}

			if (\enum_exists($class, false)) {
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
		if (isset($this->involved_branch[self::WHOLE_MAP][$class])) {
			$this->involved_branch[$this->branch_file][$class] = $this->involved_branch[self::WHOLE_MAP][$class];

			if ($this->isClass($class)) {
				$this->modified_branch[$this->branch_file] ??= $this->branch_name;
				return true;
			}
		}

		return false;
	}

	/**
	 * Запросить полную перезапись реестра.
	 * см. ultra\Registry
	 */
	private function reboot(): void {
		include_once __DIR__.'/registry.php';
		Registry::build($this);
	}

	/**
	 * Загрузить указанный класс.
	 * Функция автозагрузчика (см. ultra\Boot::start()).
	 * $class - имя класса, интерфейса, трейта или перечисления.
	 */
	public function load(string $class): void {
		if (isset($this->involved_branch[$this->branch_file][$class])) {
			if ($this->isClass($class)) {
				// Класс успешно загружен из текущей ветки реестра
				return;
			}

			$this->involved_branch[$this->branch_file] = [];
			$this->modified_branch[$this->branch_file] ??= $this->branch_name;
		}

		if (!isset($this->involved_branch[self::WHOLE_MAP])) {
			if (\is_readable($this->registry_folder.self::WHOLE_MAP)) {
				$this->involved_branch[self::WHOLE_MAP] = include $this->registry_folder.self::WHOLE_MAP;

				if (!\is_array($this->involved_branch[self::WHOLE_MAP])) {
					$this->involved_branch[self::WHOLE_MAP] = [];
				}
			}
			else {
				$this->involved_branch[self::WHOLE_MAP] = [];
			}
		}

		if ($this->isRegistered($class)) {
			// Класс успешно загружен после подключения к главной ветке реестра
			return;
		}

		if (\in_array(self::WHOLE_MAP, $this->modified_branch)) {
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
		if ($list = \spl_autoload_functions()) {
			if (1 == \count($list)) {
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
		//Mutex::clean();
		Error::log(Core::message('e_load', $class), Status::Noclass, true);
	}

	/**
	 * Смена ветки реестра.
	 * Вызывается из ultra\Boot::branch().
	 * $name - строка с условным наименованием ветки
	 */
	public function changeBranch(string $name): void {
		$this->branch_name = $name;
		$this->branch_file = \md5($name).'.php';

		if (\is_readable($this->registry_folder.$this->branch_file)) {
			$this->involved_branch[$this->branch_file] = include $this->registry_folder.$this->branch_file;

			if (!\is_array($this->involved_branch[$this->branch_file])) {
				$this->involved_branch[$this->branch_file] = [];
			}
		}
		else {
			$this->involved_branch[$this->branch_file] = [];
		}
	}

	/**
	 * Подключить все файлы перечисленные в ветке
	 * Вызывается из ultra\Boot::branch().
	 */
	public function includeBranch(): void {
		foreach ($this->involved_branch[$this->branch_file] as $file) {
			if (\file_exists($file)) {
				include_once $file;
			}
		}
	}

	/**
	 * Вернуть полный путь к файлу указанного класса или пустую строку, если класс не загружен.
	 * Вызывается из ultra\Boot::find().
	 * $class - имя искомого класса, интерфейса, трейта или перечисления.
	 * $remake - флаг перегрузки реестра
	 */
	public function getClassPath(string $class, bool $remake): string {
		if (isset($this->involved_branch[$this->branch_file][$class])
		&& \file_exists($this->involved_branch[$this->branch_file][$class])
		) {
			return $this->involved_branch[$this->branch_file][$class];
		}

		if (!isset($this->involved_branch[self::WHOLE_MAP])) {
			if (\is_readable($this->registry_folder.self::WHOLE_MAP)) {
				$this->involved_branch[self::WHOLE_MAP] = include $this->registry_folder.self::WHOLE_MAP;

				if (!\is_array($this->involved_branch[self::WHOLE_MAP])) {
					$this->involved_branch[self::WHOLE_MAP] = [];
				}
			}
			else {
				$this->involved_branch[self::WHOLE_MAP] = [];

				if (!$remake && !isset($this->modified_branch[self::WHOLE_MAP])) {
					$remake = true;
				}
			}
		}

		if (isset($this->involved_branch[self::WHOLE_MAP][$class])
		&& \file_exists($this->involved_branch[self::WHOLE_MAP][$class])
		) {
			return $this->involved_branch[self::WHOLE_MAP][$class];
		}

		if (isset($this->modified_branch[self::WHOLE_MAP])) {
			return '';
		}

		if (!$remake) {
			return '';
		}

		$this->reboot();

		if (isset($this->involved_branch[self::WHOLE_MAP][$class])) {
			return $this->involved_branch[self::WHOLE_MAP][$class];
		}

		return '';
	}
}
