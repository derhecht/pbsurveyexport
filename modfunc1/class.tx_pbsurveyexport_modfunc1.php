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
$BE_USER->modAccess($MCONF,1);
require_once(t3lib_extMgm::extPath('cc_debug').'class.tx_ccdebug.php');

class tx_pbsurveyexport_modfunc1 extends t3lib_extobjbase {
    var $arrModParameters = array(); // Module parameters, coming from TCEforms linking to the module.
    var $arrPageInfo = array(); // Page access

	/**
	 * Initialization of the class
	 *
	 * @return	void
	 */
	function init(&$pObj,$conf)	{
		global $BACK_PATH;
		parent::init($pObj,$conf);
		$this->handleExternalFunctionValue();
		$this->arrModParameters = t3lib_div::_GP($this->pObj->strExtKey);
		$this->arrPageInfo = t3lib_BEfunc::readPageAccess($this->pObj->id,$this->perms_clause);
		list($strRequestUri) = explode('#',t3lib_div::getIndpEnv('REQUEST_URI'));
		$this->pObj->form = '<form action="'.htmlspecialchars($strRequestUri).'" method="post" name="modfunc1_'.$this->pObj->strExtKey.'">';
	}

	/**
	 * Main function of the module. Write the content to $this->content
	 *
	 * @return   string		HTML content for the function
	 */
	function main()	{
		global $BE_USER;
		if (isset($this->arrModParameters['csv_type'])) {
			$this->exportCsv();
		} elseif (($this->pObj->id && is_array($this->arrPageInfo)) || ($BE_USER->user['admin'] && !$this->pObj->id))	{
			$strOutput .= $this->moduleContent();
			return $strOutput;
		}
	}
	
	/**
	 * Generates the module content
	 *
	 * @return   string      HTML Content for the module
	 */
	function moduleContent() {
		$strOutput .= $this->sectionConfiguration();
		$strOutput .= $this->sectionSeparator();
		$strOutput .= $this->sectionUserFields();
		$strOutput .= $this->sectionScoring();
		$strOutput .= $this->sectionSave();			
		return $strOutput;
	}
	
