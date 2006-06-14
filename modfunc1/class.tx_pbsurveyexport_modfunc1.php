<?php
/***************************************************************
*  Copyright notice
*  
*  (c) 2005 Patrick Broens (patrick@patrickbroens.nl)
*  All rights reserved
*
*  This script is part of the TYPO3 project. The TYPO3 project is 
*  free software; you can redistribute it and/or modify
*  it under the terms of the GNU General Public License as published by
*  the Free Software Foundation; either version 2 of the License, or
*  (at your option) any later version.
*
*  The GNU General Public License can be found at
*  http://www.gnu.org/copyleft/gpl.html.
* 
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/

$LANG->includeLLFile('EXT:pbsurveyexport/lang/locallang_modfunc1.xml');
require_once (PATH_t3lib.'class.t3lib_extobjbase.php');
require_once (PATH_t3lib.'class.t3lib_admin.php');
require_once (PATH_t3lib.'class.t3lib_basicfilefunc.php');
$BE_USER->modAccess($MCONF,1);

/**
 * Backend Module Function 'Export' for the 'pbsurvey' extension.
 *
 * @author Patrick Broens <patrick@patrickbroens.nl>
 * @package TYPO3
 * @subpackage pbsurveyexport
 */
class tx_pbsurveyexport_modfunc1 extends t3lib_extobjbase {
    var $arrModParameters = array(); // Module parameters, coming from TCEforms linking to the module.
    var $arrPageInfo = array(); // Page access
	var $arrResults = array(); // Array containing integers about results, called from main module.
	var $intTotalRows; // Total rows of results in database, finished and unfinished surveys.
	//var $arrError = array(); // Contains errormessages if validation of form failed.
	var $fileHandle; // Handle to identify filestream to temporary file.
	var $strSeparator; // Separator string.
	
    /**********************************
	 *
	 * Configuration functions
	 *
	 **********************************/
	 	
	/**
	 * Initialization of the class
	 *
	 * @param	object		Parent object
	 * @param	array		COnfiguration array
	 * @return	void
	 */
	function init(&$pObj,$conf)	{
		global $BACK_PATH;
		parent::init($pObj,$conf);
		$this->arrModParameters = t3lib_div::_GP($this->pObj->strExtKey);
		$this->arrPageInfo = t3lib_BEfunc::readPageAccess($this->pObj->id,$this->perms_clause);
		$this->arrResults = $this->pObj->countResults();
		$this->intTotalRows = $this->arrResults['finished'] + ($this->arrModParameters['unfinished']?$this->arrResults['unfinished']:0);
	}

    /**********************************
	 *
	 * General functions
	 *
	 **********************************/
	 
	/**
	 * Main function of the module. 
	 * Define if form has to be shown or file has to be created
	 *
	 * @return   string		HTML content for the function
	 */
	function main()	{
		global $BE_USER;
		$this->checkForm();
		if ($this->checkForm()) {
			$this->buildCsv();
		}
		if (($this->pObj->id && is_array($this->arrPageInfo)) || ($BE_USER->user['admin'] && !$this->pObj->id))	{
			$strOutput .= $this->moduleContent();
		}
		return $strOutput;
	}
	
	/**
	 * Generates the module content
	 *
	 * @return   string      HTML Content for the module
	 */
	function moduleContent() {
		$strOutput .= $this->sectionError();
		$strOutput .= $this->sectionConfiguration();
		$strOutput .= $this->sectionSeparator();
		$strOutput .= $this->sectionUserFields();
		$strOutput .= $this->sectionScoring();
		$strOutput .= $this->sectionSave();			
		return $strOutput;
	}
	
