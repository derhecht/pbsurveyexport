<?php

/***************************************************************
 * Extension Manager/Repository config file for ext "pbsurveyexport".
 *
 * Auto generated 21-11-2013 15:03
 *
 * Manual updates:
 * Only the data in the array - everything else is removed by next
 * writing. "version" and "dependencies" must not be touched!
 ***************************************************************/

$EM_CONF[$_EXTKEY] = array (
	'title' => 'Questionaire export',
	'description' => 'Export module for Questionaire extension.',
	'category' => 'module',
	'shy' => 0,
	'version' => '1.3.0',
	'dependencies' => 'pbsurvey',
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
	'constraints' =>
	array (
		'depends' =>
		array (
			'pbsurvey' => '1.6.0-1.6.99',
			'typo3' => '7.6.0-7.6.99'
		),
		'conflicts' =>
		array (
		),
		'suggests' =>
		array (
		)
	)
);

?>