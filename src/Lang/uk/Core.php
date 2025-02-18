<?php declare(strict_types=1);
/**
 * (c) 2005-2025 Dmitry Lebedev <dl@adios.ru>
 * This source code is part of the Ultra library.
 * Please see the LICENSE file for copyright and licensing information.
 */
namespace Ultra\Lang\uk;

use Ultra\Core as Ultra;
use Ultra\Getter;

final class Core extends Getter {
	protected function initialize(): void {
		$this->_property['success']    = 'Все в порядке.';

		$this->_property['e_ext']      =
		'Для работы программы требуется расширение "{0}".';

		$this->_property['e_class']    =
		'Подключенный файл "{0}" вернул объект класса "{1}". '.
		'Ожидался объект класса "{2}".';

		$this->_property['e_type']     =
		'Подключенный файл "{0}" вернул неверный тип данных "{1}". '.
		'Ожидался объект.';

		$this->_property['e_load']     =
		'Интерфейс (класс, трейт) "{0}" не был найден в процессе загрузки.';

		$this->_property['h_registry'] =
		'Полный реестр классов и интерфейсов.';

		$this->_property['e_ftok']     =
		'Не удается преобразовать путь "{0}" и идентификатор проекта "{1}" '.
		'в ключ System V IPC.';

		$this->_property['w_trace']    = 'Трассировка';
		$this->_property['w_file']     = 'Файл';
		$this->_property['w_line']     = 'Строка';
		$this->_property['w_context']  = 'Контекст';
		$this->_property['w_invoker']  = 'Вызвавший';

		$this->_property['src_header'] = Ultra::get()->srcHeader();
	}
}
