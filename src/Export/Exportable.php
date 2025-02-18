<?php declare(strict_types=1);
/**
 * (c) 2005-2025 Dmitry Lebedev <dl@adios.ru>
 * This source code is part of the Ultra library.
 * Please see the LICENSE file for copyright and licensing information.
 */
namespace Ultra;

use Ultra\Generic\Storable;

/**
 * Интерфейс объектов способных экспортировать себя в файл	как возвращаемый PHP код.
 * (дополнительно можно посмотреть описание для класса Ultra\Exporter).
 */
interface Exportable extends Storable, CallableState {
	/**
	 * Класс, реализующий интерфейс Ultra\Exportable, должен включать приватное свойство
	 * несущее одно из значений Ultra\Save.
	 * Стандартный метод, в котором нужно реализовать экспорт, на основе соответствия значения,
	 * указанного выше приватного свойства, флагам Ultra\Save::NoError
	 * и Ultra\Save::Destruct.
	 */
	public function __destruct();

	/**
	 * Экспортировать себя в указанный файл.
	 * 
	 * $file - полное имя файла, в который выполняется экспорт объекта. По умолчанию пустая
	 * строка, объект будет экспортировать себя в файл вычисленный при создании.
	 * $save - флаг типа Ultra\Save, означающий как и когда выполняется експорт.
	 * Необходимо передать одно из значений:
	 * Ultra\Save::NoError;
	 * Ultra\Save::Destruct;
	 * Ultra\Save::Now;
	 * По умолчанию Ultra\Save::NoError.
	 * 
	 * Класс, реализующий интерфейс Ultra\Exportable, должен самостоятельно вычислять файл
	 * по умолчанию для сохранения объектов на его основе. В большинстве случаев, достаточно
	 * задействовать трейт Ultra\Generic\Filename, реализующий интерфейс Ultra\Generic\Storable.
	 */
	public function export(string $file = '', Save $save = Save::NoError): void;

	/**
	 * Сохранить себя в файл по умолчанию.
	 * Файл по умолчанию должен определяется реализацией конкретного класса.
	 * 
	 * $save - флаг типа Ultra\Save, означающий как и когда выполняется экспорт.
	 * Необходимо передать одно из значений:
	 * Ultra\Save::NoError;
	 * Ultra\Save::Destruct;
	 * Ultra\Save::Now;
	 * По умолчанию Ultra\Save::NoError.
	 */
	public function save(Save $save = Save::NoError): void;

	/**
	 * Обновить экспортную копию объекта.
	 * Используется, если список свойств в контейнере изменился.
	 * 
	 * $save - флаг типа Ultra\Save, означающий как и когда выполняется экспорт.
	 * Ultra\Save::NoError;
	 * Ultra\Save::Destruct;
	 * Ultra\Save::Now;
	 * По умолчанию Ultra\Save::Now.
	 */
	public function update(Save $save = Save::Now): self;
}
