<?php declare(strict_types=1);
/**
 * (c) 2005-2023 Dmitry Lebedev <dl@adios.ru>
 * This source code is part of the Ultra package core library.
 * Please see the LICENSE file for copyright and licensing information.
 */
namespace ultra;

enum Status: int implements Condition {
	case Success          = 0;                    // Ошибки отсутствуют.
	// Коды ошибок генерируемых ядром PHP
	case Error            = \E_ERROR;             // [1] Фатальная ошибка времени выполнения.
	case Warning          = \E_WARNING;           // [2] Предупреждение времени выполнения.
	case Parse            = \E_PARSE;             // [4] Ошибка на этапе компиляции.
	case Notice           = \E_NOTICE;            // [8] Уведомление времени выполнения.
	case CoreError        = \E_CORE_ERROR;        // [16] Фатальная ошибка времени запуска РНР.
	case CoreWarning      = \E_CORE_WARNING;      // [32] Предупреждение времени запуска РНР.
	case CompileError     = \E_COMPILE_ERROR;     // [64] Фатальная ошибка на этапе компиляции.
	case CompileWarning   = \E_COMPILE_WARNING;   // [128] Предупреждение на этапе компиляции.
	case Strict           = \E_STRICT;            // [2048] Уведомление с предложением лучшего
												  //        взаимодействия и совместимости кода.
	case RecoverableError = \E_RECOVERABLE_ERROR; // [4096] Фатальные ошибки с возможностью обработки.
	case Deprecated       = \E_DEPRECATED;        // [8192] Уведомления времени выполнения об
												  //        использовании устаревших конструкций.

	// Коды ошибок генерируемых с помощью функции PHP trigger_error()
	case UserError        = \E_USER_ERROR;        // [256] Фатальная ошибка, сгенерированная пользователем.
	case UserWarning      = \E_USER_WARNING;      // [512] Предупреждение, сгенерированное пользователем.
	case UserNotice       = \E_USER_NOTICE;       // [1024] Уведомление, сгенерированное пользователем.
	case UserDeprecated   = \E_USER_DEPRECATED;   // [16384] Уведомление об использовании устаревшей
												  //         конструкции, сгенерированное пользователем.

	// Коды пользовательских ошибок библиотеки Ultra.
	case Fatal            = 3;     // Фатальная пользовательская ошибка по умолчанию.
	case Unknown          = 5;     // Неизвестная ошибка.
	case User             = 6;     // Пользовательская нефатальная ошибка по умолчанию.
	case Noclass          = 7;     // Описание класса, интерфейса, трейта, перечисления отсутствует.
	case Noobject         = 9;     // Ошибка при инстанцировании объекта.
	case Mode             = 10;    // Попытка выполнить код в неверном режиме.
	case Ext              = 11;    // Не загружено необходимое расширение.
	case Domain           = 12;    // Неверная область использования значения.
	case Argument         = 13;    // Неверный аргумент, неожиденное или некорректное значение аргумента.
	case Exception        = 14;    // Перехваченное исключение (для exception_handler).
	case Logic            = 15;    // Ошибка в логике.
	case Range            = 17;    // Запрос несуществующего индекса.
	case Net              = 18;    // Ошибка сети, IP адрес или сетевой сокет не доступен.
	case Tcp              = 19;    // Ошибка TCP-подключения.
	case Dns              = 20;    // Ошибка DNS, DNS запись отсутствует.
	// The range from 21 to 31 is free.


	public function isFatal(): bool {
		return match ($this) {
			self::Error,
			self::Parse,
			self::CoreError,
			self::CompileError,
			self::Fatal,
			self::Unknown,
			self::Noclass => true,
			default => false,
		};
	}
}
