<?php declare(strict_types=1);
/**
 * (c) 2005-2023 Dmitry Lebedev <dl@adios.ru>
 * This source code is part of the Ultra package core library.
 * Please see the LICENSE file for copyright and licensing information.
 */
namespace ultra;

/**
 * Преречисление наиболее общих режимов работы для всех библиотек и приложений.
 * Рекомендуется использовать, если приложения не имеют своей собственной системы
 * классификации режимов работы. Поддерживает интерфейс выбора предпочитаемого (текущего)
 * режима ultra\PreferredCase.
 */
enum Mode implements PreferredCase {
	use CurrentCase;

	case Product;
	case Develop;
	case Test;
	case Error;
}
