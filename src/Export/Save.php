<?php declare(strict_types=1);
/**
 * (c) 2005-2026 Dmitry Lebedev <dlsrc.extra@gmail.com>
 * This source code is part of the Ultra library.
 * Please see the LICENSE file for copyright and licensing information.
 */
namespace Ultra;

/**
 * Флаг состояния экспортируемого объекта, варианты перечисления обозначают степень
 * необходимости (срочности) сохранить себя.
 */
enum Save {
	/**
	 * Сохранить объект немедленно.
	 */
	case Now;

	/**
	 * Сохранить объект перед уничтожением в любом случае.
	 */
	case Destruct;

	/**
	 * Сохранить объект перед уничтожением, если в процессе выполнения не регистрировались
	 * ошибки.
	 */
	case NoError;

	/**
	 * Сохранять объект не нужно.
	 */
	case Nothing;
}
