<?php declare(strict_types=1);
/**
 * (c) 2005-2024 Dmitry Lebedev <dl@adios.ru>
 * This source code is part of the Ultra library.
 * Please see the LICENSE file for copyright and licensing information.
 */
namespace Ultra\Lang\de;

use Ultra\Core as Ultra;
use Ultra\Generic\Getter;

final class Core extends Getter {
	protected function initialize(): void {
		$this->_property['success']    = 'Alles ist okay.';

		$this->_property['e_ext']      =
		'Das Programm benötigt die Erweiterung "{0}", um zu funktionieren.';

		$this->_property['e_class']    =
		'Die eingeschlossene Datei "{0}" hat ein Objekt der Klasse "{1}" '.
		'zurückgegeben. Es wurde ein Objekt der Klasse "{2}" erwartet.';

		$this->_property['e_type']     =
		'Die mitgelieferte Datei "{0}" hat den ungültigen Datentyp "{1}" '.
		'zurückgegeben. Das Objekt wurde erwartet.';

		$this->_property['e_load']     =
		'Schnittstelle (Klasse, Eigenschaft) "{0}" wurde beim Laden '.
		'nicht gefunden.';

		$this->_property['h_registry'] =
		'Vollständige Registrierung von Klassen und Schnittstellen.';

		$this->_property['e_ftok']     =
		'Der Pfad "{0}" und die Projekt-ID "{1}" können nicht in den '.
		'System-V-IPC-Schlüssel konvertiert werden.';

		$this->_property['w_trace']    = 'Verfolgen';
		$this->_property['w_file']     = 'Datei';
		$this->_property['w_line']     = 'Leitung';
		$this->_property['w_context']  = 'Kontext';
		$this->_property['w_invoker']  = 'Revoker';

		$this->_property['src_header'] = Ultra::get()->srcHeader();
	}
}
