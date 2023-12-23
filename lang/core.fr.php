<?php declare(strict_types=1);
/**
 * (c) 2005-2023 Dmitry Lebedev <dl@adios.ru>
 * This source code is part of the Ultra package core library.
 * Please see the LICENSE file for copyright and licensing information.
 */
namespace ultra\fr;

final class Core extends \ultra\Getter {
	protected function initialize(): void {
		$this->_property['success']    = 'Tout va bien.';

		$this->_property['e_ext']      =
		'Le programme nécessite l\'extension "{0}" pour fonctionner.';

		$this->_property['e_class']    =
		'Le fichier inclus "{0}" a renvoyé un objet de classe "{1}". '.
		'Un objet de classe "{2}" était attendu.';

		$this->_property['e_type']     =
		'Le fichier inclus "{0}" a renvoyé le type de données non valide "{1}". '.
		'L\'objet était attendu.';

		$this->_property['e_load']     =
		'Interface (classe, trait) "{0}" n\'a pas été trouvé lors du chargement.';

		$this->_property['h_registry'] =
		'Registre complet des classes et des interfaces.';

		$this->_property['e_ftok']     =
		'Impossible de convertir le chemin "{0}" et l\'ID de projet "{1}" '.
		'en clé IPC System V. ';

		$this->_property['w_trace']    = 'Trace';
		$this->_property['w_file']     = 'Fichier';
		$this->_property['w_line']     = 'Ligne';
		$this->_property['w_context']  = 'Le contexte';
		$this->_property['w_invoker']  = 'Invocateur';

		$this->_property['src_header'] = \ultra\Core::get()->srcHeader();
	}
}