	/**
	 * Export a comma separated file
	 *
	 * @return	void
	 */
	function buildCsv() {
		global $LANG;
		$strFilepath = PATH_site . 'typo3temp/' . $this->arrModParameters['filename'];
		t3lib_div::unlink_tempfile($strFilepath);
		if ($this->fileHandle = fopen($strFilepath,'ab')) {
			$this->getSeparator();
			$arrError['column'] = $this->writeCsvColumnNames();
			$arrError['result'] = $this->writeCsvResult();
			$arrError['close'] = fclose($this->fileHandle);
			t3lib_div::fixPermissions($strFilepath);
			if (!in_array(FALSE,$arrError)) {
				header('Content-Disposition: attachment; filename='.$this->arrModParameters['filename'].'');
				header('Content-type: x-application/octet-stream');
				header('Content-Transfer-Encoding: binary');
				header('Content-length:'.filesize($strFilepath).'');
				$arrError['read'] = readfile($strFilepath);
				if ($arrError['read']) {
					exit;
				}
			}
		} else {
			$arrOutput[] = $LANG->getLL('error_fileopen');
		}
		foreach ($arrError as $strKey => $strValue) {
			if (!$strValue) {
				$this->arrError['error_file'.$strKey] = $LANG->getLL('error_file'.$strKey);
			}
		}
	}
	
	/**
	 * Builds the separator string according to the form input
	 *
	 * @return   void      
	 */
	function getSeparator() {
		$this->strSeparator = $this->arrModParameters['separator']['tab']?chr(9):'';
		$this->strSeparator .= $this->arrModParameters['separator']['comma']?',':'';
		$this->strSeparator .= $this->arrModParameters['separator']['semicolon']?';':'';
		$this->strSeparator .= $this->arrModParameters['separator']['space']?' ':'';
		$this->strSeparator .= $this->arrModParameters['separator']['other']?$this->arrModParameters['separator']['other_value']:'';
	}
	
	/**
	 * Write the column names as first row to the csv file
	 *
	 * @return   mixed      Number of bytes written, or FALSE on error
	 */
	function writeCsvColumnNames() {
		global $LANG,$TCA;
		$arrColNames['uid'] = 'uid';
		if ($this->arrModParameters['unfinished']) {
			$arrColNames['finished'] = $LANG->getLL('finished');
		}
		$arrColNames['ip'] = $LANG->getLL('ip-address');
		$arrColNames['begintstamp'] = $LANG->getLL('begintstamp');
		$arrColNames['endtstamp'] = $LANG->getLL('endtstamp');
		if (isset($this->arrModParameters['fe_users'])) {
			$arrUserKeys = array_keys($this->arrModParameters['fe_users']);
			foreach ($arrUserKeys as $strColumn) {
				$arrColNames[$strColumn] = ereg_replace(":$","",trim($GLOBALS['LANG']->sL($TCA['fe_users']['columns'][$strColumn]['label'])));
			}
		}
		foreach ($this->pObj->arrSurveyItems as $intQuestionKey=>$arrItem) {

			$strQuestion = $arrItem['question_alias']?$arrItem['question_alias']:$arrItem['question'];
			if (!in_array($arrItem['question_type'],array(10,12,13,14))) {
				if (in_array($arrItem['question_type'],array(1,2,3,4,5,23))) {
					if ($this->arrModParameters['scoring']!=0 && ($arrItem['question_type']!=1 && $arrItem['question_type']!=23)) {
						$arrColNames[$intQuestionKey] = $strQuestion;
					} elseif (!in_array($arrItem['question_type'],array(4,5))) {
						foreach($arrItem['answers'] as $intAnswerKey=>$strAnswer) {
							$arrColNames[$intQuestionKey.'_'.$intAnswerKey] = $strQuestion.'('.$strAnswer['answer'].')';
						}
					} else {
						$strNo = $arrItem['question_type']==4?$LANG->getLL('value_false'):$LANG->getLL('value_no');
						$strYes = $arrItem['question_type']==4?$LANG->getLL('value_true'):$LANG->getLL('value_yes');
						$arrColNames[$intQuestionKey.'_0'] = $strQuestion.'('.$strNo.')';
						$arrColNames[$intQuestionKey.'_1'] = $strQuestion.'('.$strYes.')';
					}
					if (isset($arrItem['answers_allow_additional'])) {
						$arrColNames[$intQuestionKey.'_-1'] = $strQuestion.'('.$LANG->getLL('additional').')';
					}
				} else { // 6,7,8,9,11,15,16
					foreach ($arrItem['rows'] as $intRowKey=>$row) {
						if (in_array($arrItem['question_type'],array(6,7)) || ($this->arrModParameters['scoring']==0 && $arrItem['question_type']==8)) {
							foreach ($arrItem['answers'] as $intAnswerKey=>$answer) {
								$arrColNames[$intQuestionKey.'_'.$intRowKey.'_'.$intAnswerKey] = $strQuestion.'('.$row.')('.$answer['answer'].')';
							}
						} else {
							$arrColNames[$intQuestionKey.'_'.$intRowKey] = $strQuestion.'('.$row.')';
						}
					}
				}
			} else {
				$arrColNames[$intQuestionKey] = $strQuestion;
			}
		}
		foreach($arrColNames as $intKey=>$strValue) {
			$this->arrCsvCols[$intKey] = '';
		}
		$mixOutput = $this->writeCsvLine($arrColNames);
		return $mixOutput;
	}
	
