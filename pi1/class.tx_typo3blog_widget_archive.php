<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2012 Roland Schmidt <rsch73@gmail.com>
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
 * [CLASS/FUNCTION INDEX of SCRIPT]
 *
 *
 *
 *   53: class tx_typo3blog_widget_archive extends tslib_pibase
 *   73:     private function init()
 *  106:     public function main($content, $conf)
 *  287:     private function mergeConfiguration()
 *  301:     private function fetchConfigValue($param)
 *  324:     private function getPostByRootLine()
 *
 * TOTAL FUNCTIONS: 5
 * (This index is automatically created/updated by the extension "extdeveval")
 *
 */

require_once(PATH_tslib . 'class.tslib_pibase.php');
require_once(t3lib_extMgm::extPath('typo3_blog') . 'lib/class.typo3blog_func.php');
include_once(PATH_site . 'typo3/sysext/cms/tslib/class.tslib_content.php');


/**
 * Plugin 'Typo3 Blog Archive' for the 'typo3_blog' extension.
 *
 * @author			Roland Schmidt <rsch73@gmail.com>
 * @package			TYPO3
 * @subpackage		tx_typo3blog
 */
class tx_typo3blog_widget_archive extends tslib_pibase
{
	public $prefixId = 'tx_typo3blog_widget_archive'; // Same as class name
	public $scriptRelPath = 'pi1/class.tx_typo3blog_widget_archive.php'; // Path to this script relative to the extension dir.
	public $extKey = 'typo3_blog'; // The extension key.
	public $pi_checkCHash = TRUE;
	private $envErrors = array();

	private $template = NULL;
	private $extConf = NULL;
	private $page_uid = NULL;
	private $blog_doktype_id = NULL;
	private $typo3BlogFunc = NULL;

	/**
	 * initializes this class
	 *
	 * @return	void
	 * @access private
	 */
	private function init()
	{
		// Make instance of tslib_cObj
		$this->cObj = t3lib_div::makeInstance('tslib_cObj');

		// Make instance of tslib_cObj
		$this->typo3BlogFunc = t3lib_div::makeInstance('typo3blog_func');
		$this->typo3BlogFunc->setCobj($this->cObj);

		// Merge current configuration from flexform and typoscript
		$this->mergeConfiguration();

		// unserialize extension conf
		$this->extConf = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['typo3_blog']);

		// Set current page id
		$this->page_uid = intval($this->conf['startPid']);

		// Set doktype id from extension conf
		$this->blog_doktype_id = $this->extConf['doktypeId'];

