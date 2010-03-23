<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2010 Francois Suter (Cobweb) <typo3@cobweb.ch>
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


/**
 * Filters for RealURL speaking URLs
 *
 * @author		Francois Suter (Cobweb) <typo3@cobweb.ch>
 * @package		TYPO3
 * @subpackage	tx_realurlstopwords
 *
 * $Id$
 */
class tx_realurlstopwords_filter {
	protected $configuration;

	public function  __construct() {
			// Read the extension's configuration
		$this->configuration = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['realurl_stopwords']);
	}

	/**
	 * This method post-processes a title for a speaking URL
	 * It actually starts from the raw title again and removes words from it
	 * according to different criteria: word length, black lists or white lists
	 *
	 * @param	array	$parameters: call parameters, including the raw title and the processed title
	 * @param	object	$pObj: reference to the calling RealURL object
	 * @return	string	The cleaned up title for the speaking URL
	 */
	public function filterWords($parameters, $pObj) {
			// Initialize configuration
		$encodingConfiguration = array();
		if (isset($parameters['encodingConfiguration'])) {
			$encodingConfiguration = $parameters['encodingConfiguration'];
		} elseif (isset($pObj->conf)) {
			$encodingConfiguration = $pObj->conf;
		} else {
			$encodingConfiguration['strtolower'] = TRUE;
			$encodingConfiguration['spaceCharacter'] = '_';
		}

			// NOTE: the first part of this code is copied from tx_realurl_advanced::encodeTitle
			// as we have to redo its job, since we start from the raw title again and
			// not from the processed title
			// Fetch character set:
		$charset = $GLOBALS['TYPO3_CONF_VARS']['BE']['forceCharset'] ? $GLOBALS['TYPO3_CONF_VARS']['BE']['forceCharset'] : $GLOBALS['TSFE']->defaultCharSet;

			// Convert to lowercase
		$processedTitle = $parameters['title'];
		if ($encodingConfiguration['strtolower']) {
			$processedTitle = $GLOBALS['TSFE']->csConvObj->conv_case($charset, $parameters['title'], 'toLower');
		}

			// Strip tags
		$processedTitle = strip_tags($processedTitle);

			// Convert some special tokens to the space character
		$space = $encodingConfiguration['spaceCharacter'];
		$processedTitle = preg_replace('/[ \-+_]+/', $space, $processedTitle);

			// Convert extended letters to ascii equivalents
		$processedTitle = $GLOBALS['TSFE']->csConvObj->specCharsToASCII($charset, $processedTitle);

			// Get an instance for the stopwords word filter service
		$wordFilter = t3lib_div::makeInstanceService('wordFilter', 'stopwords');
			// Load the list of allowed words (white list)
		$wordFilter->load(array('uid' => $this->configuration['whiteList']));

			// After initial transformation, check if processed title appears as is
			// in the white list. If no, continue processing.
		if (!$wordFilter->isValidWord($processedTitle)) {

				// Split on all non-word characters to break the title into the
				// consituent words
			$titleParts = preg_split('/[^a-zA-Z0-9]/', $processedTitle, -1, PREG_SPLIT_NO_EMPTY);
			$validWords = array();
			foreach ($titleParts as $word) {
				$isValidWord = TRUE;
					// Check if the word is long enough
				if ($this->configuration['minWordLength'] > 0 && strlen($word) < $this->configuration['minWordLength']) {
					$isValidWord = FALSE;
				}
					// If the word was rejected by the length test,
					// check if it is accepted by the word filter
				if (!$isValidWord) {
					$isValidWord |= $wordFilter->isValidWord($word);
				}
					// If the word is valid, keep it
				if ($isValidWord) {
					$validWords[] = $word;
				}
			}
				// Take all words that passed the first test and put them through the black list
			$wordFilter->load(array('uid' => $this->configuration['blackList']));
			$finalValidWords = array();
			foreach ($validWords as $word) {
				if ($wordFilter->isValidWord($word)) {
					$finalValidWords[] = $word;
				}
			}
				// If there were no valid words at all, fall back on already
				// processed title. Otherwise, assemble new title
			if (count($finalValidWords) == 0) {
				$processedTitle = $parameters['processedTitle'];
			} else {
				$processedTitle = implode($space, $finalValidWords);
				$processedTitle = trim($processedTitle, $space);
			}
		}
		return $processedTitle;
	}
}



if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/realurl_stopwords/sv1/class.tx_realurlstopwords_filter.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/realurl_stopwords/sv1/class.tx_realurlstopwords_filter.php']);
}

?>