	/**
	 * Write each result as a new line to the csv file
	 *
	 * @return   mixed      Number of bytes written, or FALSE on error
	 */
	function writeCsvResult() {
		global $LANG;
		$intCounter = 0;
		$arrTemp = array();
		$arrResultsConf['selectFields'] = 'uid,user,ip,begintstamp,endtstamp,finished';
    	$arrResultsConf['where'] = '1=1';
    	$arrResultsConf['where'] .= ' AND pid=' . intval($this->pObj->id);
		$arrResultsConf['where'] .= isset($this->arrModParameters['unfinished'])?'':' AND finished=1';
		$arrResultsConf['where'] .= t3lib_BEfunc::BEenableFields($this->pObj->strResultsTable);
		$arrResultsConf['where'] .= t3lib_BEfunc::deleteClause($this->pObj->strResultsTable);
		$arrResultsConf['orderBy'] = 'uid ASC';
		$dbRes = $GLOBALS['TYPO3_DB']->exec_SELECTquery($arrResultsConf['selectFields'],$this->pObj->strResultsTable,$arrResultsConf['where'],'',$arrResultsConf['orderBy'],'');
		if ($this->arrModParameters['rows']=='selected') {
			$boolSeek = $GLOBALS['TYPO3_DB']->sql_data_seek($dbRes,($this->arrModParameters['configuration']['from'] - 1));
		}
		while (($arrResultRow = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($dbRes)) && $intCounter<=$this->intTotalRows){
			$this->arrCsvRow = $this->arrCsvCols;
			$this->arrCsvRow['uid'] = $arrResultRow['uid'];
			if ($this->arrModParameters['unfinished']) {
				$this->arrCsvRow['finished'] = $arrResultRow['finished'];
			}
			$this->arrCsvRow['ip'] = $arrResultRow['ip'];
			$this->arrCsvRow['begintstamp'] =  t3lib_BEfunc::datetime($arrResultRow['begintstamp']);
			$this->arrCsvRow['endtstamp'] = t3lib_BEfunc::datetime($arrResultRow['endtstamp']);
			$this->readUser($arrResultRow['user']);
			$this->readAnswers($arrResultRow['uid']);
            $intCounter++;
			$mixOutput = $this->writeCsvLine($this->arrCsvRow);
			if (!$mixOutput) {
				break;
			}
			unset($this->arrCsvRow);
		}
		return $mixOutput;
	}
	
	/**
	 * Write a single csv line to the file
	 *
	 * @param	 integer		Uid of the user
	 * @return   mixed			Number of bytes written, or FALSE on error
	 */
	function writeCsvLine($arrInput) {
	    $strWrite = t3lib_div::csvValues($arrInput,$this->strSeparator,$this->arrModParameters['separator']['delimiter']).chr(10);
		$mixOutput = fwrite($this->fileHandle, $strWrite);
		return $mixOutput;
	}

    /**********************************
	 *
	 * Checking functions
	 *
	 **********************************/

