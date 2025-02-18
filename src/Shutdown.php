<?php declare(strict_types=1);
/**
 * (c) 2005-2025 Dmitry Lebedev <dl@adios.ru>
 * This source code is part of the Ultra library.
 * Please see the LICENSE file for copyright and licensing information.
 */
namespace Ultra;

use Closure;

final class Shutdown {
	/**
	 * Завершающая функция, вызываемая в случае фатальной ошибки.
	 * В качестве аргумента функция может принимать сам объект фатальной ошибки.
	 */
	public Closure|null $error = null {
		set(Closure|null $value) {
			$this->error = $value;
			$this->_shutdownStart();
		}
	}

	/**
	 * Завершающая функция, вызываемая в случае остановки по таймауту.
	 */
	public Closure|null $timeout = null {
		set(Closure|null $value) {
			$this->timeout = $value;
			$this->_shutdownStart();
		}
	}

	/**
	 * Завершающая функция, вызываемая в случае прерывания исполнения.
	 */
	public Closure|null $abort = null {
		set(Closure|null $value) {
			$this->abort = $value;
			$this->_shutdownStart();
		}
	}

	/**
	 * Флаг выполнения завершающей функции в случае прерывания пользователем исполнения
	 * приложения.
	 * Если параметр php.ini ignore_user_abort установлен в значение TRUE, то завершающая
	 * функция self::$_aborted по умолчанию исполняться не будет.
	 * Если параметр ignore_user_abort установлен в значение FALSE, либо флаг $_ignore
	 * установлен в TRUE, завершающая функция self::$_aborted будет вызвана, даже если
	 * ignore_user_abort установлен в значение TRUE.
	 */
	public bool $ignore;

	/**
	 * Фатальная ошибка, которая будет передана в функцию завершения.
	 * При возникновении фатальной ошибки после ее регистрации в журнале событий Core
	 * присваивает её свойству, после чего в Core вызывается функция exit() и выполнение
	 * приложения прекращается.
	 * Поле управляет вызовом замыкания, которое предназначено для случая фатальной ошибки
	 * и вызывается в функции завершения работы.
	 */
	public Error|null $fatal;

	/**
	 * Флаг установки завершающей функции. По умолчанию FALSE.
	 * Становится TRUE когда регистрируется хотя-бы одно из замыканий в качестве завершающей
	 * функции.
	 */
	private bool $_shutdown;

	public function __construct() {
		$this->ignore     = false;
		$this->fatal      = null;
		$this->_shutdown  = false;
	}

	private function _shutdownHandler(): void {
		if (null != $this->error && null != $this->fatal) {
			($this->error)($this->fatal);
		}

		if (null != $this->abort && 1 == connection_aborted()
		&& ($this->ignore || !ini_get('ignore_user_abort'))) {
			($this->abort)();
		}

		if (null != $this->timeout && connection_status() > 1) {
			($this->timeout)();
		}
	}

	private function _shutdownStart(): void {
		if (!$this->_shutdown) {
			$this->_shutdown = true;
			register_shutdown_function($this->_shutdownHandler(...));
		}
	}
}
