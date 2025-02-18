<?php declare(strict_types=1);
/**
 * (c) 2005-2025 Dmitry Lebedev <dl@adios.ru>
 * This source code is part of the Ultra library.
 * Please see the LICENSE file for copyright and licensing information.
 */
namespace Ultra\Lang\ru;

use Ultra\Getter;

final class IO extends Getter {
	protected function initialize(): void {
		$this->_property['e_chmod']     = 'Не удалось изменить режим доступа к файлу "{0}".';
		$this->_property['e_copy']      = 'Ошибка при копировании файла "{0}" из "{1}" в "{2}".';
		$this->_property['e_dir']       = 'Директория "{0}" не существует.';
		$this->_property['e_file']      = 'Файл "{0}" не существует.';
		$this->_property['e_make_dir']  = 'Ошибка создания директории "{0}".';
		$this->_property['e_make_file'] = 'Невозможно создать файл "{0}". '.
			'Возможно, это связано с правами на папку в которой создается файл.';
		$this->_property['e_rename']    = 'Ошибка при переносе файла "{0}" в "{1}".';
		$this->_property['e_rmdir']     = 'Невозможно удалить директорию "{0}".';
		$this->_property['e_unlink']    = 'Невозможно удалить файл "{0}".';
	}
}