	/**
	 * Do a validation on the form
	 *
	 * @return	boolean		True if fields are correct
	 */
	function checkForm() {
		global $LANG;
		$this->objFileManagement = t3lib_div::makeInstance('t3lib_basicFileFunctions');
		$boolOutput = TRUE;
		if (isset($this->arrModParameters['submit'])) {
			if ($this->arrModParameters['rows']=='selected') {
				if ($this->arrModParameters['configuration']['from']=='' || $this->arrModParameters['configuration']['till']=='') {
					$this->arrError['configuration'] = $LANG->getLL('error_configuration_empty');
				} elseif ($this->arrModParameters['configuration']['from'] > $this->arrModParameters['configuration']['till']) {
					$this->arrError['configuration'] = $LANG->getLL('error_configuration_values');
				} elseif (!is_numeric($this->arrModParameters['configuration']['from']) || !is_numeric($this->arrModParameters['configuration']['till'])) {
					$this->arrError['configuration'] = $LANG->getLL('error_configuration_numeric');
				} elseif ($this->arrModParameters['configuration']['from']<=0 || $this->arrModParameters['configuration']['till']>$this->intTotalRows) {
					$this->arrError['configuration'] = $LANG->getLL('error_configuration_range');
				}
			}
			if (isset($this->arrModParameters['separator']['other']) && !$this->arrModParameters['separator']['other_value']) {
				$this->arrError['separator'] = $LANG->getLL('error_separator');
			}
			if (!$this->arrModParameters['filename']) {
				$this->arrError['filename'] = $LANG->getLL('error_filename');
			} else {
				$this->arrModParameters['filename'] = $this->objFileManagement->cleanFileName($this->arrModParameters['filename']);
			}
		} else {
			$boolOutput = FALSE;
		}
		if (isset($this->arrError)) {
			$boolOutput = FALSE;
		}
		return $boolOutput;
	}
		 
	/**********************************
	 *
	 * Rendering functions
	 *
	 **********************************/
	 	 	
	/**
	 * Section which shows the main error message if any after submitting the form
	 *
	 * @return	string	HTML containing the section
	 */
	function sectionError() {
		global $LANG;
		if (isset($this->arrError)) {
			$strTemp = '<p><span class="typo3-red">'.$LANG->getLL('error_text').'</span></p>';
			$strTemp .= '<ul class="typo3-red"><li>'.implode('</li>'.chr(13).'<li>',$this->arrError).'</li></ul>';
			$strOutput = $this->pObj->objDoc->section($LANG->getLL('error'),$strTemp,0,1);
		return $strOutput;
		}
	}
	
	/**
	 * Build section to define which rows are exported
	 *
	 * @return	string	HTML containing the section
	 */
	function sectionConfiguration() {
		global $LANG;
		$arrOptions[] = '<table>';
		$arrOptions[] = '<tr>';
		$strChecked = (!isset($this->arrModParameters['rows']) || $this->arrModParameters['rows']=='all')?'checked="checked"':'';
		$arrOptions[] = '<td><input name="'.$this->pObj->strExtKey.'[rows]" type="radio" value="all"' . $strChecked . ' /></td>';
		$arrOptions[] = '<td colspan="4">'.$LANG->getLL('export_all').' ('.$this->arrResults['finished'].')</td>';
		$arrOptions[] = '</tr>';
		$arrOptions[] = '<tr>';
		$strChecked = ($this->arrModParameters['rows']=='selected')?'checked="checked"':'';
		$arrOptions[] = '<td><input name="'.$this->pObj->strExtKey.'[rows]" type="radio" value="selected"' . $strChecked . ' /></td>';
		$arrOptions[] = '<td colspan="4">'.$LANG->getLL('export_selected').'</td>';
		$arrOptions[] = '</tr>';
		$arrOptions[] = '<tr>';
		$arrOptions[] = '<td>&nbsp;</td>';
		$arrOptions[] = '<td>'.$LANG->getLL('export_from').'</td>';
		$arrOptions[] = '<td><input type="text" name="'.$this->pObj->strExtKey.'[configuration][from]" value="' . $this->arrModParameters['configuration']['from'] . '" /></td>';
		$arrOptions[] = '<td>'.$LANG->getLL('export_till').'</td>';
		$arrOptions[] = '<td><input type="text" name="'.$this->pObj->strExtKey.'[configuration][till]" value="' . $this->arrModParameters['configuration']['till'] . '" /></td>';
		$arrOptions[] = '</tr>';
		$arrOptions[] = '<tr>';
		$strChecked = (isset($this->arrModParameters['unfinished']))?'checked="checked"':'';
		$arrOptions[] = '<td><input name="'.$this->pObj->strExtKey.'[unfinished]" type="checkbox" value="1"' . $strChecked . ' /></td>';
		$arrOptions[] = '<td colspan="4">'.$LANG->getLL('export_unfinished').' ('.$this->arrResults['unfinished'].')</td>';
		$arrOptions[] = '</tr>';
		$arrOptions[] = '</table>';
		$strOutput = $this->pObj->objDoc->section($LANG->getLL('export_configuration'),t3lib_BEfunc::cshItem('_MOD_'.$GLOBALS['MCONF']['name'],'export_configuration',$GLOBALS['BACK_PATH'],'|<br/>').implode(chr(13),$arrOptions),0,1);
		$strOutput .= $this->pObj->objDoc->divider(10);
		return $strOutput;
	}
	
