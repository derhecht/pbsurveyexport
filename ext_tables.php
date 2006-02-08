<?php
if (!defined ('TYPO3_MODE')) 	die ('Access denied.');

if (TYPO3_MODE=='BE')	{
	t3lib_extMgm::insertModuleFunction(
		'web_txpbsurveyM1',
		'tx_pbsurveyexport_modfunc1',
		t3lib_extMgm::extPath($_EXTKEY).'modfunc1/class.tx_pbsurveyexport_modfunc1.php',
		'LLL:EXT:pbsurveyexport/lang/locallang_modfunc1.xml:moduleFunction'
	);
}
t3lib_extMgm::addLLrefForTCAdescr('_MOD_web_txpbsurveyM1','EXT:pbsurveyexport/csh/locallang_modfunc1.xml');
?>
