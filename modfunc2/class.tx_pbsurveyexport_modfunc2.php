<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2012 Patrick Broens (patrick@patrickbroens.nl)
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

$GLOBALS['LANG']->includeLLFile('EXT:pbsurveyexport/lang/locallang_modfunc2.xml');
$GLOBALS['BE_USER']->modAccess($GLOBALS['MCONF'], 1);

use \TYPO3\CMS\Core\Utility\GeneralUtility;
use \TYPO3\CMS\Backend\Utility\BackendUtility;

/**
 * Backend Module Function 'Delete results' for the 'pbsurvey' extension.
 *
 * @author Patrick Broens <patrick@patrickbroens.nl>
 * @package TYPO3
 * @subpackage pbsurveyexport
 */
class tx_pbsurveyexport_modfunc2 extends \TYPO3\CMS\Backend\Module\AbstractFunctionModule
{
    /**
     * Initialization of the class
     *
     * @param object Parent object
     * @param array Configuration array
     * @return void
     */
    public function init(&$pObj, $conf)
    {
        parent::init($pObj, $conf);
        $this->getPostParameters = GeneralUtility::_GP($this->pObj->strExtKey);
        $this->pageInfo = BackendUtility::readPageAccess($this->pObj->id, $this->perms_clause);
    }

    /**
     * Main function of the module.
     *
     * Go to action based on the step
     *
     * @return string HTML content
     */
    public function main()
    {
        global $BE_USER;

        if (($this->pObj->id && is_array($this->pageInfo)) || ($BE_USER->user['admin'] && !$this->pObj->id)) {
            switch ($this->getPostParameters['step']) {
                case '1':
                    $content = $this->confirmationAction();
                    break;
                case '2':
                    $content = $this->deleteAction();
                    break;
                default:
                    $content = $this->indexAction();
            }
        }
        return $content;
    }

    /**
     * Entry point of the module
     *
     * Shows introduction and warning about the use of this module
     *
     * @return string HTML content
     */
    private function indexAction()
    {
        global $LANG;

        $lines = array();

        $lines[] = '<fieldset style="border: 0px none;">';
        $lines[] = '<p style="margin: 1em 0px;">' . $LANG->getLL('indexText') . '</p>';
        $lines[] = '<input type="hidden" name="' . $this->pObj->strExtKey . '[step]" value="1" />';
        $lines[] = '<input type="submit" name="' . $this->pObj->strExtKey . '[submit]" value="'
            . $LANG->getLL('submitDelete') . '" />';
        $lines[] = '</fieldset>';

        $content = implode(chr(13), $lines);

        return $this->pObj->objDoc->section($LANG->getLL('indexSection'), $content, 0, 1);
    }

    /**
     * Confirmation to delete all results
     *
     * @return string HTML content
     */
    private function confirmationAction()
    {
        global $LANG;

        $lines = array();

        $lines[] = '<fieldset style="border: 0px none;">';
        $lines[] = '<p style="margin: 1em 0px;">' . $LANG->getLL('confirmationText') . '</p>';
        $lines[] = '<input type="hidden" name="' . $this->pObj->strExtKey . '[step]" value="2" />';
        $lines[] = '<input type="submit" name="' . $this->pObj->strExtKey . '[submit]" value="'
            . $LANG->getLL('submitConfirmation') . '" />';
        $lines[] = '</fieldset>';

        $content = implode(chr(13), $lines);

        return $this->pObj->objDoc->section($LANG->getLL('confirmationSection'), $content, 0, 1);
    }

    /**
     * Delete the results and answers from the database
     *
     * Returns confirmation about deletion or an error in case the deletion went wrong
     *
     * @return string HTML content
     */
    private function deleteAction()
    {
        global $LANG;

        $lines = array();

        if ($this->deleteAnswers() && $this->deleteResults()) {
            $lines[] = '<p style="margin: 1em 0px;">' . $LANG->getLL('deleteText') . '</p>';
        } else {
            $lines[] = '<p style="margin: 1em 0px;"><span class="typo3-red">' . $LANG->getLL('errorText')
                . '</span></p>';
        }

        $content = implode(chr(13), $lines);

        return $this->pObj->objDoc->section($LANG->getLL('deleteSection'), $content, 0, 1);
    }

    /**
     * Delete the answers from the database, based on selected page
     *
     * @return mixed Resource on success, FALSE on error
     */
    private function deleteAnswers()
    {
        return $GLOBALS['TYPO3_DB']->exec_DELETEquery(
            $this->pObj->strAnswersTable,
            'pid=' . intval($this->pObj->id)
        );
    }

    /**
     * Delete the results from the database, based on selected page
     *
     * @return mixed Resource on success, FALSE on error
     */
    private function deleteResults()
    {
        return $GLOBALS['TYPO3_DB']->exec_DELETEquery(
            $this->pObj->strResultsTable,
            'pid=' . intval($this->pObj->id)
        );
    }
}

if (defined('TYPO3_MODE')
    && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/pbsurveyexport/modfunc2/class.tx_pbsurveyexport_modfunc2.php']
) {
    include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/pbsurveyexport/modfunc2/class.tx_pbsurveyexport_modfunc2.php']);
}