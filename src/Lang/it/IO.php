<?php declare(strict_types=1);
/**
 * (c) 2005-2024 Dmitry Lebedev <dl@adios.ru>
 * This source code is part of the Ultra library.
 * Please see the LICENSE file for copyright and licensing information.
 */
namespace Ultra\Lang\it;

use Ultra\Getter;

final class IO extends Getter {
	protected function initialize(): void {
		$this->_property['e_chmod']     = 'Impossibile modificare la modalità di accesso ai file "{0}".';
		$this->_property['e_copy']      = 'Errore durante la copia del file "{0}" da "{1}" a "{2}".';
		$this->_property['e_dir']       = 'La directory "{0}" non esiste.';
		$this->_property['e_file']      = 'Il file "{0}" non esiste.';
		$this->_property['e_make_dir']  = 'Errore durante la creazione della directory "{0}".';
		$this->_property['e_make_file'] = 'Impossibile creare il file "{0}". '.
			'Forse questo è dovuto ai diritti sulla cartella in cui viene creato il file.';
		$this->_property['e_rename']    = 'Errore durante il trasferimento del file "{0}" su "{1}".';
		$this->_property['e_rmdir']     = 'Impossibile eliminare la directory "{0}".';
		$this->_property['e_unlink']    = 'Impossibile eliminare il file "{0}".';
	}
}
