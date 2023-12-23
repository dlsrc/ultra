<?php declare(strict_types=1);
/**
 * (c) 2005-2023 Dmitry Lebedev <dl@adios.ru>
 * This source code is part of the Ultra package core library.
 * Please see the LICENSE file for copyright and licensing information.
 */
namespace ultra;

/**
 * Интерфейс предпочитаемого варианта перечисления.
 * Позволяет установить один из вариантов перечисления в качестве основного варианта, что
 * позволяет использовать это значение в разных частях программы без явного на него указания.
 */
interface PreferredCase extends \UnitEnum {
	/**
	 * Вернуть вариант по умолчанию для текущего перечисления.
	 * Необходимо, для установки в качестве основного варианта перечисления, если основной
	 * вариант для перечисления не был выбран.
	 */
	public static function byDefault(): static;

	/**
	 * Установить новый предпочитаемый вариант перечисления и вернуть предыдущий.
	 * Если основной вариант до этого не устанавливался, вернет значение по умолчанию. Если
	 * метод вызван без аргументов, вернуть текущей предпочитаемый выриант, либо значение
	 * по умолчанию.
	 */
	public static function now(self|null $case = null): static;

	/**
	 * Пытается установить предпочитаемый вариант по имени варианта перечисления.
	 * Вернет предыдущий предпочитаемый вариант перечисления.
	 */
	public static function nowByName(string $name): static;

	/**
	 * Вернуть имя предпочитаемого варианта в текущем перечислении.
	 */
	public static function name(): string;
	
	/**
	 * Проверить, является ли текущее значение перечисления основным.
	 */
	public function current(): bool;

	/**
	 * Сделать текущее значение предпочитаемым.
	 */
	public function prefer(): static;
}

/**
 * Реализация методов интерфейса ultra\PreferredCase:
 * Метод nowByName() использует метод byName() из трейта ultra\SearchingCase.
 */
trait CurrentCase {
	use SearchingCase;

	final public static function now(PreferredCase|null $case = null): static {
		static $current = null;

		if (null == $case || $case::class != self::class) {
			return $current ?? self::byDefault();
		}

		$previous = $current ?? self::byDefault();
		$current  = $case;
		return $previous;
	}

	final public static function nowByName(string $name): static {
		return self::now(self::byName($name));
	}

	final public static function name(): string {
		return self::now()->name;
	}

	final public function current(): bool {
		return self::now() === $this;
	}

    final public function prefer(): static {
        return self::now($this);
    }
}
