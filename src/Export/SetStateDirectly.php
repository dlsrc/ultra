<?php declare(strict_types=1);
/**
 * (c) 2005-2025 Dmitry Lebedev <dl@adios.ru>
 * This source code is part of the Ultra library.
 * Please see the LICENSE file for copyright and licensing information.
 */
namespace Ultra;

use Attribute;
use ReflectionClass;

/**
 * Атрибут необходимый экспортируемому объекту для прямого вызова оператором new, минуя метод
 * __set_state().
 * Объект Ultra\Exporter при сохранении объектов проверяет их на наличие атрибута
 * Ultra\Attribute\SetStateDirectly и ведет себя соответствующим образом
 * (см. Ultra\Exporter::_isSetStateDirectly())
 */
#[Attribute(Attribute::TARGET_CLASS)]
class SetStateDirectly {
	private string $_name;

	public function __construct(string $name = 'state') {
		$this->_name = $name;
	}

	public function isCallable(ReflectionClass $r): bool {
		if (!$r->hasMethod('__construct')) {
			return false;
		}

		$m = $r->getMethod('__construct');
        
		if (!$m->isPublic()) {
			return false;
        }
		
		$p = $m->getParameters();
		
		if (!isset($p[0])) {
			return false;
		}

		if ($this->_name != $p[0]->name) {
			return false;
		}
                
		if (!$p[0]->hasType()) {
			return false;
		}

		if ('array' != $p[0]->getType()) {
			return false;
		}

		if (count($p) > 1) {
			for ($i = 1; isset($p[$i]); $i++) {
				if (!$p[$i]->isDefaultValueAvailable()) {
					return false;
				}
			}
		}

		return true;
	}
}
