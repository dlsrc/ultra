<?php declare(strict_types=1);
/**
 * (c) 2005-2025 Dmitry Lebedev <dl@adios.ru>
 * This source code is part of the Ultra library.
 * Please see the LICENSE file for copyright and licensing information.
 */
namespace Ultra\Generic;

use Ultra\Exportable;
/**
 * Интерфейс именованных, импортируемых из файла объектов.
 */
interface ImportableNamed extends Exportable {
	/**
	 * 
	 */
	public static function load(string $file, string $name = ''): static;
	/**
	 * 
	 */
	public static function find(string $file, string $name = '', bool $log = false): static|null;
	/**
	 * Перечитать себя заново из файла, если интерфейс Storable поддерживается.
	 * Пытаться перезаписать себя в контейнерный пул и вернуть полученный объект как интерфейс состояния,
	 * либо, в случае ошибки оставить пул объектов без изенения и вернуть интерфейс состояния как объект ошибки.
	 */
	public function refind(): self;
}