	/**
	 * Build section to define which rows are exported
	 *
	 * @return	string	HTML containing the section
	 */
	function sectionConfiguration() {
		global $LANG;
		$arrResults = $this->pObj->countResults();
		$arrOptions[] = '<table>';
		$arrOptions[] = '<tr>';
		$arrOptions[] = '<td><input name="'.$this->pObj->strExtKey.'[configuration]" type="radio" value="all" checked="checked" /></td>';
		$arrOptions[] = '<td colspan="4">'.$LANG->getLL('export_all').' ('.$arrResults['finished'].')</td>';
		$arrOptions[] = '</tr>';
		$arrOptions[] = '<tr>';
		$arrOptions[] = '<td><input name="'.$this->pObj->strExtKey.'[configuration]" type="radio" value="selected" /></td>';
		$arrOptions[] = '<td colspan="4">'.$LANG->getLL('export_selected').'</td>';
		$arrOptions[] = '</tr>';
		$arrOptions[] = '<tr>';
		$arrOptions[] = '<td>&nbsp;</td>';
		$arrOptions[] = '<td>'.$LANG->getLL('export_from').'</td>';
		$arrOptions[] = '<td><input type="text" name="'.$this->pObj->strExtKey.'[configuration][from]" /></td>';
		$arrOptions[] = '<td>'.$LANG->getLL('export_till').'</td>';
		$arrOptions[] = '<td><input type="text" name="'.$this->pObj->strExtKey.'[configuration][till]" /></td>';
		$arrOptions[] = '</tr>';
		$arrOptions[] = '<tr><td colspan="5">&nbsp;</td></tr>';
		$arrOptions[] = '<tr>';
		$arrOptions[] = '<td><input name="'.$this->pObj->strExtKey.'[configuration][unfinished]" type="checkbox" value="1" /></td>';
		$arrOptions[] = '<td colspan="4">'.$LANG->getLL('export_unfinished').' ('.$arrResults['unfinished'].')</td>';
		$arrOptions[] = '</tr>';
		$arrOptions[] = '</table>';
		$strOutput = $this->pObj->objDoc->section($LANG->getLL('export_configuration'),t3lib_BEfunc::cshItem('_MOD_'.$GLOBALS['MCONF']['name'],'pbsurveyexport_modfunc1',$GLOBALS['BACK_PATH'],'|<br/>').implode(chr(13),$arrOptions),0,1);
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
		$arrSeparator[] = '<td><input type="checkbox" name="'.$this->pObj->strExtKey.'[separator][tab]" value="checkbox" /></td><td width="25%">'.$LANG->getLL('separator_tab').'</td>';
		$arrSeparator[] = '<td><input type="checkbox" name="'.$this->pObj->strExtKey.'[separator][comma]" value="checkbox" checked="checked" /></td><td width="25%">'.$LANG->getLL('separator_comma').'</td>';
		$arrSeparator[] = '<td><input type="checkbox" name="'.$this->pObj->strExtKey.'[separator][other]" value="checkbox" /></td><td width="25%">'.$LANG->getLL('separator_other').'</td>';
		$arrSeparator[] = '<td width="25%"><input type="text" name="'.$this->pObj->strExtKey.'[separator][other_value]" /></td>';
		$arrSeparator[] = '</tr>';
		$arrSeparator[] = '<tr>';
		$arrSeparator[] = '<td><input type="checkbox" name="'.$this->pObj->strExtKey.'[separator][semicolon]" value="checkbox" /></td><td>'.$LANG->getLL('separator_semicolon').'</td>';
		$arrSeparator[] = '<td><input type="checkbox" name="'.$this->pObj->strExtKey.'[separator][space]" value="checkbox" /></td><td>'.$LANG->getLL('separator_space').'</td>';
		$arrSeparator[] = '<td></td><td>'.$LANG->getLL('separator_delimiter').'</td>';
		$arrSeparator[] = '<td><input type="text" name="'.$this->pObj->strExtKey.'[separator][delimiter]" value="&quot;" /></td>';
		$arrSeparator[] = '</tr>';
		$arrSeparator[] = '</table>';
		$strOutput = $this->pObj->objDoc->section($LANG->getLL('separator_options'),implode(chr(13),$arrSeparator),0,0);
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
		$strOutput = $this->pObj->objDoc->section($LANG->getLL('fe_users'),implode(chr(13),$arrUserFields),0,0);
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
		$strOutput = $this->pObj->objDoc->section($LANG->getLL('scoring'),implode(chr(13),$arrScoring),0,0);
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
		$strFileName = 'res_'.$strTitle.'_'.date('dmy-Hi').'.csv';
		$arrFileName[] = '<p>'.$LANG->getLL('save_filename').'&nbsp;<input type="text" name="'.$this->pObj->strExtKey.'[filename]" value="'.$strFileName.'" />&nbsp;<input type="submit" value="'.$LANG->getLL('save_submit').'" /></p>';
		$strOutput = $this->pObj->objDoc->section($LANG->getLL('save'),implode(chr(13),$arrFileName),0,0);
		return $strOutput;
	}
	
	/**
	 * Export a comma separated file
	 *
	 * @return	void
	 */
	function exportCsv() {
		$strMimeType = 'application/octet-stream';
		$strTitle = str_replace(' ', '', $this->arrPageInfo['title']);
		$strFileName = 'res_'.$strTitle.'_'.date('dmy-Hi').'.csv';
		
		Header('Content-Type: '.$strMimeType);
		Header('Content-Disposition: attachment; filename='.$strFileName);
		echo $this->makeCsv();
		exit;
	}
	
