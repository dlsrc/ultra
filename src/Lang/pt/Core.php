<?php declare(strict_types=1);
/**
 * (c) 2005-2024 Dmitry Lebedev <dl@adios.ru>
 * This source code is part of the Ultra library.
 * Please see the LICENSE file for copyright and licensing information.
 */
namespace Ultra\Lang\pt;

use Ultra\Core as Ultra;
use Ultra\Container\Getter;

final class Core extends Getter {
	protected function initialize(): void {
		$this->_property['success']    = 'Está tudo bem.';

		$this->_property['e_ext']      =
		'O programa requer a extensão "{0}" para funcionar.';

		$this->_property['e_class']    =
		'O arquivo incluído "{0}" retornou um objeto da classe "{1}". '.
		'Um objeto da classe "{2}" era esperado.';

		$this->_property['e_type']     =
		'O arquivo incluído "{0}" retornou o tipo de dados inválido "{1}". '.
		'O objeto era esperado. ';

		$this->_property['e_load']     =
		'Interface (classe, traço) "{0}" não foi encontrada durante '.
		'o carregamento.';

		$this->_property['h_registry'] =
		'Registro completo de classes e interfaces.';

		$this->_property['e_ftok']     =
		'Não foi possível converter o caminho "{0}" e o ID do projeto "{1}" '.
		'para a chave IPC do System V.';

		$this->_property['w_trace']    = 'Vestígio';
		$this->_property['w_file']     = 'Arquivo';
		$this->_property['w_line']     = 'Linha';
		$this->_property['w_context']  = 'Contexto';
		$this->_property['w_invoker']  = 'Invocador';

		$this->_property['src_header'] = Ultra::get()->srcHeader();
	}
}
