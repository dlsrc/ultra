<?php declare(strict_types=1);
/**
 * (c) 2005-2024 Dmitry Lebedev <dl@adios.ru>
 * This source code is part of the Ultra library.
 * Please see the LICENSE file for copyright and licensing information.
 */
namespace Ultra\Generic;

use Ultra\IO;

/**
 * Реализация интерфейса Ultra\Property\Storable.
 */
trait Filename {
	public string $filename = '' {
		set(string $file) {
			if ('' != $file && IO::indir($file)) {
				$this->filename = strtr(realpath(dirname($file)).'/'.basename($file), '\\', '/');
			}
		}
	}
}
