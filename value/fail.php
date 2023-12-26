<?php declare(strict_types=1);
/**
 * (c) 2005-2023 Dmitry Lebedev <dl@adios.ru>
 * This source code is part of the Ultra package core library.
 * Please see the LICENSE file for copyright and licensing information.
 */
namespace ultra;

/**
 * Класс выражает значение или результат, находящийся в ошибочном, неприемлемом или
 * недоступном для использования состоянии.
 * Хранит основные параметры ошибочного состояния: сообщение об ошибке; статус ошибки;
 * трассировку (опционально); имя файла и номер строки, в которых зафиксировано ошибочное
 * состояние.
 * Класс использует для имплементации интерфейса ultra\Valuable типаж ultra\Suspense.
 */
readonly class Fail implements Valuable {
	use Suspense;

	final public function __construct(
		public Condition $type,
		public string $message,
		public string $file,
		public int $line,
		public array|null $trace = null,
		bool $report_to_core = false,
	) {
		if ($report_to_core) {
			Core::get->registerFailure($this);
		}
	}
}