	/**
	 * Build section to configure the text separators and delimiter
	 *
	 * @return	string	HTML containing the section
	 */
	function sectionSeparator() {
		global $LANG;
		$arrSeparator[] = '<table>';
		$arrSeparator[] = '<tr>';
		$strChecked = (isset($this->arrModParameters['separator']['tab']))?'checked="checked"':'';
		$arrSeparator[] = '<td><input type="checkbox" name="'.$this->pObj->strExtKey.'[separator][tab]" value="1"' . $strChecked . ' /></td><td width="25%">'.$LANG->getLL('separator_tab').'</td>';
		if (!isset($this->arrModParameters['submit'])) {
			$strChecked = ' checked="checked"';
		} else {
			$strChecked = (isset($this->arrModParameters['separator']['comma']))?' checked="checked"':'';
		}
		$arrSeparator[] = '<td><input type="checkbox" name="'.$this->pObj->strExtKey.'[separator][comma]" value="1"' . $strChecked . ' /></td><td width="25%">'.$LANG->getLL('separator_comma').'</td>';
		$strChecked = (isset($this->arrModParameters['separator']['other']))?' checked="checked"':'';
		$arrSeparator[] = '<td><input type="checkbox" name="'.$this->pObj->strExtKey.'[separator][other]" value="1"' . $strChecked . ' /></td><td width="25%">'.$LANG->getLL('separator_other').'</td>';
		$strValue = $this->arrModParameters['separator']['other_value']!=''?$this->arrModParameters['separator']['other_value']:'';
		$arrSeparator[] = '<td width="25%"><input type="text" name="'.$this->pObj->strExtKey.'[separator][other_value]" value="' . $strValue . '" /></td>';
		$arrSeparator[] = '</tr>';
		$arrSeparator[] = '<tr>';
		$strChecked = (isset($this->arrModParameters['separator']['semicolon']))?' checked="checked"':'';
		$arrSeparator[] = '<td><input type="checkbox" name="'.$this->pObj->strExtKey.'[separator][semicolon]" value="1"' . $strChecked . ' /></td><td>'.$LANG->getLL('separator_semicolon').'</td>';
		$strChecked = (isset($this->arrModParameters['separator']['space']))?' checked="checked"':'';
		$arrSeparator[] = '<td><input type="checkbox" name="'.$this->pObj->strExtKey.'[separator][space]" value="1"' . $strChecked . ' /></td><td>'.$LANG->getLL('separator_space').'</td>';
		$arrSeparator[] = '<td></td><td>'.$LANG->getLL('separator_delimiter').'</td>';
		if (!isset($this->arrModParameters['submit'])) {
			$strValue = '&quot;';
		} else {
			$strValue = $this->arrModParameters['separator']['delimiter']!=''?htmlspecialchars($this->arrModParameters['separator']['delimiter']):'';
		}
		$arrSeparator[] = '<td><input type="text" name="'.$this->pObj->strExtKey.'[separator][delimiter]" value="' . $strValue . '" maxlength="1" /></td>';
		$arrSeparator[] = '</tr>';
		$arrSeparator[] = '</table>';
		$strOutput = $this->pObj->objDoc->section($LANG->getLL('separator_options'),t3lib_BEfunc::cshItem('_MOD_'.$GLOBALS['MCONF']['name'],'export_separator',$GLOBALS['BACK_PATH'],'|<br/>').implode(chr(13),$arrSeparator),0,0);
		$strOutput .= $this->pObj->objDoc->divider(10);
		return $strOutput;
	}
	
