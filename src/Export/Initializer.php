<?php declare(strict_types=1);
/**
 * (c) 2005-2024 Dmitry Lebedev <dl@adios.ru>
 * This source code is part of the Ultra library.
 * Please see the LICENSE file for copyright and licensing information.
 */
namespace Ultra;

use Attribute;

/**
 * Атрибут указывающий на статический метод инициализации объекта-синглетона.
 */
#[Attribute(Attribute::TARGET_METHOD)]
class Initializer {}