	/**
	 * Creates array containing all column headers of the csv file
	 *
	 * @return   array      First row of the csv file
	 */
	function csvHeaderRow() {
		global $LANG;
		$arrOutput = array(
			'id',
			$LANG->getLL('crdate'),
			$LANG->getLL('tstamp'),
		);
		foreach ($this->pObj->arrSurveyItems as $arrItem) {
			if ($arrItem['question_alias']) {
				$strColName = $arrItem['question_alias'];
			} else { 
				$strColName = trim($arrItem['question']);
			}
			$arrAnswers = $this->pObj->answersArray($arrItem['answers']);
			$arrRows = explode("\n",$arrItem['rows']);
			if (in_array($arrItem['question_type'],array(1,2,3,4,5,10,12,13,14))) {
				if (($this->arrModParameters['csv_type'] && $arrItem['question_type']!=1) || in_array($arrItem['question_type'],array(10,12,13,14))) {
					$arrOutput[] = $strColName;
				} elseif (in_array($arrItem['question_type'],array(1,2,3))) {
					foreach (array_keys($arrAnswers) as $strSingleAnswer){
						$arrOutput[] = $strColName . ' (' . $strSingleAnswer . ')';
					}
					if ($arrItem['answers_allow_additional'] && $arrItem['question_type']!=2) {
						$arrOutput[] = $strColName . ' (' . $LANG->getLL('additional') .')';
					}
				} elseif ($arrItem['question_type']==4) {
					$arrOutput[] = $strColName . ' (' . $LANG->getLL('value_true') . ')';
					$arrOutput[] = $strColName . ' (' . $LANG->getLL('value_false') . ')';
				} elseif ($arrItem['question_type']==5) {
					$arrOutput[] = $strColName . ' (' . $LANG->getLL('value_yes') .')';
					$arrOutput[] = $strColName . ' (' . $LANG->getLL('value_no') . ')';
				}
			} else {
				foreach ($arrRows as $strRow) {
					if (($this->arrModParameters['csv_type'] && $arrItem['question_type']==8) || in_array($arrItem['question_type'],array(9,11,15,16))) {
						$arrOutput[] = $strColName . ' (' . trim($strRow) . ')';
					} else {
						foreach (array_keys($arrAnswers) as $arrSingleCol){
							$arrOutput[] = $strColName . ' (' . trim($strRow) . ' ' . $arrSingleCol . ')';
						}
					}
				}
			}
		}
		return $arrOutput;
	}
	
	/**
	 * Get the personal information from the user if not anonymous
	 * 
	 * @param	array	Results record
	 * @return	array	Personal user information
	 */
	 function getUser($arrInput) {
	 	global $LANG;
 		if (intval($arrInput['user'])) {
			$dbRes=$GLOBALS['TYPO3_DB']->exec_SELECTquery('name,address,zip,city,email','fe_users','uid='.intval($arrInput['user']));
			$arrUser = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($dbRes);
			$arrResult['id'] = $arrInput['uid'];
			$arrOutput[$LANG->getLL('crdate')] = date('d-m-Y',$arrInput['crdate']);
			$arrOutput[$LANG->getLL('tstamp')] = date('d-m-Y',$arrInput['tstamp']);
			$arrOutput[$LANG->getLL('name')] = $arrUser['name'];
			$arrOutput[$LANG->getLL('address')] = $arrUser['address'];
			$arrOutput[$LANG->getLL('zip')] = $arrUser['zip'];
			$arrOutput[$LANG->getLL('city')] = $arrUser['city'];
			$arrOutput[$LANG->getLL('email')] = $arrUser['email'];
 		}
 		return $arrOutput;
	 }
	
	/**
	 * Get the points for an answer if present
	 * 
	 * @param	array	Results record
	 * @param	array	Answer to search for
	 * @return	array	Point matching the given answer
	 */
	function getPoints($strInput,$strAnswers) {
		$arrPossibleAnswers = $this->pObj->answersArray($strAnswers);
		if (is_array($arrPossibleAnswers)) {
			if ($arrPossibleAnswers[$strInput]) {
				$intOutput = intval($arrPossibleAnswers[$strInput]);
			}
		}
		return $intOutput;
	}
	
