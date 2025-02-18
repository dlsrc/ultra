<?php declare(strict_types=1);
/**
 * (c) 2005-2025 Dmitry Lebedev <dl@adios.ru>
 * This source code is part of the Ultra library.
 * Please see the LICENSE file for copyright and licensing information.
 */
namespace Ultra\Lang\ja;

use Ultra\Getter;

final class IO extends Getter {
	protected function initialize(): void {
		$this->_property['e_chmod']     = 'Failed to change file access mode "{0}".';
		$this->_property['e_copy']      = 'Error copying file "{0}" from "{1}" to "{2}".';
		$this->_property['e_dir']       = 'Directory "{0}" does not exist.';
		$this->_property['e_file']      = 'File "{0}" does not exist.';
		$this->_property['e_make_dir']  = 'Error creating directory "{0}".';
		$this->_property['e_make_file'] = 'Unable to create file "{0}". '.
			'Perhaps this is due to the rights to the folder in which the file is created.';
		$this->_property['e_rename']    = 'Error transferring file "{0}" to "{1}".';
		$this->_property['e_rmdir']     = 'Unable to delete directory "{0}".';
		$this->_property['e_unlink']    = 'Unable to delete file "{0}".';
	}
}