	/**
	 * Build section to select which fields from fe_user table will be included in the export file
	 *
	 * @return	string	HTML containing the section
	 */
	function sectionUserFields() {
		global $LANG,$TCA;
		$arrUserFields[] = '<p>'.$LANG->getLL('fe_users_explain').'</p>';
		$arrUserFields[] = '<table>';
		$intColCount = 0;
		if (is_array($TCA['fe_users']))	{
			foreach ($TCA['fe_users']['columns'] as $strColName=>$arrCol) {
				if ($arrCol['label'] && !in_array($strColName,array('usergroup','lockToDomain','disable','starttime','endtime','TSconfig'))) {
					$arrUserFields[] = (!$intColCount?'<tr>':'').'<td><input type="checkbox" name="'.$this->pObj->strExtKey.'[fe_users]['.$strColName.']" value="1" /></td><td width="50%">'.ereg_replace(":$","",trim($GLOBALS['LANG']->sL($arrCol['label']))).'</td>'.($intColCount?'</tr>':'');
					$intColCount = $intColCount?0:1;
				}
			}
			if ($intColCount) {
				$arrUserFields[] = '<td colspan="2">&nbsp;</td></tr>';
			}
		}
		$arrUserFields[] = '</table>';
		$strOutput = $this->pObj->objDoc->section($LANG->getLL('fe_users'),t3lib_BEfunc::cshItem('_MOD_'.$GLOBALS['MCONF']['name'],'export_user_information',$GLOBALS['BACK_PATH'],'|<br/>').implode(chr(13),$arrUserFields),0,0);
		$strOutput .= $this->pObj->objDoc->divider(10);
		return $strOutput;
	}
	
	/**
	 * Build section where user can select the type of export for option groups
	 *
	 * @return	string	HTML containing the section
	 */
	function sectionScoring() {
		global $LANG;
		$arrScoring[] = '<p>'.$LANG->getLL('scoring_explain').'</p>';
		$arrScoring[] = '<table>';
		$arrScoring[] ='<tr><td><input type="radio" name="'.$this->pObj->strExtKey.'[scoring]" value="1" checked="checked" /></td><td>'.$LANG->getLL('scoring_combine').'</td></tr>';
		$arrScoring[] ='<tr><td><input type="radio" name="'.$this->pObj->strExtKey.'[scoring]" value="0" /></td><td>'.$LANG->getLL('scoring_notCombine').'</td></tr>';
		$arrScoring[] = '</table>';
		$strOutput = $this->pObj->objDoc->section($LANG->getLL('scoring'),t3lib_BEfunc::cshItem('_MOD_'.$GLOBALS['MCONF']['name'],'export_scoring',$GLOBALS['BACK_PATH'],'|<br/>').implode(chr(13),$arrScoring),0,0);
		$strOutput .= $this->pObj->objDoc->divider(10);
		return $strOutput;
	}
	
	/**
	 * Build section where user can provide a filename for the export file
	 *
	 * @return	string	HTML containing the section
	 */
	function sectionSave() {
		global $LANG;
		$strTitle = str_replace(' ', '', $this->arrPageInfo['title']);
		$strFileName = $this->arrModParameters['filename']?$this->arrModParameters['filename']:'res_'.$strTitle.'_'.date('dmy-Hi').'.csv';
		$arrFileName[] = '<p>'.$LANG->getLL('save_filename').'&nbsp;<input type="text" name="'.$this->pObj->strExtKey.'[filename]" value="'.$strFileName.'" />&nbsp;<input type="submit" name="'.$this->pObj->strExtKey.'[submit]" value="'.$LANG->getLL('save_submit').'" /></p>';
		$strOutput = $this->pObj->objDoc->section($LANG->getLL('save'),t3lib_BEfunc::cshItem('_MOD_'.$GLOBALS['MCONF']['name'],'export_save',$GLOBALS['BACK_PATH'],'|<br/>').implode(chr(13),$arrFileName),0,0);
		return $strOutput;
	}

    /**********************************
	 *
	 * Reading functions
	 *
	 **********************************/
	
