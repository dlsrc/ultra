<?php declare(strict_types=1);
/**
 * (c) 2005-2024 Dmitry Lebedev <dl@adios.ru>
 * This source code is part of the Ultra library.
 * Please see the LICENSE file for copyright and licensing information.
 */
namespace Ultra;

enum Code: int implements namespace\Result\Condition {
	// VALUE RANGE 40 - 60
	case Filename = 40; // Неверное имя файла
	case Makedir  = 41; // Ошибка при создании директории
	case Makefile = 42; // Ошибка при создании файла
	case Copy     = 43; // Ошибка при копировании файла
	case Rename   = 44; // Ошибка при переименовании файла
	case Nodir    = 45; // Директория не существует
	case Nofile   = 46; // Файл не существует
	case Rmdir    = 47; // Не удалось удалить директорию
	case Unlink   = 48; // Не удалось удалить файл
	case Chmod    = 49; // Ошибка при изменении режима доступа к папкам и файлам

	public function isFatal(): bool {
		return false;
	}
}