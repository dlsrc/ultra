<?php declare(strict_types=1);
/**
 * (c) 2005-2023 Dmitry Lebedev <dl@adios.ru>
 * This source code is part of the Ultra package core library.
 * Please see the LICENSE file for copyright and licensing information.
 */
namespace ultra;

trait SearchingCaseByName {
	/**
	 * Найти вариант перечисления по имени варианта для текущего типа. Вернуть вариант
	 * перечисления с указанным именем, либо значение NULL, если в текущем перечислении вариант
	 * отсутствует.
	 */
	final public static function byName(string $name): static|null {
		foreach(self::cases() as $case) {
			if ($name == $case->name) {
				return $case;
			}
		}

		return null;
	}
}

trait SearchingCaseByDefault {
	/**
	 * Возвращает первый вариант из списка вариантов текщего перечисления.
	 * Если это поведение нужно изменить метод переопределяется в коде перечисления.
	 */
	public static function byDefault(): static {
		return self::cases()[0];
	}
}

trait SearchingCase {
	use SearchingCaseByName;
	use SearchingCaseByDefault;

	/**
	 * Проверить значения массива на принадлежность хотя бы одного из них к варианту текущего
	 * перечисления.
	 * $name  - строка имени варианта перечисления.
	 * $cases - массив в котором должен присутствовать вариант перечисления.
	 */
	final public static function inCases(string $name, array $cases): bool {
		if (!$case = self::byName($name)) {
			return false;
		}

		return in_array($case, $cases);
	}
}
