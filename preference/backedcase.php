<?php declare(strict_types=1);
/**
 * (c) 2005-2023 Dmitry Lebedev <dl@adios.ru>
 * This source code is part of the Ultra package core library.
 * Please see the LICENSE file for copyright and licensing information.
 */
namespace ultra;

/**
 * Методы расширяющие возможности интерфейса ultra\PreferredCase,
 * добавляют дополнительную функциональность в типизированные перечисления.
 */
interface PreferredBackedCase extends \BackedEnum, PreferredCase {
	/**
	 * Получить целое или строковое значение предпочитаемого варианта в текущем перечислении.
	 */
	public static function get(): int|string;

	/**
	 * Пытаться установить вариант в качестве основного (предпочитаемого) в текущем
	 * перечислении с помощью целого или строкового литерала.
	 */
	public static function set(int|string $value): static;
}

/**
 * Реализация интерфейса ultra\PreferredBackedCase.
 * Использует трейт ultra\CurrentCase как реализацию основной части интерфейса
 * ultra\PreferredCase.
 * В перечислении использующем ultra\CurrentBackedCase должен быть определен метод
 * byDefault(), либо в перечислении нужно задействовать трейт ultra\DefaultCase.
 */
trait CurrentBackedCase {
	use CurrentCase;

	final public static function get(): int|string {
		return self::now()->value;
	}

	final public static function set(int|string $value): static {
		return self::now(self::tryFrom($value));
	}
}
