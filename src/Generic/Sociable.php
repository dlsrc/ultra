<?php declare(strict_types=1);
/**
 * (c) 2005-2025 Dmitry Lebedev <dl@adios.ru>
 * This source code is part of the Ultra library.
 * Please see the LICENSE file for copyright and licensing information.
 */
namespace Ultra\Generic;

/**
 * Интерфейс построения многоязычных сообщений.
 */
interface Sociable {
	public static function message(string $code, string...$context): string;
	public static function pattern(string $code): Template;
	public static function info(): Immutable|null;
}
