<?php declare(strict_types=1);
/**
 * (c) 2005-2024 Dmitry Lebedev <dl@adios.ru>
 * This source code is part of the Ultra library.
 * Please see the LICENSE file for copyright and licensing information.
 */
namespace Ultra\Export;

use Ultra\Core;
use Ultra\Generic\Filename;
use Ultra\Generic\Called;

/**
 * Общая реализация интерфейса Ultra\Export\Exportable.
 */
trait Replica {
	use Filename;
	use SetState;

	/**
	 * Флаг состояния объекта, указывающий на необходимость экспорта.
	 * Принимает одно из значений:
	 * Ultra\Export\Save::Nothing;
	 * Ultra\Export\Save::NoError;
	 * Ultra\Export\Save::Destruct;
	 * Ultra\Export\Save::Now;
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
						date('Y'),
						date('Y-m-d H:i:s'),
						PHP_MAJOR_VERSION.'.'.PHP_MINOR_VERSION
					)
				);
			}
		}
		else {
			$this->_save = $save;
		}
	}

	final public function update(Save $save = Save::Now): self {
		$type = get_class($this);

		if ($this instanceof Called) {
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

/*	final public function refind(): self {
		if ($this instanceof Called) {
			$name = $this->getName();
			self::drop($name);

			if (!$refind = self::find($this->_file, $name)) {
				self::add($this, $name);
				return $this;
			}
		}
		else {
			$name = get_class($this);
			self::drop($name);

			if (!$refind = self::find($this->_file)) {
				self::add($this, $name);
				return $this;
			}
		}

		return $refind;
	}*/
}
