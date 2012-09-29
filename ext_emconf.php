<?php

########################################################################
# Extension Manager/Repository config file for ext "pbsurveyexport".
#
# Auto generated 29-09-2012 12:28
#
# Manual updates:
# Only the data in the array - everything else is removed by next
# writing. "version" and "dependencies" must not be touched!
########################################################################

$EM_CONF[$_EXTKEY] = array(
	'title' => 'Questionaire export',
	'description' => 'Export module for Questionaire extension.',
	'category' => 'module',
	'shy' => 0,
	'version' => '1.0.6',
	'dependencies' => '',
	'conflicts' => '',
	'priority' => '',
	'loadOrder' => '',
	'module' => '',
	'state' => 'stable',
	'uploadfolder' => 0,
	'createDirs' => '',
	'modify_tables' => '',
	'clearcacheonload' => 0,
	'lockType' => '',
	'author' => 'Patrick Broens',
	'author_email' => 'patrick@patrickbroens.nl',
	'author_company' => '',
	'CGLcompliance' => '',
	'CGLcompliance_note' => '',
	'constraints' => array(
		'depends' => array(
			'pbsurvey' => '',
			'php' => '4.0.0-0.0.0',
			'typo3' => '3.8.1-0.0.0',
		),
		'conflicts' => array(
		),
		'suggests' => array(
		),
	),
	'_md5_values_when_last_written' => 'a:9:{s:9:"Changelog";s:4:"7f3f";s:12:"ext_icon.gif";s:4:"ee26";s:15:"ext_php_api.dat";s:4:"dfe5";s:14:"ext_tables.php";s:4:"d60c";s:13:"locallang.xml";s:4:"fe2d";s:26:"csh/locallang_modfunc1.xml";s:4:"ca89";s:29:"csh/nl.locallang_modfunc1.xml";s:4:"0343";s:27:"lang/locallang_modfunc1.xml";s:4:"cd52";s:45:"modfunc1/class.tx_pbsurveyexport_modfunc1.php";s:4:"4f01";}',
);

?>