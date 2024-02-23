<?php declare(strict_types=1);
/**
 * (c) 2005-2024 Dmitry Lebedev <dl@adios.ru>
 * This source code is part of the Ultra library.
 * Please see the LICENSE file for copyright and licensing information.
 */
namespace Ultra\Lang\en;

use Ultra\Core as Ultra;
use Ultra\Generic\Getter;

final class Core extends Getter {
	protected function initialize(): void {
		$this->_property['success']    = 'All correct.';

		$this->_property['e_ext']      =
		'The program requires the extension "{0}" to work.';

		$this->_property['e_class']    =
		'The included file "{0}" returned an object of class "{1}". '.
		'An object of class "{2}" was expected.';

		$this->_property['e_type']     =
		'The included file "{0}" returned the invalid data type "{1}". '.
		'The object was expected.';

		$this->_property['e_load']     =
		'Interface (class, trait) "{0}" was not found during loading.';

		$this->_property['h_registry'] =
		'Complete registry of classes and interfaces.';

		$this->_property['e_ftok']     =
		'Unable to convert path "{0}" and project ID "{1}" to System V IPC key.';

		$this->_property['w_trace']    = 'Trace';
		$this->_property['w_file']     = 'File';
		$this->_property['w_line']     = 'Line';
		$this->_property['w_context']  = 'Context';
		$this->_property['w_invoker']  = 'Invoker';

		$this->_property['src_header'] = Ultra::get()->srcHeader();
	}
}
