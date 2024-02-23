<?php declare(strict_types=1);
/**
 * (c) 2005-2024 Dmitry Lebedev <dl@adios.ru>
 * This source code is part of the Ultra library.
 * Please see the LICENSE file for copyright and licensing information.
 */
namespace Ultra\Generic;

/**
 * Реализация интерфейса Ultra\Property\\Storable.
 */
trait Filename {
	private string $_file;

	public function getFilename(): string {
		return $this->_file;
	}

	public function setFilename(string $file): void {
		if ('' != $file && IO::indir($file)) {
			$this->_file = strtr(realpath(dirname($file)).'/'.basename($file), '\\', '/');
		}
	}
}