	/**
	 * Read the user information from the database
	 *
	 * @param	 integer		uid of the user
	 * @return   void
	 */
	function readUser($intInput) {
		if (isset($this->arrModParameters['fe_users'])) {
			$arrUserKeys = array_keys($this->arrModParameters['fe_users']);
			if ($intInput) {
				$arrUserConf['selectFields'] = implode(',',$arrUserKeys);
		    	$arrUserConf['where'] = '1=1';
		    	$arrUserConf['where'] .= ' AND uid=' . $intInput;
				$arrUserConf['orderBy'] = '';
				$dbRes = $GLOBALS['TYPO3_DB']->exec_SELECTquery($arrUserConf['selectFields'],$this->pObj->strUserTable,$arrUserConf['where'],'',$arrUserConf['orderBy'],'');
				$arrRow = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($dbRes);
				foreach($arrRow as $strKey=>$strValue) {
					$this->arrCsvRow[$strKey] = $strValue;
				}
			} else {
				foreach ($arrUserKeys as $strKey) {
					$this->arrCsvRow[$strKey] = '';
				}
			}
		}
	}
	
	/**
	 * Read the answers belonging to the result from the database
	 *
	 * @param	 integer		uid of the result
	 * @return   void
	 */
	function readAnswers($intInput) {
		$arrAnswersConf['selectFields'] = 'uid,question,col,row,answer';
    	$arrAnswersConf['where'] = '1=1';
    	$arrAnswersConf['where'] .= ' AND result=' . $intInput;
		$arrAnswersConf['where'] .= t3lib_BEfunc::BEenableFields($this->pObj->strAnswersTable);
		$arrAnswersConf['where'] .= t3lib_BEfunc::deleteClause($this->pObj->strAnswersTable);
		$dbRes = $GLOBALS['TYPO3_DB']->exec_SELECTquery($arrAnswersConf['selectFields'],$this->pObj->strAnswersTable,$arrAnswersConf['where'],'',$arrAnswersConf['orderBy'],'');
		while ($arrAnswersRow = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($dbRes)){
			$arrItem = $this->pObj->arrSurveyItems[$arrAnswersRow['question']];
			$strKey = $arrAnswersRow['question'];
			if (in_array($arrItem['question_type'],array(1,23))) {
				$strKey .= '_'.$arrAnswersRow['row'];
			} 
			if (in_array($arrItem['question_type'],array(2,3,4,5))) {
				if ($arrItem['question_type']==3 && !is_numeric($arrAnswersRow['answer'])) {
					$strKey .= '_-1';
				} elseif ($this->arrModParameters['scoring']!=1) {
					if (in_array($arrItem['question_type'],array(4,5))) {
						$strKey .= '_'.($arrAnswersRow['answer']-1);
					} else {
						$strKey .= '_'.$arrAnswersRow['answer'];
					}
				}
			}
			if (in_array($arrItem['question_type'],array(6,7,8,9,11,15,16))) {
				$strKey .= '_'.($arrAnswersRow['row']-1);
				if (in_array($arrItem['question_type'],array(6,7))) {
					$strKey .= '_'.$arrAnswersRow['col'];
				}
			}			
			if (in_array($arrItem['question_type'],array(7,9,10,11,12,13,14,15,16)) || ($arrItem['question_type']==1 && $arrAnswersRow['row']<0) || ($arrItem['question_type']==3 && !is_numeric($arrAnswersRow['answer']))) {
				$strAnswer = $arrAnswersRow['answer'];
			} elseif (in_array($arrItem['question_type'],array(1,2,3,6,8,23))) {
				if ($arrItem['answers'][$arrAnswersRow['answer']]['points']!='') {
					$strAnswer = $arrItem['answers'][$arrAnswersRow['answer']]['points'];
				} else { // Er zijn geen punten
					$strAnswer = $arrItem['answers'][$arrAnswersRow['answer']]['answer'];
				}
			} elseif (in_array($arrItem['question_type'],array(4,5))) {
				if ($this->arrModParameters['scoring']==1) {
					$strAnswer = $arrAnswersRow['answer']-1;
				} else {
					$strAnswer = 1;
				}
			}
			$this->arrCsvRow[$strKey] = str_replace(array(chr(10),chr(13)), " ", $strAnswer);
		}
	}
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/pbsurveyexport/modfunc1/class.tx_pbsurveyexport_modfunc1.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/pbsurveyexport/modfunc1/class.tx_pbsurveyexport_modfunc1.php']);
}
?>