	/**
	 * Creates the csv file
	 *
	 * @return   string      csv file content
	 */
	function makeCsv() {
		global $LANG;	
		$dbRes=$GLOBALS['TYPO3_DB']->exec_SELECTquery('*',$this->strTableResults,'pid='.intval($this->pObj->id).' AND deleted=0 AND hidden=0');
		while ($arrDbResults =$GLOBALS['TYPO3_DB']->sql_fetch_assoc($dbRes)) {
			$arrResult = $this->getUser($arrDbResults); 
			$strResults = unserialize($arrDbResults['results']);
			foreach ($strResults as $strName => $strResult) {
				$arrItem = $this->pObj->arrSurveyItems[$strName];
				if ($arrItem['question_alias']) {
					$strResultKey = $arrItem['question_alias'];
				} else {
					$strResultKey = $strName;
				}
				if (in_array($arrItem['question_type'],array(1,6,7,8,9,11,15,16))) {
					foreach ($strResult as $mixedKey=>$strAnswer) {
						if (in_array($arrItem['question_type'],array(6,7))) {
							foreach ($strAnswer as $mixedKey2=>$strSingle) {
								if ($arrItem['question_type']==6) {
									$intPoint = $this->getPoints($strSingle,$arrItem['answers']);
									$strValueTemp = $intPoint?$intPoint:'1';
									$strKeyTemp = $strResultKey . ' (' . $mixedKey . ' ' . ($strSingle) . ')';
								} else {
									$strValueTemp = $strSingle;
									$strKeyTemp = $strResultKey . ' (' . $mixedKey . ' ' . $mixedKey2 . ')';
								}
								$arrResult[$strKeyTemp] = $strValueTemp;
								unset($intPoint);
							}
						} elseif (in_array($arrItem['question_type'],array(1,8,9,11,15,16))) {
							if (in_array($arrItem['question_type'],array(1,8))) {
								$intPoint = $this->getPoints($strAnswer,$arrItem['answers']);
							}
							if ($arrItem['question_type']==1) {
								if (is_numeric($mixedKey)) {
									$arrResult[$strResultKey . ' (' . $strAnswer .')'] = $intPoint?$intPoint:'1';
								} elseif ($mixedKey=='additional') {
									$arrResult[$strResultKey . ' (' . $LANG->getLL('additional') .')'] = $strAnswer;
								}
							} elseif ($arrItem['question_type']==8) {
								if ($this->arrModParameters['csv_type']) {
									$strKeyTemp = $strResultKey . ' (' . $mixedKey . ')';
									$strValueTemp = $intPoint?$intPoint:$strAnswer;
								} else {
									$strKeyTemp = $strResultKey . ' (' . $mixedKey . ' ' . $strAnswer . ')';
									$strValueTemp = $intPoint?$intPoint:'1';
								}
							} else {
								$strKeyTemp = $strResultKey . ' (' . $mixedKey . ')';
								$strValueTemp = $strAnswer;
							}
							$arrResult[$strKeyTemp] = $strValueTemp;
							unset($intPoint);
						}
					}
				} elseif (in_array($arrItem['question_type'],array(2,3,4,5))) { 
					if (in_array($arrItem['question_type'],array(2,3))) {
						$intPoint = $this->getPoints($strResult,$arrItem['answers']);
						if ($arrItem['question_type']==3) {
							$arrPossibleAnswers = $this->pObj->answersArray($arrItem['answers']);
						}
					}
					if (($arrItem['question_type']==3 && $arrPossibleAnswers[$strResult]) || $arrItem['question_type']!=3) {
						if ($this->arrModParameters['csv_type']) {
							$strKeyTemp = $strResultKey;
							$strValueTemp = $intPoint?$intPoint:$strResult;
						} else {
							$strKeyTemp = $strResultKey . ' (' . $strResult .')';
							$strValueTemp = $intPoint?$intPoint:'1';
						}
					} else {
						$strKeyTemp = $strResultKey . ' (' . $LANG->getLL('additional') . ')';
						$strValueTemp = $strResult;
					}
					$arrResult[$strKeyTemp] = $strValueTemp;
					unset($intPoint);
				} elseif (in_array($arrItem['question_type'],array(10,12,13,14))) {
					$strKeyTemp = $strResultKey;
					$strValueTemp = str_replace( "\n", ' ', str_replace( "\r", "\n", str_replace( "\r\n", "\n", $strResult ) ) );						
					$arrResult[$strKeyTemp] = $strValueTemp;
				}
			}
			$arrAllRows[] = $arrResult;
		}
		$arrAllCols = $this->csvHeaderRow();
		$strOutput = '"'.implode('","',$arrAllCols).'"'.chr(10);
		foreach($arrAllRows as $arrRow) {
			foreach($arrAllCols as $strCol) {
				if ($arrRow[$strCol]) {
					$strCsv .= '"' . str_replace('"',"'",$arrResult[$strCol]) . '",';
				} else {
					$strCsv .= '"",';
				}
			}
			$strOutput .= trim($strCsv, ',') . chr(10);
			unset($strCsv);
		}
		return $strOutput;
	}
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/pbsurveyexport/modfunc1/class.tx_pbsurveyexport_modfunc1.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/pbsurveyexport/modfunc1/class.tx_pbsurveyexport_modfunc1.php']);
}
?>