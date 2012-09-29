<?php
if (!defined ('TYPO3_MODE')) {
	die ('Access denied.');
}

if (TYPO3_MODE == 'BE') {
	// Adds the "Function menu module" ('third level module') "Export results" to the existing function menu for the pbsurvey backend module
	t3lib_extMgm::insertModuleFunction(
		'web_txpbsurveyM1',
		'tx_pbsurveyexport_modfunc1',
		t3lib_extMgm::extPath($_EXTKEY) . 'modfunc1/class.tx_pbsurveyexport_modfunc1.php',
		'LLL:EXT:pbsurveyexport/lang/locallang_modfunc1.xml:moduleFunction'
	);
	// Adds the "Function menu module" ('third level module') "Delete results" to the existing function menu for the pbsurvey backend module
	t3lib_extMgm::insertModuleFunction(
		'web_txpbsurveyM1',
		'tx_pbsurveyexport_modfunc2',
		t3lib_extMgm::extPath($_EXTKEY) . 'modfunc2/class.tx_pbsurveyexport_modfunc2.php',
		'LLL:EXT:pbsurveyexport/lang/locallang_modfunc2.xml:moduleFunction'
	);
}

// Adds a reference for module "Export results" to a locallang file with $GLOBALS['TCA_DESCR'] labels
t3lib_extMgm::addLLrefForTCAdescr('_MOD_web_txpbsurveyM1', 'EXT:pbsurveyexport/csh/locallang_modfunc1.xml');
// Adds a reference for module "Delete results" to a locallang file with $GLOBALS['TCA_DESCR'] labels
t3lib_extMgm::addLLrefForTCAdescr('_MOD_web_txpbsurveyM2', 'EXT:pbsurveyexport/csh/locallang_modfunc2.xml');