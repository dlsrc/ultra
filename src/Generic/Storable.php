<?php declare(strict_types=1);
/**
 * (c) 2005-2024 Dmitry Lebedev <dl@adios.ru>
 * This source code is part of the Ultra library.
 * Please see the LICENSE file for copyright and licensing information.
 */
namespace Ultra\Generic;

/**
 * Интерфейс объекта с возможностью сохранять что-то в файл.
 * Обычно под этим подразумевается способность объекта сохранить свою экспортную копию,
 * но принципиально нет никакой разницы что будет сохраняться.
 */
interface Storable {
	public function getFilename(): string;
	public function setFilename(string $file): void;
}
