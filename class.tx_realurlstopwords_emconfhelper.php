<?php
/***************************************************************
*  Copyright notice
*
*  (c) Francois Suter <typo3@cobweb.ch>
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
*  A copy is found in the textfile GPL.txt and important notices to the license
*  from the author is found in LICENSE.txt distributed with these scripts.
*
*
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/


/**
 * This provides helpers for displaying custom fields in the extension configuration screen
 *
 * @author		Francois Suter (Cobweb) <typo3@cobweb.ch>
 * @package		TYPO3
 * @subpackage	tx_realurlstopwords
 *
 * $Id$
 */
class tx_realurlstopwords_emconfhelper {
	protected $configuration; // The extension's configuration

	/**
	 * This performs some initializations
	 *
	 * @return void
	 */
	public function  __construct() {
		$this->configuration = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['realurl_stopwords']);
		$GLOBALS['LANG']->includeLLFile('EXT:realurl_stopwords/locallang.xml');
	}

	/**
	 * This method renders a selector with all black or white word lists
	 *
	 * @param	array				$params: Field information to be rendered
	 * @param	t3lib_tsStyleConfig	$pObj: The calling parent object.
	 * @param	string				$type: The type of list to fetch (black or white)
	 * @return	string				The HTML selector
	 */
	protected function buildSelector(array $params, t3lib_tsStyleConfig $pObj, $type) {
		$fieldName = substr($params['fieldName'], 5, -1);
		$unknownVariablePleaseRenameMe = '\'' . substr(md5($fieldName), 0, 10) . '\'';

			// Select only the given type of list
		$where = "type = '" . $type . "'";
			// Select only records in default language or for all languages
			// Overlaying is done later, when using these records and we can't overlay
			// now, because we're in the BE context
		$where .= ' AND ' . $GLOBALS['TCA']['tx_stopwords_lists']['ctrl']['languageField'] . ' IN (0,-1)';
		$where .= t3lib_BEfunc::BEenableFields('tx_stopwords_lists') . t3lib_BEfunc::deleteClause('tx_stopwords_lists');
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('uid,title', 'tx_stopwords_lists', $where);

			// Assemble the selector
		$selector = '<select id="' . $fieldName . '" name="' . $params['fieldName'] . '" onChange="uFormUrl(' . $unknownVariablePleaseRenameMe . ')">';
			// Add option for empty selection
		$selected = (empty($this->configuration[$fieldName])) ? ' selected="selected" ' : '';
		$selector .= '<option value="0"' . $selected . '>' . $GLOBALS['LANG']->getLL('tx_realurlstopwords.none') . '</option>';
			// Add options for all lists found
		while (($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res))) {
			$selected = ($row['uid'] == $this->configuration[$fieldName]) ? ' selected="selected" ' : '';
			$selector .= '<option value="' . $row['uid'] . '"' . $selected . '>' . htmlspecialchars($row['title']) . '</option>';
		}

		$selector .= '</select>';

		return $selector;
	}

	/**
	 * This method returns a selector containing all white lists
	 *
	 * @param	array				$params: Field information to be rendered
	 * @param	t3lib_tsStyleConfig	$pObj: The calling parent object.
	 * @return	string				The HTML selector
	 */
	public function buildWhiteListSelector(array $params, t3lib_tsStyleConfig $pObj) {
		return $this->buildSelector($params, $pObj, 'white');
	}

	/**
	 * This method returns a selector containing all black lists
	 *
	 * @param	array				$params: Field information to be rendered
	 * @param	t3lib_tsStyleConfig	$pObj: The calling parent object.
	 * @return	string				The HTML selector
	 */
	public function buildBlackListSelector(array $params, t3lib_tsStyleConfig $pObj) {
		return $this->buildSelector($params, $pObj, 'black');
	}
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/realurl_stopwords/class.realurlstopwords.php']) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/realurl_stopwords/class.realurlstopwords.php']);
}
?>