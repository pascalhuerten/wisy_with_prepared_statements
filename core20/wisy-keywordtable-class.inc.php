<?php if( !defined('IN_WISY') ) die('!IN_WISY');
/*******************************************************************************
WISY
********************************************************************************

Render a keyword table

Usage:
	$ob =& createWisyObject('WISY_KEYWORDTABLE_CLASS', $framework, array('args' => '123, 456+, 789+2'));
	echo $ob->getHtml();

With args as:
-	comma-separated list of keyword IDs,
-	a `+` indicates that children should be added, too 
	the plus may be followed by a number indicating the maximum depth

@author Bjoern Petersen

*******************************************************************************/



class WISY_KEYWORDTABLE_CLASS
{
	protected static $keywords;
	protected static $sw_modified;

	function __construct(&$framework, $addparam)
	{
		$this->db = new DB_Admin;
		$this->framework =& $framework;
		$this->args = $addparam['args'];
		$this->selfGlossarId = intval($addparam['selfGlossarId']); // may be 0 if the page is not a glossar entry
		$this->rownum = 0;
		$this->tagSuggestor =& createWisyObject('WISY_TAGSUGGESTOR_CLASS', $framework);
		$this->searchRenderer =& createWisyObject('WISY_SEARCH_RENDERER_CLASS', $framework);
		$this->dbCache		=& createWisyObject('WISY_CACHE_CLASS', $this->framework, array('table'=>'x_cache_search', 'itemLifetimeSeconds'=>5*60*60)); // reset after 5 hours, needed to get updated tag frequencies

		if( !is_array(WISY_KEYWORDTABLE_CLASS::$keywords) )
		{
			WISY_KEYWORDTABLE_CLASS::$keywords = array();
			$this->db->query("SELECT id, stichwort, eigenschaften, zusatzinfo, glossar FROM stichwoerter;");
			while( $this->db->next_record() ) {
				WISY_KEYWORDTABLE_CLASS::$keywords[ $this->db->f('id') ] = $this->db->Record;
			}

			WISY_KEYWORDTABLE_CLASS::$sw_modified = '0000-00-00 00:00:00';
			$this->db->query("SELECT MAX(date_modified) d FROM stichwoerter;");
			if( $this->db->next_record() ) {
				WISY_KEYWORDTABLE_CLASS::$sw_modified = $this->db->f('d');
			}			
		}		
	}

	protected function getKeywordsRow($keywordId, $level, $hasChildren, $expanded, $showempty)
	{
		// collect common information
		$icon_arr_down = '&#9660;';
		$icon_arr_right = '&nbsp;&#9654;';
		$icon_empty = '&nbsp;&middot;&nbsp;';
				
		$title = WISY_KEYWORDTABLE_CLASS::$keywords[ $keywordId ]['stichwort'];
		$url = 'search?q=' . urlencode(g_sync_removeSpecialChars($title));
		$zusatzinfo = WISY_KEYWORDTABLE_CLASS::$keywords[ $keywordId ]['zusatzinfo'];
		$tag_type = WISY_KEYWORDTABLE_CLASS::$keywords[ $keywordId ]['eigenschaften'];
		$glossarId = WISY_KEYWORDTABLE_CLASS::$keywords[ $keywordId ]['glossar'];
				
		// get tag ID for the given keyword
		$tag_id = 0;
		$this->db->query("SELECT tag_id, tag_type FROM x_tags WHERE tag_name=".$this->db->quote($title));
		if( $this->db->next_record() ) {
			$tag_id = $this->db->f('tag_id');
		} 
		
		// get row type, class etc.
		$tag_freq = $this->tagSuggestor->getTagFreq(array($tag_id));
		if( $tag_freq == 0 && !$showempty ) {
			return '';
		}
		
		// render
		$ret = '<tr>';
			$ret .= '<td style="padding-left:'.intval($level*2).'em">';
			
				if( $hasChildren ) {
					if( $expanded ) {
						$ret .= $icon_arr_down . ' ';
					}
					else {
						$ret .= $icon_arr_right . ' ';
					}
				}
				else {
					$ret .= $icon_empty . ' ';
				}
								
				$ret .= $this->searchRenderer->formatItem($title, $zusatzinfo, $tag_type, 
					$glossarId != $this->selfGlossarId? $glossarId : 0, 
					$tag_freq);

			$ret .= '</td>';
			
		$ret .= '</tr>';

		$this->rownum ++;
		return $ret;
	}
	
	protected function getKeywordsDivRecursive($keywordId, $level, $expand)
	{
		// check for children
		$child_ids = array();
		$this->db->query("SELECT attr_id FROM stichwoerter_verweis2 WHERE primary_id=$keywordId ORDER BY structure_pos;");
		while( $this->db->next_record() ) {
			$child_ids[] = $this->db->f('attr_id');
		}

		$showempty = $this->showempty;
		if( $level == 0 || (sizeof($child_ids)!=0 && $expand > 0) ) {
			$showempty = true;
		}

		// add the item itself
		$ret = $this->getKeywordsRow($keywordId, $level, sizeof($child_ids)!=0, $expand, $showempty);
		
		// add children
		if( $expand > 0 ) 
		{
			for( $a = 0; $a < sizeof($child_ids); $a++ ) {
				$ret .= $this->getKeywordsDivRecursive($child_ids[$a], $level+1, $expand-1);
			}
		}
		
		return $ret;
	}
	
	public function getHtml()
	{
		// is the result in the cache?
		$cacheVersion = '1';
		$cacheKey = "wisykwt.$cacheVersion.".$GLOBALS['wisyPortalId'].".$this->args".".".WISY_KEYWORDTABLE_CLASS::$sw_modified;
		if( ($ret=$this->dbCache->lookup($cacheKey))!='' ) {
			return $ret;
		}
	
		// prepare html
		$ret = '';
		
		// parse keywordId string ...
		$temp = str_replace(' ', '', $this->args); // remove all spaces for easier parsing
		if( ($p=strpos($temp, ';'))!==false ) { $temp = substr($temp, 0, $p); } // allow comments after a `;` (this is undocumented stuff!)
		$temp = explode(',', $temp);

		// ... pass 1: check for special parameters
		$this->showempty = false;
		for( $k = 0; $k < sizeof($temp); $k++ ) 
		{
			if( $temp[$k] == 'showempty' ) {
				$this->showempty = true;
				
			}
			else {
				$keywordIds[] = $temp[$k];
			}
		}
		
		// ... pass 2: render all rows for each keyword ID		
		$ret_items = array();
		for( $k = 0; $k < sizeof($keywordIds); $k++ ) 
		{
			$expand = 0;
			$keywordId = $keywordIds[$k];
			if( ($p=strpos($keywordId, '+')) !== false ) { $expand = intval(substr($keywordId, $p+1)); if($expand<=0) {$expand=666;} $keywordId = substr($keywordId, 0, $p); }
			$keywordId = intval($keywordId);
			
			$ret .= $this->getKeywordsDivRecursive($keywordId, 0, $expand);
		}		
		
		// done, surround the result by a table
		if( $ret == '' ) 
		{
			$ret = '<tr><td><i>Keine aktuellen Angebote.</i></td></tr>';
		}

		$ret = '<table class="wisy_glskey">'
			.		'<thead>'
			.			'<tr>'
			.				'<td>Rechercheziele</td>'
			.			'<tr>'
			.		'</thead>'
			.		'<tbody>'
			.			$ret
			.		'</tbody>'
			. '</table>';
		
		// add to cache
		$this->dbCache->insert($cacheKey, $ret);		

		// done
		return $ret;
	}
	
		
};


