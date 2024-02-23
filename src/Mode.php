<?php declare(strict_types=1);
/**
 * (c) 2005-2024 Dmitry Lebedev <dl@adios.ru>
 * This source code is part of the Ultra library.
 * Please see the LICENSE file for copyright and licensing information.
 */
namespace Ultra;

use Ultra\Dominant\UnitEnum as Dominant;
use Ultra\Dominant\UnitCase;
/**
 * Преречисление наиболее общих режимов работы для всех библиотек и приложений.
 * Рекомендуется использовать, если приложения не имеют своей собственной системы
 * классификации режимов работы. Поддерживает интерфейс выбора предпочитаемого (текущего)
 * режима Ultra\PreferredCase.
 */
enum Mode implements Dominant {
	use UnitCase;

	case Product;
	case Develop;
	case Test;
	case Error;
}
