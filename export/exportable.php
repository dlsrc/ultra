<?php declare(strict_types=1);
/**
 * (c) 2005-2023 Dmitry Lebedev <dl@adios.ru>
 * This source code is part of the Ultra package core library.
 * Please see the LICENSE file for copyright and licensing information.
 */
namespace ultra;

/**
 * Флаг состояния экспортируемого объекта, варианты перечисления обозначают степень
 * необходимости (срочности) сохранить себя.
 */
enum Save {
	/**
	 * Сохранить объект немедленно.
	 */
	case Now;

	/**
	 * Сохранить объект перед уничтожением в любом случае.
	 */
	case Destruct;

	/**
	 * Сохранить объект перед уничтожением, если в процессе выполнения не регистрировались
	 * ошибки.
	 */
	case NoError;

	/**
	 * Сохранять объект не нужно.
	 */
	case Nothing;
}

/**
 * Интерфейс объектов способных экспортировать себя в файл	как возвращаемый PHP код.
 * (дополнительно можно посмотреть описание для класса ultra\Exporter).
 */
interface Exportable extends Storable, CallableState {
	/**
	 * Класс, реализующий интерфейс ultra\Exportable, должен включать приватное свойство
	 * несущее одно из значений ultra\Save.
	 * Стандартный метод, в котором нужно реализовать экспорт, на основе соответствия значения,
	 * указанного выше приватного свойства, флагам ultra\Save::NoError и ultra\Save::Destruct.
	 */
	public function __destruct();

	/**
	 * Экспортировать себя в указанный файл.
	 * 
	 * $file - полное имя файла, в который выполняется экспорт объекта. По умолчанию пустая
	 * строка, объект будет экспортировать себя в файл вычисленный при создании.
	 * $save - флаг типа ultra\Save, означающий как и когда выполняется експорт.
	 * Необходимо передать одно из значений:
	 * ultra\Save::NoError;
	 * ultra\Save::Destruct;
	 * ultra\Save::Now;
	 * По умолчанию ultra\Save::NoError.
	 * 
	 * Класс, реализующий интерфейс ultra\Exportable, должен самостоятельно вычислять файл
	 * по умолчанию для сохранения объектов на его основе. В большинстве случаев, достаточно
	 * задействовать трейт ultra\Filename, реализующий интерфейс ultra\Storable.
	 */
	public function export(string $file = '', Save $save = Save::NoError): void;

	/**
	 * Сохранить себя в файл по умолчанию.
	 * Файл по умолчанию должен определяется реализацией конкретного класса.
	 * 
	 * $save - флаг типа ultra\Save, означающий как и когда выполняется экспорт.
	 * Необходимо передать одно из значений:
	 * ultra\Save::NoError;
	 * ultra\Save::Destruct;
	 * ultra\Save::Now;
	 * По умолчанию ultra\Save::NoError.
	 */
	public function save(Save $save = Save::NoError): void;

	/**
	 * Обновить экспортную копию объекта.
	 * Используется, если список свойств в контейнере изменился.
	 * 
	 * $save - флаг типа ultra\Save, означающий как и когда выполняется экспорт.
	 * ultra\Save::NoError;
	 * ultra\Save::Destruct;
	 * ultra\Save::Now;
	 * По умолчанию ultra\Save::Now.
	 */
	public function update(Save $save = Save::Now): self;
	
	/**
	 * Перечитать контейнер из файла заново.
	 * Вернёт перечитанный контейнер, либо себя, если файл контейнера более не доступен.
	 */
	public function refind(): self;
}

/**
 * Общая реализация интерфейса ultra\Exportable.
 */
trait OwnExport {
	use Filename;
	use SetStateCall;

	/**
	 * Флаг состояния объекта, указывающий на необходимость экспорта.
	 * Принимает одно из значений:
	 * ultra\Save::Nothing;
	 * ultra\Save::NoError;
	 * ultra\Save::Destruct;
	 * ultra\Save::Now;
	 */
	private Save $_save;

	final public function __destruct() {
		if (Save::Destruct == $this->_save) {
			$this->save(Save::Now);
		}
		elseif (Save::NoError == $this->_save && !Core::get()->isFailure()) {
			$this->save(Save::Now);
		}
	}

	final public function export(string $file = '', Save $save = Save::NoError): void {
		$this->setFilename($file);
		$this->save($save);
	}

	final public function status(): Save {
		return $this->_save;
	}

	final public function save(Save $save = Save::NoError): void {
		if (Save::Now == $save) {
			$this->_save = Save::Nothing;

			if ('' != $this->_file) {
				(new Exporter($this->_file))->save(
					$this,
					Core::message(
						'src_header',
						$this->_file,
						\date('Y'),
						\date('Y-m-d H:i:s'),
						\PHP_MAJOR_VERSION.'.'.\PHP_MINOR_VERSION
					)
				);
			}
		}
		else {
			$this->_save = $save;
		}
	}

	final public function update(Save $save = Save::Now): self {
		$type = \get_class($this);

		if ($this instanceof Named) {
			$name = $this->getName();
			$up = new $type([], $name);
		}
		else {
			$name = $type;
			$up = new $type;
		}

		$up->setFilename($this->_file);

		foreach ($this->_property as $key => $val) {
			$up->$key = $val;
		}

		$up->save($save);
		self::add($up, $name);
		return $up;
	}

	final public function refind(): self {
		if ($this instanceof Named) {
			$name = $this->getName();
			self::drop($name);

			if (!$refind = self::find($this->_file, $name)) {
				self::add($this, $name);
				return $this;
			}
		}
		else {
			$name = \get_class($this);
			self::drop($name);

			if (!$refind = self::find($this->_file)) {
				self::add($this, $name);
				return $this;
			}
		}

		return $refind;
	}
}