		// Read template file
		$this->template = $this->cObj->fileResource($this->conf['archive.']['templateFile']);
	}

	/**
	 * The main method of the PlugIn
	 *
	 * @param	string		$content:		The PlugIn content
	 * @param	array		$conf:			The PlugIn configuration
	 * @return	string		$content:		The content that is displayed on the website
	 * @access public
	 */
	public function main($content, $conf)
	{
		$this->conf = $conf;
		$this->pi_setPiVarDefaults();
		$this->pi_loadLL();
		$this->init();

		// Check the environment for typo3blog archive view
		if (NULL === $this->template) {
			return $this->pi_wrapInBaseClass(
				"Error :Template file " . $this->conf['archive.']['templateFile'] . " not found.<br />Please check the typoscript configuration!"
			);
		}

		if (!t3lib_div::testInt($this->blog_doktype_id)) {
			return $this->pi_wrapInBaseClass(
				"ERROR: doktype Id for page type blog not found.<br />Please set the doktype ID in extension conf!"
			);
		}

		// Get subparts from HTML template BLOGLIST_TEMPLATE
		$template = $this->cObj->getSubpart($this->template, '###ARCHIVE_TEMPLATE###');
		$subpartArchiveItems = $this->cObj->getSubpart($template, '###ARCHIVE_ITEMS###');


		// Define array and vars for template
		$subparts = array();
		$subparts['###ARCHIVE_ITEMS###'] = '';
		$subparts['###ARCHIVE_YEAR###'] = '';
		$subparts['###ARCHIVE_MONTH###'] = '';
		$subparts['###ARCHIVE_POST###'] = '';

		$markerArray = array();
		$markers = array();

		$markers['###ARCHIVE_TITLE###'] = 'Archiv';


/*
		$rows = array(
			array(
				'year' => '2010',
				'month' => 8,
				'quantity' => 4
			),
			array(
				'year' => '2010',
				'month' => 9,
				'quantity' => 2
			),
			array(
				'year' => '2010',
				'month' => 10,
				'quantity' => 6
			),
			array(
				'year' => '2010',
				'month' => 11,
				'quantity' => 3
			),

			array(
				'year' => '2011',
				'month' => 1,
				'quantity' => 2
			),
			array(
				'year' => '2011',
				'month' => 2,
				'quantity' => 3
			),
			array(
				'year' => '2011',
				'month' => 3,
				'quantity' => 4
			)
		);
*/
		//$GLOBALS['TYPO3_DB']->debugOutput = true;
		//$GLOBALS['TYPO3_DB']->store_lastBuiltQuery = true;

		// Query to load current category page with all post pages in rootline
		$sql = $GLOBALS['TYPO3_DB']->exec_SELECT_queryArray(array(
				'SELECT'	=> 'MONTH(FROM_UNIXTIME(crdate)) as month, YEAR(FROM_UNIXTIME(crdate)) as year, count(*) as quantity',
				'FROM'		=> 'pages',
				'WHERE'		=> 'pid IN (' . $this->getPostByRootLine() . ') AND hidden = 0 AND deleted = 0 AND doktype != ' . $this->blog_doktype_id,
				'GROUPBY'	=> 'year, month',
				'ORDERBY'	=> 'crdate DESC',
				'LIMIT'		=> ''
			)
		);
		$currentYear = NULL;
		// Execute sql and set retrieved records in marker for bloglist
		while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($sql)) {
		//foreach ($rows as $row) {
			// add data to ts template
			$this->cObj->data = $row;

			// Get data year, month and count
			if ($currentYear != $row['year']) {
				$currentYear = $row['year'];
				$subpartArchiveYear = $this->cObj->getSubpart($subpartArchiveItems, '###ARCHIVE_YEAR###');
				$year = $this->cObj->cObjGetSingle($this->conf['archive.']['marker.']['year'], $this->conf['archive.']['marker.']['year' . '.']);
				$markerArray['###' . strtoupper('year') . '###'] = $year;
				$subparts['###ARCHIVE_YEAR###'] = $this->cObj->substituteMarkerArrayCached($subpartArchiveYear, $markerArray);
			}

			$subpartArchiveMonth = $this->cObj->getSubpart($subpartArchiveYear, '###ARCHIVE_MONTH###');
			$month = $this->cObj->cObjGetSingle($this->conf['archive.']['marker.']['month'], $this->conf['archive.']['marker.']['month' . '.']);
			$quantity = $this->cObj->cObjGetSingle($this->conf['archive.']['marker.']['quantity'], $this->conf['archive.']['marker.']['quantity' . '.']);

			// Add data in $markerArray for subpart ARCHIVE_YEAR

			$markerArray['###' . strtoupper('month') . '###'] = $month;
			$markerArray['###' . strtoupper('quantity') . '###'] = $quantity;

			// Set Data in subpart Template
			$subparts['###ARCHIVE_MONTH###'] = $this->cObj->substituteMarkerArrayCached($subpartArchiveMonth, $markerArray);


			// Query to load pages for archive
			$sqlquery = $GLOBALS['TYPO3_DB']->exec_SELECT_queryArray(array(
					'SELECT'	=> '*',
					'FROM'		=> 'pages',
					'WHERE'		=> 'pid IN (' . $this->getPostByRootLine() . ') AND hidden = 0 AND deleted = 0 AND doktype != ' . $this->blog_doktype_id . ' AND MONTH(FROM_UNIXTIME(crdate)) = '.intval($row['month']) . ' AND YEAR(FROM_UNIXTIME(crdate)) = ' . intval($row['year']),
					'GROUPBY'	=> '',
					'ORDERBY'	=> 'crdate DESC',
					'LIMIT'		=> ''
				)
			);

/*
			unset($result);
			$result = array(
					array('title' => 'Post '.$row['year'].' '.$row['month'].' Post 1'),
					array('title' => 'Post '.$row['year'].' '.$row['month'].' Post 2'),
					array('title' => 'Post '.$row['year'].' '.$row['month'].' Post 3'),
					array('title' => 'Post '.$row['year'].' '.$row['month'].' Post 4')
			);
*/
			// Each all post from result $sqlquery
			while ($res = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($sqlquery)) {
//			foreach ($result as $res) {
				// add data to ts template
				$this->cObj->data = $res;
				$subpartArchivePost = $this->cObj->getSubpart($subpartArchiveMonth, '###ARCHIVE_POST###');

				// Each all records and set data in HTML template marker
				foreach ($res as $column => $data) {
					if ($this->conf['archive.']['marker.'][$column]) {
						$this->cObj->setCurrentVal($data);
						$data = $this->cObj->cObjGetSingle($this->conf['archive.']['marker.'][$column], $this->conf['archive.']['marker.'][$column . '.']);
						$this->cObj->setCurrentVal(false);
					} else {
						$this->cObj->setCurrentVal($data);
						$data = $this->cObj->stdWrap($data, $this->conf['archive.']['marker.'][$column . '.']);
						$this->cObj->setCurrentVal(false);
					}
					$markerArray['###' . strtoupper($column) . '###'] = $data;
				}
				$subparts['###ARCHIVE_POST###'] .= $this->cObj->substituteMarkerArrayCached($subpartArchivePost, $markerArray);
			}

			$subparts['###ARCHIVE_ITEMS###'] .=  $this->cObj->substituteSubpartArray($subpartArchiveItems, $subparts);
			$subparts['###ARCHIVE_POST###'] = '';
		}

		// Complete the template expansion by replacing the "content" marker in the template
		$content .= $this->typo3BlogFunc->substituteMarkersAndSubparts($template, $markers, $subparts);

		// Return the content to display in frontend
		return $this->pi_wrapInBaseClass($content);
	}

	/**
	 * THIS NICE PART IS FROM TYPO3 comments EXTENSION
	 * Merges TS configuration with configuration from flexform (latter takes precedence).
	 *
	 * @return	void
	 * @access private
	 */
	private function mergeConfiguration()
	{
		$this->pi_initPIflexForm();
	}

	/**
	 * THIS NICE FUNCTION IS FROM TYPO3 comments EXTENSION
	 * Fetches configuration value from flexform. If value exists, value in
	 * <code>$this->conf</code> is replaced with this value.
	 *
	 * @param	string		$param:		Parameter name. If <code>.</code> is found, the first part is section name, second is key (applies only to $this->conf)
	 * @return	void
	 * @access private
	 */
	private function fetchConfigValue($param)
	{
		if (strchr($param, '.')) {
			list($section, $param) = explode('.', $param, 2);
		}
		$value = trim($this->pi_getFFvalue($this->cObj->data['pi_flexform'], $param, ($section ? 's' . ucfirst($section) : 'sDEF')));
		if (!is_null($value) && $value != '') {
			if ($section) {
				$this->conf[$section . '.'][$param] = $value;
			}
			else {
				$this->conf[$param] = $value;
			}
		}
	}


	/**
	 * Get all sub pages from current page_id as string "123,124,125"
	 *
	 * @return	string
	 * @access private
	 */
	private function getPostByRootLine()
	{
		// Read all post uid's from rootline by current category page
		$this->cObj->data['recursive'] = 4;
		$pidList = $this->pi_getPidList(intval($this->page_uid), $this->cObj->data['recursive']);

		// return the string with all uid's and clean up
		return $GLOBALS['TYPO3_DB']->cleanIntList($pidList);
	}
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/typo3_blog/pi1/class.tx_typo3blog_widget_archive.php']) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/typo3_blog/pi1/class.tx_typo3blog_widget_archive.php']);
}

?>