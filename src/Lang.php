<?php declare(strict_types=1);
/**
 * (c) 2005-2024 Dmitry Lebedev <dl@adios.ru>
 * This source code is part of the Ultra library.
 * Please see the LICENSE file for copyright and licensing information.
 */
namespace Ultra;

use Ultra\Enum\BackedDominant;
use Ultra\Enum\BackedDominantCase;


enum Lang: int implements BackedDominant {
	use BackedDominantCase;

	case ru = 1;
	case en = 2;
	case ar = 3;
	case es = 4;
	case zh = 5;
	case fr = 6;
	case de = 7;
	case ja = 8;
	case it = 9;
	case pt = 10;
	case uk = 11;

	#[\Override]
	public static function getDefaultCase(): static {
		return self::en;
	}

	public function title(): string {
		return match($this) {
			self::en => 'English',
			self::ru => 'Русский',
			self::ja => '日本語',
			self::de => 'Deutsch',
			self::es => 'Español',
			self::fr => 'Français',
			self::pt => 'Português',
			self::it => 'Italiano',
			self::ar => 'اللغة العربية',
			self::zh => '中文',
			self::uk => 'Українська',
		};
	}
}
