<?php if( !defined('IN_WISY') ) die('!IN_WISY');



class WISY_SEARCH_RENDERER_CLASS
{
	var $framework;
	var $unsecureOnly = false;
	var $tokens;

	function __construct(&$framework)
	{
		// constructor
		$this->framework =& $framework;
		$this->rows = 20;
	}

	function pageSelLink($baseUrl, $currRowsPerPage, $currPageNumber, $hilite)
	{
		$ret = $hilite? '<strong class="wisy_paginate_pagelink">' : '<a class="wisy_paginate_pagelink" href="' . htmlentities($baseUrl) . intval($currPageNumber*$currRowsPerPage) . '">';
			$ret .= intval($currPageNumber+1);
		$ret .= $hilite? '</strong> ' : '</a> ';
		return $ret;
	}
	
	function pageSelCurrentpage($currOffset, $currRowsPerPage)
	{
		// find out the current page number (the current page number is zero-based)
		return $currPageNumber = intval($currOffset / $currRowsPerPage);
	}
	
	function pageSelMaxpages($totalRows, $currRowsPerPage)
	{
		// find out the max. page page number (also zero-based)
		$maxPageNumber = intval($totalRows / $currRowsPerPage);
		if( intval($totalRows / $currRowsPerPage) == $totalRows / $currRowsPerPage ) {
			$maxPageNumber--;
		}
		return $maxPageNumber;
	}
	
	
	function pageSel($baseUrl, $currRowsPerPage, $currOffset, $totalRows)
	{
		$page_sel_surround = 3;
		$currPageNumber = $this->pageSelCurrentpage($currOffset, $currRowsPerPage);
		$maxPageNumber = $this->pageSelMaxpages($totalRows, $currRowsPerPage);
	
		// find out the first/last page number surrounding the current page (zero-based)
		$firstPageNumber = $currPageNumber-$page_sel_surround;
		if( $firstPageNumber < $page_sel_surround ) {
			$firstPageNumber = 0;
		}
		
		$lastPageNumber = $currPageNumber+$page_sel_surround;
		if( $lastPageNumber > ($maxPageNumber-$page_sel_surround) ) {
			$lastPageNumber = $maxPageNumber;
		}
	
		// get the options string
		$options = '';
		if( $firstPageNumber != 0 ) {
			$options .= $this->pageSelLink($baseUrl, $currRowsPerPage, 0, 0) . '... ';
		}
		
		for( $i = $firstPageNumber; $i<=$lastPageNumber; $i++ ) {
			$options .= $this->pageSelLink($baseUrl, $currRowsPerPage, $i, $i==$currPageNumber? 1 : 0);
		}
		
		if( $lastPageNumber != $maxPageNumber ) {
			$options .= '... ' . $this->pageSelLink($baseUrl, $currRowsPerPage, $maxPageNumber, 0);
		}
	
		return trim($options);
	}
	
	function renderColumnTitle($title, $sollOrder, $istOrder, $info=0)
	{
		// Add column title class for use in responsive CSS
		echo '    <th class="wisyr_'. $this->framework->cleanClassname($title) .'">';
			
			if($this->framework->simplified)
			{
				echo $title;
			}
			else
			{
				if( $sollOrder )
				{
					if( $istOrder{0} == $sollOrder ) 
					{
						$dir = $istOrder{1}=='d'? 'd' /*desc*/ : 'a' /*asc*/;
						$newOrder = $sollOrder . ($dir=='d'? '' : 'd');
						$icon = $dir=='d'? ' &#9660;' /*v*/ : ' &#9650;' /*^*/;
					}
					else
					{
						$newOrder = $sollOrder;
						$icon = '';
					}
				
					echo '<a href="' . htmlspecialchars($this->framework->getUrl('search', array('q'=>$this->framework->getParam('q', ''), 'order'=>$newOrder))) . '" title="Liste nach diesem Kriterium sortieren" class="wisy_orderby">';
				}
			
				echo $title;
			
				if( $sollOrder )
				{			
					echo $icon . '</a>';
				}
			
				if( $info > 0 )
				{
					echo ' <a href="' . htmlspecialchars($this->framework->getHelpUrl($info)) . '" title="Hilfe" aria-label="Ratgeber zum Thema" class="wisy_help">i</a>';
				}
			}
		echo '</th>' . "\n";
	}
	
	function renderPagination($prevurl, $nexturl, $pagesel, $currRowsPerPage, $currOffset, $totalRows, $extraclass)
	{
		$currentPage = $this->pageSelCurrentpage($currOffset, $currRowsPerPage) + 1;
		$maxPages = $this->pageSelMaxpages($totalRows, $currRowsPerPage) + 1;
		echo ' <span class="wisy_paginate ' . $extraclass . '">';
			echo '<span class="wisy_paginate_seitevon">Seite ' . $currentPage . ' von ' . $maxPages . '</span>';
			echo '<span class="wisy_paginate_text">Gehe zu Seite</span>';
		
			if( $prevurl ) {
				echo " <a class=\"wisy_paginate_prev\" aria-label=\"Vorherige Seite\" href=\"" . htmlspecialchars($prevurl) . "\">&laquo;</a> ";
			}
	
			echo $pagesel;
	
			if( $nexturl ) {
				echo " <a class=\"wisy_paginate_next\" aria-label=\"Nächste Seite\" href=\"" . htmlspecialchars($nexturl) . "\">&raquo;</a>";
			}
		echo '</span>' . "\n";
	}
	
	protected function renderAnbieterCell2(&$db2, $record, $param)
	{
		$currAnbieterId = $record['id'];
		$anbieterName = $record['suchname'];
		$pruefsiegel_seit = $record['pruefsiegel_seit'];
		$anspr_tel = $record['anspr_tel'];

		echo '    <td class="wisy_anbieter wisyr_anbieter" data-title="Anbieter">';
			echo $this->framework->getSeals($db2, array('anbieterId'=>$currAnbieterId, 'seit'=>$pruefsiegel_seit, 'size'=>'small'));
			
			if( $anbieterName )
			{
				$aparam = array('id'=>$currAnbieterId, 'q'=>$param['q']);
				if( $param['promoted'] )
				{
					$aparam['promoted'] = intval($param['kurs_id']);
					echo '<span class="wisy_promoted_prefix">Anzeige von:</span> ';
				}
			
				if( $param['clickableName'] ) echo '<a href="'.$this->framework->getUrl('a', $aparam).'">';
					
					if( $param['addIcon'] ) {
						if( $record['typ'] == 2 ) echo '<span class="wisy_icon_beratungsstelle">Beratungsstelle<span class="dp">:</span></span> ';
					}
					
					echo htmlspecialchars($this->framework->encode_windows_chars(utf8_encode($anbieterName)));
					
				if( $param['clickableName'] ) echo '</a>';

				if( $param['addPhone'] && $anspr_tel )
				{
					// $anspr_tel = str_replace(' ', '', $anspr_tel); // macht Aerger, da in den Telefonnummern teilw. Erklaerungen/Preise mitstehen. Auskommentiert am 5.9.2008 (bp)
					$anspr_tel = str_replace('/', ' / ', $anspr_tel);
					echo '<span class="wisyr_comma">,</span><span class="wisyr_anbieter_telefon"> ' . htmlspecialchars(utf8_encode($anspr_tel)) . '</span>';
				}
				
				if( !$param['clickableName'] )  echo '<span class="wisyr_anbieter_profil"> - <a href="'.$this->framework->getUrl('a', $aparam).'">Anbieterprofil...</a></span>';
			}
			else
			{
				echo 'k. A.';
			}
		echo '</td>' . "\n";
	}
	
	function renderKursRecords(&$db, &$records, &$recordsToSkip, $param)
	{
		global $wisyPortalSpalten;

		$loggedInAnbieterId = $this->framework->getEditAnbieterId();

		// build skip hash
		$recordsToSkipHash = array();
		if( is_array($recordsToSkip['records']) )
		{
			reset($recordsToSkip['records']);
			while( list($i, $record) = each($recordsToSkip['records']) )
			{
				$recordsToSkipHash[ $record['id'] ] = true;
			}
		}

		// load all latlng values
		$distances = array();
		if( $this->hasDistanceColumn )
		{
			$ids = '';
			reset($records['records']);
			while( list($i, $record) = each($records['records']) )
			{
				$ids .= ($ids==''? '' : ', ') . $record['id'];
			}
			
			if ($ids != '' )
			{
				$x1 = $this->baseLng *  71460.0;
				$y1 = $this->baseLat * 111320.0;
				$sql = "SELECT kurs_id, lat, lng FROM x_kurse_latlng WHERE kurs_id IN ($ids)";
				$db->query($sql);
				while( $db->next_record() )
				{
					$kurs_id = intval($db->f8('kurs_id'));
					$x2 = (floatval($db->f8('lng')) / 1000000) *  71460.0;
					$y2 = (floatval($db->f8('lat')) / 1000000) * 111320.0;

					// calculate the distance between the points ($x1/$y1) and ($x2/$y2)
					// d = sqrt( (x1-x2)^2 + (y1-y2)^2 )
					$dx = $x1 - $x2; if( $dx < 0 ) $dx *= -1;
					$dy = $y1 - $y2; if( $dy < 0 ) $dy *= -1;
					$d = sqrt( $dx*$dx + $dy*dy ); // $d ist nun die Entfernung in Metern ;-)
					
					// remember the smallest distance
					if( !isset($distances[ $kurs_id ]) || $distances[ $kurs_id ] > $d )
					{
						$distances[ $kurs_id ] = $d;
					}
				}
			}
		}

		// go through result
		$durchfClass =& createWisyObject('WISY_DURCHF_CLASS', $this->framework);
		
		$kursAnalyzer =& createWisyObject('WISY_KURS_ANALYZER_CLASS', $this->framework);
		
		$fav_use = $this->framework->iniRead('fav.use', 0);
		
		$rows = 0;
		reset($records['records']);
		while( list($i, $record) = each($records['records']) )
		{	
			// get kurs basics
			$currKursId = $record['id'];
			$currAnbieterId = $record['anbieter'];
			$currKursFreigeschaltet = $record['freigeschaltet'];
			$durchfuehrungenIds = $durchfClass->getDurchfuehrungIds($db, $currKursId);

			// record already promoted? if so, skip the normal row
			if( $recordsToSkipHash[ $currKursId ] )
				continue;

			// dump kurs
			$rows ++;
			
			if( $param['promoted'] )
				$class = ' class="wisy_promoted"';
			else
				$class = ($rows%2)==0? ' class="wisy_even"' : '';
			
			echo "  <tr$class>\n";

				// SPALTE: kurstitel
				$db->query("SELECT id, suchname, pruefsiegel_seit, anspr_tel, typ FROM anbieter WHERE id=$currAnbieterId");
				$db->next_record();
				$anbieter_record = $db->Record;
					
				echo '    <td class="wisy_kurstitel wisyr_angebot" data-title="Angebot">';
					$aparam = array('id'=>$currKursId, 'q'=>$param['q']);
					if( $param['promoted'] ) {$aparam['promoted'] = $currKursId;}
					
					$aclass = '';
					if( $fav_use ) {
						$aclass = ' class="fav_add" data-favid="'.$currKursId.'"';
					}
					
					echo '<a href="' .$this->framework->getUrl('k', $aparam). "\"{$aclass}>";
						
							if( $currKursFreigeschaltet == 0 ) { echo '<em>Kurs in Vorbereitung:</em><br />'; }
							if( $currKursFreigeschaltet == 2 ) { echo '<em>Gesperrt:</em><br />'; }
							if( $currKursFreigeschaltet == 3 ) { echo '<em>Abgelaufen:</em><br />'; }
							
							if( $anbieter_record['typ'] == 2 ) echo '<span class="wisy_icon_beratungsstelle">Beratung<span class="dp">:</span></span> ';
							
							if($this->framework->iniRead('label.abschluss', 0) && count($kursAnalyzer->loadKeywordsAbschluss($db, 'kurse', $currKursId))) echo '<span class="wisy_icon_abschluss">Abschluss<span class="dp">:</span></span> ';
							if($this->framework->iniRead('label.zertifikat', 0) && count($kursAnalyzer->loadKeywordsZertifikat($db, 'kurse', $currKursId))) echo '<span class="wisy_icon_zertifikat">Zertifikat<span class="dp">:</span></span> ';
						
							echo htmlspecialchars($this->framework->encode_windows_chars(utf8_encode($record['titel'])));
						
					echo '</a>';
					if( $loggedInAnbieterId == $currAnbieterId )
					{
						$vollst = $record['vollstaendigkeit'];
						if( $vollst>=1 ) {
							echo " <span class=\"wisy_editvollstcol\" title=\"Vollst&auml;ndigkeit der Kursdaten, bearbeiten Sie den Kurs, um die Vollst&auml;ndigkeit zu erh&ouml;hen\">($vollst% vollst&auml;ndig)</span>";
						}
						echo '<br><span class="wisy_edittoolbar"><a href="'.$this->framework->getUrl('edit', array('action'=>'ek', 'id'=>$currKursId)).'">Bearbeiten</a></span>';
					}
				echo '</td>' . "\n";

				if (($wisyPortalSpalten & 1) > 0)
				{
					// SPALTE: anbieter

					$this->renderAnbieterCell2($db, $anbieter_record, array('q'=>$param['q'], 'addPhone'=>true, 'promoted'=>$param['promoted'], 'kurs_id'=>$currKursId));
				}
				
				// SPALTEN: durchfuehrung
				$addText = '';
				if( sizeof($durchfuehrungenIds) > 1 )
				{
					$addText = ' <span class="wisyr_termin_weitere"><a href="' .$this->framework->getUrl('k', $aparam). '">';
						$temp = sizeof($durchfuehrungenIds) - 1;
						$addText .= $temp==1? "$temp<span> weiterer...</span>" : "$temp<span> weitere...</span>";
					$addText .= '</a></span>';
				}
				
				$stichwoerter = $this->framework->loadStichwoerter($db, 'kurse', $currKursId);
				$durchfClass->formatDurchfuehrung($db, $currKursId, intval($durchfuehrungenIds[0]), 0, 0, 1, $addText, array('record'=>$record, 'stichwoerter'=>$stichwoerter));
				
				// SPALTE: Entfernung
				if( $this->hasDistanceColumn )
				{
					$cell = '<td class="wisyr_entfernung" data-title="Entfernung">';
					if( isset($distances[$currKursId]) )
					{
						$meters = $distances[$currKursId];
						if( $meters > 1500 )
						{
							// 1 km, 2 km etc.
							$km = intval(($meters+500)/1000); if( $km < 1 ) $km = 1;
							$cell .= '~' . $km . ' km';
						}
						else if( $meters > 550 )
						{
							// 100 m, 200 m etc.
							$hundreds = intval(($meters+50)/100); if( $hundreds < 1 ) $hundreds = 1;
							$cell .= '~' . $hundreds . '00 m';
						}
						else
						{
							$cell .= '&lt;500 m';
						}
					}
					else
					{
						$cell .= 'k. A.';
					}
					$cell .= '</td>';
					echo $cell;
					
				}
				
			echo '  </tr>' . "\n";
		}
	}
	
	function formatItem($tag_name, $tag_descr, $tag_type, $tag_help, $tag_freq, $addparam=0)
	{
		if( !is_array($addparam) ) $addparam = array();
		
		/* see also (***) in the JavaScript part*/
		$row_class   = 'ac_normal';
		$row_prefix  = '';
		$row_preposition = '';
		$row_postfix = '';
		
		/* base type */
		     if( $tag_type &   1 )	{ $row_class = "ac_abschluss";		      $row_preposition = ' zum '; $row_postfix = '<b>Abschluss</b>'; }
		else if( $tag_type &   2 )	{ $row_class = "ac_foerderung";		      $row_preposition = ' zur '; $row_postfix = 'F&ouml;rderung'; }
		else if( $tag_type &   4 )	{ $row_class = "ac_qualitaetszertifikat"; $row_preposition = ' zum '; $row_postfix = 'Qualit&auml;tszertifikat'; }
		else if( $tag_type &   8 )	{ $row_class = "ac_zielgruppe";		      $row_preposition = ' zur '; $row_postfix = 'Zielgruppe'; }
		else if( $tag_type &  16 )	{ $row_class = "ac_abschlussart";		  $row_preposition = ' zur '; $row_postfix = 'Abschlussart'; }
		else if( $tag_type & 128 )	{ $row_class = "ac_thema";		 		  $row_preposition = ' zum '; $row_postfix = 'Thema'; }
		else if( $tag_type & 256 )	{ $row_class = "ac_anbieter";		     
											  if( $tag_type &  0x10000 )    { $row_preposition = ' zum '; $row_postfix = 'Trainer'; }
										 else if( $tag_type &  0x20000 )    { $row_preposition = ' zur '; $row_postfix = 'Beratungsstelle'; }
										 else if( $tag_type & 0x400000 )    { $row_preposition = ' zum '; $row_postfix = 'Anbieterverweis'; }
										 else							    { $row_preposition = ' zum '; $row_postfix = 'Anbieter'; }
								    }
		else if( $tag_type & 512 )	{ $row_class = "ac_ort";                  $row_preposition = ' zum '; $row_postfix = 'Ort'; }
		else if( $tag_type & 1024 )	{ $row_class = "ac_sonstigesmerkmal";     $row_preposition = ' zum '; $row_postfix = 'sonstigen Merkmal'; }
		else if( $tag_type & 32768 ){ $row_class = "ac_unterrichtsart";       $row_preposition = ' zur '; $row_postfix = 'Unterrichtsart'; }
		else if( $tag_type & 65536 ){ $row_class = "ac_zertifikat";           $row_preposition = ' zum '; $row_postfix = 'Zertifikat'; }
	
		if( $addparam['hidetagtypestr'] ) {
			$row_preposition = '';
			$row_postfix = '';
		}

		/* frequency, end base type */ 
		if( $tag_freq > 0 )
		{
			$row_postfix = ($tag_freq==1? '1 Kurs' : "$tag_freq Kurse") . $row_preposition . $row_postfix;
		}
		
		if( $tag_descr ) 
		{
			$row_postfix = $tag_descr . ', ' . $row_postfix;
		}
		
		if( $row_postfix != '' )
		{
			$row_postfix = ' <span class="ac_tag_type">(' . $row_postfix . ')</span> ';
		}
	
		/* additional flags */
		if( $tag_type & 0x10000000 )
		{
			$row_prefix = '&nbsp; &nbsp; &nbsp; &nbsp; &#8594; ';
			$row_class .= " ac_indent";
		}	
		else if( $tag_type & 0x20000000 )
		{
			$row_prefix = ''; //05.06.2017 war: 'Meinten Sie: '
		}
		else
		{
			$row_prefix = ''; //13.05.2014 was: 'Suche nach '
		}
		
		
		/* help link */
		if( $tag_help != 0 )
		{
			$row_postfix .=
			 " <a class=\"wisy_help\" href=\"" . $this->framework->getUrl('g', array('id'=>$tag_help, 'q'=>$tag_name)) . "\" title=\"Ratgeber\" aria-label=\"Ratgeber zu " . $tag_name . "\">&nbsp;i&nbsp;</a>";
		}
		
		return '<span class="' .$row_class. '">' .
				$row_prefix . ' <a href="' . $this->framework->getUrl('search', array('q'=>$addparam['qprefix'].$tag_name)) . '">' . htmlspecialchars($tag_name) . '</a> ' . $row_postfix .
			   '</span>';
	}
	
	function formatItem_v2($tag_name, $tag_descr, $tag_type, $tag_help, $tag_freq, $tag_anbieter_id=false, $tag_groups='', $tr_class='', $queryString='')
	{
		/* see also (***) in the JavaScript part*/
		$row_class   = 'ac_normal';
		$row_type  = 'Lernziel';
		$row_count = '';
		$row_count_prefix = ($tag_freq == 1) ? ' Kurs zum' : ' Kurse zum';
		$row_info = '';
		$row_prefix = '';
		$row_postfix = '';
		$row_groups = '';
	
		/* base type */
		     if( $tag_type &   1 ) { $row_class = "ac_abschluss";		     $row_type = 'Abschluss'; }
		else if( $tag_type &   2 ) { $row_class = "ac_foerderung";		     $row_type = 'F&ouml;rderung'; $row_count_prefix = ($tag_freq == 1) ? ' Kurs zur' : ' Kurse zur'; }
		else if( $tag_type &   4 ) { $row_class = "ac_qualitaetszertifikat"; $row_type = 'Qualit&auml;tsmerkmal'; }
		else if( $tag_type &   8 ) { $row_class = "ac_zielgruppe";		     $row_type = 'Zielgruppe'; $row_count_prefix = ($tag_freq == 1) ? ' Kurs zur' : ' Kurse zur'; }
		else if( $tag_type &  16 ) { $row_class = "ac_abschlussart";		 $row_type = 'Abschlussart'; $row_count_prefix = ($tag_freq == 1) ? ' Kurs zur' : ' Kurse zur'; }
		else if( $tag_type &  64 ) { $row_class = "ac_synonym";				 $row_type = 'Verweis'; }
		else if( $tag_type & 128 ) { $row_class = "ac_thema";		 		 $row_type = 'Thema'; }
		else if( $tag_type & 256 ) { $row_class = "ac_anbieter";		     
									      if( $tag_type &  0x20000 ) { $row_type = 'Beratungsstelle'; $row_count_prefix = ($tag_freq == 1) ? ' Kurs von der' : ' Kurse von der'; }
									 else if( $tag_type & 0x400000 ) { $row_type = 'Tr&auml;gerverweis'; }
									 else							 { $row_type = 'Tr&auml;ger'; $row_count_prefix = ($tag_freq == 1) ? ' Kurs vom' : ' Kurse vom'; }
								   }
		else if( $tag_type & 512 ) { $row_class = "ac_ort";                  $row_type = 'Kursort'; $row_count_prefix = ($tag_freq == 1) ? ' Kurs am' : ' Kurse am'; }
		else if( $tag_type & 1024) { $row_class = "ac_merkmal";			 	 $row_type = 'Kursmerkmal'; }
		else if( $tag_type & 32768){ $row_class = "ac_unterrichtsart";		 $row_type = 'Unterrichtsart'; $row_count_prefix = ($tag_freq == 1) ? ' Kurs zur' : ' Kurse zur'; }
		else if( $tag_type & 65536){ $row_class = "ac_zertifikat";           $row_type = 'Zertifikat'; }

		if( $tag_descr ) $row_postfix .= ' <span class="ac_tag_type">('. $tag_descr .')</span>';
		
	
		if( $tag_freq > 0 ) {
			$row_count = $tag_freq;
			if($row_count_prefix == '') {
				$row_count .= ($tag_freq == 1) ? ' Kurs' : ' Kurse';
			} else {
				$row_count .= $row_count_prefix;
			}
		}

		/* additional flags */
		if( $tag_type & 0x10000000 )
		{
			$row_prefix = '<span class="wisyr_indent">&#8594;</span> ';
			$row_class .= " ac_indent";
		}	
		else if( $tag_type & 0x20000000 )
		{
			$row_prefix = 'Meinten Sie: ';
		}
		else
		{
			$row_prefix = ''; //13.05.2014 was: 'Suche nach '
		}
	
		if( $tag_groups ) $row_groups = implode('<br />', $tag_groups);
	
		if( $tag_help )
		{
			$row_info = '<a href="' . $this->framework->getUrl('g', array('id'=>$tag_help, 'q'=>$tag_name)) . '">Zeige Erkl&auml;rung</a>';
		} else if( $tag_type & 256 && $tag_anbieter_id ) {
			$row_info = '<a href="' . $this->framework->getUrl('a', array('id'=>$tag_anbieter_id)) . '">Zeige Tr&auml;gerprofil</a>';
		}
	
		$row_class = $row_class . ' ' . $tr_class;
		
		// highlight search string
		$tag_name = htmlspecialchars($tag_name);
		if($queryString != '') {
			//$tag_name_highlighted = str_ireplace($queryString, "<strong>$queryString</strong>", $tag_name);
			$tag_name_highlighted = preg_replace("/".preg_quote($queryString, "/")."/i", "<em>$0</em>", $tag_name);
		} else {
			$tag_name_highlighted = $tag_name;
		}
	
		return '<tr class="' .$row_class. '">' .
					'<td class="wisyr_tag_name" data-title="Rechercheziele">'. $row_prefix .'<a href="' . $this->framework->getUrl('search', array('q'=>$tag_name)) . '">' . $tag_name_highlighted . '</a>'. $row_postfix .'<span class="tag_count">'. $row_count .'</span></td>' . 
					'<td class="wisyr_tag_type" data-title="Kategorie">'. $row_type .'</td>' .
					'<td class="wisyr_tag_groups" data-title="Oberbegriffe">'. $row_groups .'</td>' . 
					'<td class="wisyr_tag_info" data-title="Zusatzinfo">'. $row_info . '</td>' .
			   '</tr>';
	}
	
	function renderTagliste($queryString)
	{
		$tagsuggestor =& createWisyObject('WISY_TAGSUGGESTOR_CLASS', $this->framework);
		$suggestions = $tagsuggestor->suggestTags($queryString);

		if( sizeof($suggestions) ) 
		{
			if($this->framework->iniRead('search.suggest.v2') == 1)
			{
				echo '<div class="wisyr_list_header"><h1 class="wisyr_rechercheziele">Suchvorschläge zum Stichwort &quot;' . htmlspecialchars(trim($this->framework->QS)) . '&quot;</h1></div>';
				echo '<table class="wisy_list wisy_tagtable">';
				echo '	<thead>';
				echo '		<tr>'.
								'<th class="wisyr_titel"><span class="title">Rechercheziele</span> <span class="tag_count">Angebote dazu</span></th>'.
								'<th class="wisyr_art">Kategorie</th>'.
								'<th class="wisyr_gruppe">Oberbegriffe</th>'.
								'<th class="wisyr_info">Zusatzinfo</th>'.
							'</tr>';
				echo '	</thead>';
				echo '	<tbody>';
				for( $i = 0; $i < sizeof($suggestions); $i++ )
				{
					$tr_class = ($i%2) ? 'ac_even' : 'ac_odd';
					echo $this->formatItem_v2($suggestions[$i]['tag'], $suggestions[$i]['tag_descr'], $suggestions[$i]['tag_type'], intval($suggestions[$i]['tag_help']), intval($suggestions[$i]['tag_freq']), $suggestions[$i]['tag_anbieter_id'], $suggestions[$i]['tag_groups'], $tr_class, $queryString);
				}
				echo '	</tbody>';
				echo '</table>';
			}
			else
			{
				echo '<h1 class="wisyr_rechercheziele">Ihre Suche nach &quot;' . htmlspecialchars(trim($this->framework->QS)) . '&quot; ergab keine Treffer</h1>';
				echo '<ul>';
					for( $i = 0; $i < sizeof($suggestions); $i++ )
					{
						echo '<li>' . $this->formatItem($suggestions[$i]['tag'], $suggestions[$i]['tag_descr'], $suggestions[$i]['tag_type'], intval($suggestions[$i]['tag_help']), intval($suggestions[$i]['tag_freq'])) . '</li>';
					}
				echo '</ul>';
			}
		}
		else
		{
			echo 'Keine Treffer.';
		}
	}
	
	function renderKursliste(&$searcher, $queryString, $offset, $showFilters=true, $baseurl='/search/', $hlevel=1)
	{
		global $wisyPortalSpalten;
	
		$validOrders = array('a', 'ad', 't', 'td', 'b', 'bd', 'd', 'dd', 'p', 'pd', 'o', 'od', 'creat', 'creatd', 'rand');
		$orderBy = $this->framework->order; if( !in_array($orderBy, $validOrders) ) $orderBy = 'b';

		$info = $searcher->getInfo();
		if( $info['changed_query'] || sizeof($info['suggestions']) )
		{
            $this->render_emptysearchresult_message($info, $false, $hlevel);
		}
		
		$sqlCount = $searcher->getKurseCount();
		
		if( $sqlCount )
		{
			$db = new DB_Admin();
			
			// create get prev / next URLs
			$prevurl = $offset==0? '' : $this->framework->getUrl($baseurl, array('q'=>$queryString, 'offset'=>$offset-$this->rows));
			$nexturl = ($offset+$this->rows<$sqlCount)? $this->framework->getUrl($baseurl, array('q'=>$queryString, 'offset'=>$offset+$this->rows)) : '';
			if( $prevurl || $nexturl )
			{	
				$param = array('q'=>$queryString);
				if( $orderBy != 'b' ) $param['order'] = $orderBy;
				$param['offset'] = '';
				$pagesel = $this->pageSel($this->framework->getUrl($baseurl, $param), $this->rows, $offset, $sqlCount);
			}
			else
			{
				$pagesel = '';
			}

			// render head
			echo '<div class="wisyr_list_header" role="region" aria-labelledby="wisy_list_title">';
				echo '<div class="wisyr_listnav"><span class="active">Kurse</span><a href="' . $baseurl . '?q=' . urlencode($queryString) . '%2C+Zeige:Anbieter">Anbieter</a></div>';
				echo '<div class="wisyr_filternav';
				if($this->framework->simplified && $this->framework->filterer->getActiveFiltersCount() > 0) echo ' wisyr_filters_active';
				echo '">';
			
				if( $queryString == '' ) {
					echo '<h' . $hlevel . ' id="wisy_list_title" class="wisyr_seitentitel wisyr_aktuelle_angebote">Aktuelle Angebote</h' . $hlevel . '>';
				}
				else {
					echo '<h' . $hlevel . ' id="wisy_list_title" class="wisyr_seitentitel wisyr_angebote_zum_suchauftrag">';
					echo $sqlCount==1? '<span class="wisyr_anzahl_angebote">1 Angebot</span> zum Suchauftrag ' : '<span class="wisyr_anzahl_angebote">' . $sqlCount . ' Angebote</span> zum Suchauftrag ';
					if($this->framework->simplified)
					{
						if(trim($this->framework->QS) != '')
						{
							echo '<span class="wisyr_angebote_suchauftrag">"' . htmlspecialchars($this->framework->QS) . '"</span>';
						}
					}
					else
					{
						echo '<span class="wisyr_angebote_suchauftrag">"' . htmlspecialchars((trim($queryString, ', '))) . '"</span>';	
					}
					echo '</h' . $hlevel . '>';
				}
				
				// Show filter / advanced search
				if( $showFilters )
				{
					$DEFAULT_FILTERLINK_HTML= '<a href="/filter?q=__Q_URLENCODE__" id="wisy_filterlink">Suche anpassen</a>';
					echo $this->framework->replacePlaceholders($this->framework->iniRead('searcharea.filterlink', $DEFAULT_FILTERLINK_HTML));
				
					$filterRenderer =& createWisyObject('WISY_FILTER_RENDERER_CLASS', $this->framework);
					$filterRenderer->renderForm($queryString, $searcher->getKurseRecords(0, 0, $orderBy), $hlevel);
				}

				if( $pagesel )
				{
					$this->renderPagination($prevurl, $nexturl, $pagesel, $this->rows, $offset, $sqlCount, 'wisyr_paginate_top');
				}
				
				echo '</div>';
			echo '</div>';
			
			flush();

			// render table start
			echo "\n".'<table class="wisy_list wisyr_kursliste" role="region" aria-label="Angebotsliste">' . "\n";
			
			// render column titles
			echo '  <thead><tr>' . "\n";
				$colspan = 0;
				$this->hasDistanceColumn = false;
												   {	$this->renderColumnTitle('Angebot',			't', 	$orderBy,	1333);			$colspan++; }
				if (($wisyPortalSpalten & 1)  > 0) {	$this->renderColumnTitle('Anbieter',		'a', 	$orderBy,	311); 			$colspan++; }
				if (($wisyPortalSpalten & 2)  > 0) {	$this->renderColumnTitle('Termin',			'b', 	$orderBy,	308);			$colspan++; }
				if (($wisyPortalSpalten & 4)  > 0) {	$this->renderColumnTitle('Dauer',			'd',	$orderBy,	0);				$colspan++; }
				if (($wisyPortalSpalten & 8)  > 0) {	$this->renderColumnTitle('Art',				'',		$orderBy,	1967);			$colspan++; }
				if (($wisyPortalSpalten & 16) > 0) {	$this->renderColumnTitle('Preis',			'p',	$orderBy,	309);			$colspan++; }
				if (($wisyPortalSpalten & 32) > 0) {	$this->renderColumnTitle('Ort',				'o', 	$orderBy,	1936);			$colspan++; }
				if (($wisyPortalSpalten & 64) > 0) {	$this->renderColumnTitle('Ang.-Nr.',		'', 	$orderBy,	0);				$colspan++; }
				if (($wisyPortalSpalten & 128)> 0) { 	$this->renderColumnTitle('BU',				'', 	$orderBy,	0);				$colspan++; }
				if (($wisyPortalSpalten & 256)> 0 
					&& $info['lat'] && $info['lng']) {  $this->renderColumnTitle('Entfernung',		'', 	$orderBy,	0);				$colspan++; $this->hasDistanceColumn = true; $this->baseLat = $info['lat']; $this->baseLng = $info['lng'];  }
			echo '  </tr></thead>' . "\n";
			
			// render promoted records
			$records2 = array();
			if( !$info['changed_query'] 
			 && $offset == 0 
			 && $this->framework->iniRead('useredit.promote', 0)!=0 )
			{
				global $wisyPortalId;
				$searcher2 =& createWisyObject('WISY_SEARCH_CLASS', $this->framework);
				$searcher2->prepare(mysql_real_escape_string($queryString) . ', schaufenster:' . $wisyPortalId);
				if( $searcher2->ok() )
				{
					$promoteCnt = $searcher2->getKurseCount();
					if( $promoteCnt > 0 )
					{
						$promoteRows = 3; 
						if( $promoteRows > $promoteCnt ) $promoteRows = $promoteCnt;
						
						$promoteOffset = mt_rand(0, $promoteCnt-$promoteRows);
						
						$records2 = $searcher2->getKurseRecords($promoteOffset, $promoteRows, 'rand');
						$notingToSkip = array();
						
						// render promoted head
						echo '<tr class="wisy_promoted_head"><td colspan="'.$colspan.'">';
							echo 'Schaufenster Weiterbildung ';
							echo '<a href="' .$this->framework->getHelpUrl(3368). '" class="wisy_help" title="Hilfe" aria-label="Ratgeber zu Schaufenster Weiterbildung">i</a>';
						echo '</td></tr>';
						
						// render promoted records
						$this->renderKursRecords($db, $records2, $nothingToSkip, array('q'=>$queryString, 'promoted'=>true));
						
						// log the rendered records
						if( $queryString != '' ) // do not decreease / log on the homepage, see E-Mails/04.11.2010
						{
							$promoter =& createWisyObject('WISY_PROMOTE_CLASS', $this->framework);
							$promoter->logPromotedRecordViews($records2, $queryString);
						}
					}
				}
				
			}
			
			// render other records
			$records = $searcher->getKurseRecords($offset, $this->rows, $orderBy);
			$this->renderKursRecords($db, $records, $records2 /*recordsToSkip*/, array('q'=>$queryString));
		
			// main table end
			echo '</table>' . "\n\n";
			flush();
			
			if( $pagesel )
			{
				echo '<div class="wisyr_list_footer clearfix" role="contentinfo">';
					echo '<div class="wisyr_rss_link_wrapper">' . $this->framework->getRSSLink() . '</div>';
					$this->renderPagination($prevurl, $nexturl, $pagesel, $this->rows, $offset, $sqlCount, 'wisyr_paginate_bottom');
				echo '</div>';
			}
		}
		else 
		{
			
			if( sizeof($info['suggestions']) == 0 )
			{
				$this->render_emptysearchresult_message($info, false, $hlevel);
			}
			
			echo '<div class="wisyr_list_footer clearfix">';
				echo '<div class="wisyr_rss_link_wrapper">' . $this->framework->getRSSLink() . '</div>';
			echo '</div>';
		}

		if( !$nexturl && $_SERVER['HTTPS']!='on' && !$this->framework->editSessionStarted ) {

			echo '	<div id="iwwb"><!-- BANNER IWWB START -->
				<script type="text/javascript">
			        var defaultZIP="PLZ";
			        var defaultCity="Ort";
			        var defaultKeywords = "Suchwörter eingeben";

			        function IWWBonFocusTextField(field,defaultValue){
			                if (field.value==defaultValue) field.value="";
			        }
			        function IWWBonBlurTextField(field,defaultValue){
			                if (field.value=="") field.value=defaultValue;
			        }
			        function IWWBsearch(button) {
			            if (button.form.feldinhalt1.value == defaultKeywords) {
			                        alert("Bitte geben Sie Ihre Suchw366rter ein!");
			                } else {
			                        if ((typeof button.form.feldinhalt2=="object") && button.form.feldinhalt2.value == defaultZIP) button
			.form.feldinhalt2.value="";
			                        if ((typeof button.form.feldinhalt3=="object") && button.form.feldinhalt3.value == defaultCity) button.form.feldinhalt3.value="";

			                        button.form.submit();
			                        if ((typeof button.form.feldinhalt2=="object") && button.form.feldinhalt2.value == "") button.form.feldinhalt2.value=defaultZIP;
			                        if ((typeof button.form.feldinhalt3=="object") && button.form.feldinhalt3.value == "") button.form.feldinhalt3.value=defaultCity;
			                }
			        }
			</script>

			<form method="post" action="https://www.iwwb.de/suchergebnis.php" target="IWWB" aria-label="Suche bei IWWB">
			<input type="hidden" name="external" value="true">
  <input type="hidden" name="method" value="iso">
  <input type="hidden" name="feldname1" id="feldname1" value="Freitext" />
  <input type="hidden" name="feldname2" id="feldname2" value="PLZOrt" />
  <input type="hidden" name="feldname3" id="feldname3" value="PLZOrt" />
  <input type="hidden" name="feldname7" id="feldname7" value="datum1" />
  <input type="hidden" name="feldinhalt7" id="feldinhalt7" value="morgen" />

			<table width="100%" border="0" cellpadding="4" cellspacing="0" style="border: 1px solid #777EA7;background-color: #EFEFF7;">
			<tr>
			<td style="font-family: Verdana, Arial, Helvetica, sans-serif;font-size: 11px;color: #5F6796;font-weight: bold;">Bundesweite
			Suche im InfoWeb Weiterbildung</td>
			<td><input name="feldinhalt1" type="text" style="height: 22px;width: 150px;font-family: Verdana, Arial, Helvetica, sans-serif
			;font-size: 11px;color: #000000;" value="' .  $queryString . '" onfocus="IWWBonFocusTextField(this,defaultKeywords)" onblur="IWWBonBlurTextField(this,defaultKeywords)"><input name="search" type
			="button" style="font-family: Verdana, Arial, Helvetica, sans-serif;font-size: 9px;height: 22px;width: 90px;background-color:
			 #A5AAC6;font-weight: bold;color: #FFFFFF;margin: 0px;border: 1px solid #FFFFFF;padding: 0px;" value="Suche starten" onClick=
			"IWWBsearch(this)"></td>
			<td align="right" style="font-family: Verdana, Arial, Helvetica, sans-serif;font-size: 11px;color: #5F6796;font-weight: bold;
			"><a href="https://www.iwwb.de" target="_blank"><img src="https://www.iwwb.de/web/images/iwwb.gif" border="0"></a>&nbsp;</td>
			</tr>
			</table>
			</form>
			</div>
			';
		
		}
	}
	
	function renderAnbieterliste(&$searcher, $queryString, $offset)
	{
		$anbieterRenderer =& createWisyObject('WISY_ANBIETER_RENDERER_CLASS', $this->framework);

		$validOrders = array('a', 'ad', 's', 'sd', 'p', 'pd', 'o', 'od', 'h', 'hd', 'e', 'ed', 't', 'td', 'creat', 'creatd');
		$orderBy = $this->framework->order; if( !in_array($orderBy, $validOrders) ) $orderBy = 'a';

		$db2 = new DB_Admin();

		$sqlCount = $searcher->getAnbieterCount();
		if( $sqlCount )
		{
			// create get prev / next URLs
			$prevurl = $offset==0? '' : $this->framework->getUrl('search', array('q'=>$queryString, 'offset'=>$offset-$this->rows));
			$nexturl = ($offset+$this->rows<$sqlCount)? $this->framework->getUrl('search', array('q'=>$queryString, 'offset'=>$offset+$this->rows)) : '';
			if( $prevurl || $nexturl )
			{	
				$param = array('q'=>$queryString);
				if( $orderBy != 'b' ) $param['order'] = $orderBy;
				$param['offset'] = '';
				$pagesel = $this->pageSel($this->framework->getUrl('search', $param), $this->rows, $offset, $sqlCount);
			}
			else
			{
				$pagesel = '';
			}

			// render head
			echo '<div class="wisyr_list_header" role="region" aria-labelledby="wisy_list_title">';
				echo '<div class="wisyr_listnav"><a href="search?q=' . urlencode(str_replace(array(',,', ', ,'), array(',', ','), str_replace('Zeige:Anbieter', '', $queryString))). '">Kurse</a><span class="active">Anbieter</span></div>';
				echo '<h1 id="wisy_list_title" class="wisyr_seitentitel wisyr_anbieter_zum_suchauftrag">';
				echo '<span class="wisyr_anzahl_anbieter">' . $sqlCount . ' Anbieter</span> zum Suchauftrag';
				echo '</h1>';

				if( $pagesel )
				{
					$this->renderPagination($prevurl, $nexturl, $pagesel, $this->rows, $offset, $sqlCount, 'wisyr_paginate_top');
				}
			echo '</div>' . "\n";
			flush();
			
			// render column titles
			echo "\n".'<table class="wisy_list wisyr_anbieterliste" role="region" aria-label="Anbieterliste">' . "\n";
			echo '  <thead><tr>' . "\n";
				$this->renderColumnTitle('Anbieter',	'a', 	$orderBy,	311);
				$this->renderColumnTitle('Straße',		's', 	$orderBy,	0);
				$this->renderColumnTitle('PLZ',			'p',	$orderBy,	0);
				$this->renderColumnTitle('Ort',			'o',	$orderBy,	0);
				$this->renderColumnTitle('Web',			'h',	$orderBy,	0);
				$this->renderColumnTitle('E-Mail',		'e',	$orderBy,	0);
				$this->renderColumnTitle('Telefon',		't',	$orderBy,	0);
			echo '  </tr></thead>' . "\n";

			// render records
			$records = $searcher->getAnbieterRecords($offset, $this->rows, $orderBy);
			$rows = 0;
			
			while( list($i, $record) = each($records['records']) )
			{
				
				$rows++;
				$class = ($rows%2)==0? ' class="wisy_even"' : '';
			
				echo "  <tr$class>\n";
					$this->renderAnbieterCell2($db2, $record, array('q'=>$queryString, 'addPhone'=>false, 'clickableName'=>true, 'addIcon'=>true));
					echo '<td class="wisyr_strasse" data-title="Straße">';
						echo htmlspecialchars(utf8_encode($record['strasse']));
					echo ' </td>';
					echo '<td class="wisyr_plz" data-title="PLZ">';
						echo htmlspecialchars(utf8_encode($record['plz']));
					echo ' </td>';
					echo '<td class="wisyr_ort" data-title="Ort">';
						echo htmlspecialchars(utf8_encode($record['ort']));
					echo ' </td>';
					echo '<td class="wisyr_homepage" data-title="Homepage">';
						$link = $record['homepage'];
						if( $link != '' )
						{
							if( substr($link, 0, 4) != 'http' )
								$link = 'http:/' . '/' . $link;
							echo '<a href="'.$link.'" target="_blank">Web</a>';
						}
					echo ' </td>';
					echo '<td class="wisyr_email" data-title="E-Mail">';
						$link = $record['anspr_email'];
						if( $link != '' )
							echo '<a href="' . $anbieterRenderer->createMailtoLink($link) . '" target="_blank">E-Mail</a>';
					echo ' </td>';
					echo '<td class="wisyr_telefon" data-title="Telefon">';
						echo '<a href="tel:' . htmlspecialchars(utf8_encode($record['anspr_tel'])) . '">' . htmlspecialchars(utf8_encode($record['anspr_tel'])) . '</a>';
					echo ' </td>';
				echo '  </tr>' . "\n";
			}

			// main table end
			echo '</table>' . "\n\n";
			flush();

			// render tail
			if( $pagesel )
			{
				echo '<div class="wisyr_list_footer clearfix" role="contentinfo">';
					echo '<div class="wisyr_rss_link_wrapper">' . $this->framework->getRSSLink() . '</div>';
					$this->renderPagination($prevurl, $nexturl, $pagesel, $this->rows, $offset, $sqlCount, 'wisyr_paginate_bottom');
				echo '</div>';
			}
		}
		else /* if( sqlCount ) */
		{
			$this->render_emptysearchresult_message();
		}
	}
	
	function render_emptysearchresult_message($info = false, $error = false, $hlevel=1) {
					
		echo '<div class="wisy_suggestions">';
		
		echo '<h' . $hlevel . ' class="wisyr_seitentitel wisyr_angebote_zum_suchauftrag"><span class="wisyr_anzahl_angebote">0 Angebote</span> zum Suchauftrag';
		if(trim($this->framework->QS) != '')
		{
			echo ' &quot;' . htmlspecialchars($this->framework->QS) . '&quot;</h1>';
		}
		else
		{
			echo '</h' . $hlevel . '>';
		}
		
		echo '<div class="wisy_suggestions_inner">';
		
		if($info['changed_query']) echo '<b>Hinweis:</b> Der Suchauftrag wurde abgeändert in <i><a href="'.$this->framework->getUrl('search', array('q'=>$info['changed_query'])).'">'.htmlspecialchars(utf8_encode($info['changed_query'])).'</a></i>';
			
		// Hinweis anpassen je nachdem ob Filter ausgewählt sind
		if(count($this->framework->tokensQF) == 0)
		{	
			// Leere Suche ohne gesetzte Filter
			if( sizeof($info['suggestions']) ) 
			{
				echo '<h' . ($hlevel + 1) . '>Suchvorschläge</h' . ($hlevel + 1) . '>';
				echo '<ul>';
				for( $i = 0; $i < sizeof($info['suggestions']); $i++ )
				{
					echo '<li>' . $this->formatItem($info['suggestions'][$i]['tag'], $info['suggestions'][$i]['tag_descr'], $info['suggestions'][$i]['tag_type'], intval($info['suggestions'][$i]['tag_help']), intval($suggestions[$i]['tag_freq'])) . '</li>';
				}
				echo '</ul>';
			}
			echo '<h' . ($hlevel + 1) . '>Anbietersuche</h' . ($hlevel + 1) . '>';
			echo '<p>Falls Sie auf der Suche nach einem bestimmten Kursanbieter sind, nutzen Sie bitte unser Anbieterverzeichnis.</p>';
			echo '<a href="/search?q=' . htmlspecialchars($this->framework->QS) . '%2C+Zeige%3AAnbieter">Eine Anbietersuche nach &quot;' . htmlspecialchars(trim($this->framework->QS)) . '&quot; ausführen</a>';
		
			echo '<h' . ($hlevel + 1) . '>Volltextsuche</h' . ($hlevel + 1) . '>';
			echo '<p>Das Ergebnis der Volltextsuche enthält alle Kurse, die den Suchbegriff oder den Wortteil in der Kursbeschreibung enthalten.</p>';
			echo '<a href="/search?q=volltext:' . htmlspecialchars($this->framework->QS) . '">Eine Volltextsuche nach &quot;' . htmlspecialchars(trim($this->framework->QS)) . '&quot; ausführen</a>';
		
			echo '<h' . ($hlevel + 1) . '>Möglicherweise helfen auch Veränderungen an Ihrem Suchbegriff:</h' . ($hlevel + 1) . '>';
			echo '<ul>';
			echo '<li>Prüfen Sie Ihren Suchbegriff auf Rechtschreibfehler</li>';
			echo '<li>Nutzen Sie die Suchvorschläge, die während der Eingabe unter dem Eingabefeld angezeigt werden</li>';
			echo '<li>Suchen Sie nach ähnlichen Stichwörtern oder Kursthemen</li>';
			echo '</ul>';
		}
		else
		{

			// Leere Suche mit gesetzen Filtern
			echo '<h' . ($hlevel + 1) . '>Möglicherweise helfen Veränderungen an Ihren Filtereinstellungen:</h' . ($hlevel + 1) . '>';
			echo '<ul>';
			echo '<li>Ändern oder entfernen Sie einzelne Filter, um mehr Angebote für Ihren Suchauftrag zu erhalten</li>';
			echo '<li>Suchen Sie nach ähnlichen Stichwörtern oder Kursthemen</li>';
			echo '</ul>';
			
			// Auch bei leerem Suchergebnis Filternavigation ausgeben
			echo '<div class="wisyr_list_header">';
				echo '<div class="wisyr_filternav';
				if($this->framework->simplified && $this->framework->filterer->getActiveFiltersCount() > 0) echo ' wisyr_filters_active';
				echo '">';
			
					// Show filter / advanced search
					$DEFAULT_FILTERLINK_HTML= '<a href="filter?q=__Q_URLENCODE__" id="wisy_filterlink">Suche anpassen</a>';
					echo $this->framework->replacePlaceholders($this->framework->iniRead('searcharea.filterlink', $DEFAULT_FILTERLINK_HTML));
			
					$filterRenderer =& createWisyObject('WISY_FILTER_RENDERER_CLASS', $this->framework);
					$filterRenderer->renderForm($this->framework->getParam('q', ''), array(), $hlevel);
				echo '</div>';
			echo '</div>';
		}
		
		// Fehler
		if(is_array($error) && count($error))
		{
			switch($error['id'])
			{
				case 'bad_location':
				case 'inaccurate_location':
				case 'bad_km':
				case 'km_without_bei':
					echo '<h' . ($hlevel + 1) . '>Fehler bei Ortssuche</h' . ($hlevel + 1) . '>';
					echo '<ul>';
					echo '<li>Überprüfen Sie Ihre Ortsangabe und nutzen Sie die Suchvorschläge bei der Ortseingabe</li>';
					echo '<li>Überprüfen Sie die gewählte Umkreis-Einstellung</li>';
					echo '</ul>';
					
				break;
			}
		}
		
		
		echo '<p><br /></p>';
		echo '</div>';
		echo '</div>';
	}
	
	function render()
	{	
		global $wisyPortalName;
		
		// get parameters
		// --------------------------------------------------------------------
		
		if($this->framework->simplified) {
			$queryString = $this->framework->Q;	
		} else {
			$queryString = $this->framework->getParam('q', '');
		}
		$redirect = false;
	
		if( $this->framework->iniRead('searcharea.radiussearch', 0) )
		{
			// add "bei" and "km" to the parameter "q" - this is needed as we forward only one paramter, eg. to subsequent pages
			$bei = trim($this->framework->getParam('bei', ''));
			if( $bei != '' ) {
				$bei = strtr($bei, array(', '=>'/', ','=>'/')); // convert the comma to slashes as commas are used to separate fields
				$queryString = trim($queryString, ', ');
				$queryString .= ($queryString!=''? ', ' : '') . 'bei:' . $bei;
			
				$km = intval($this->framework->getParam('km', ''));
				if( $km > 0 ) {
					$queryString .= ($queryString!=''? ', ' : '') . 'km:' . $km;
				}
			
				$redirect = true;
			}
		}
  	
		// Redirect if $queryString has changed
		if($redirect && $this->framework->getParam('r', 0) == 1) {
			// TODO
		}
		if($redirect && $this->framework->getParam('r', 0) != 1)
		{
			header('Location: search?q=' . urlencode($queryString) . '&qs=' . urlencode(htmlspecialchars($this->framework->QS)) . '&qf=' . urlencode(htmlspecialchars($this->framework->QF)) . '&r=1');
			exit();
		}
		
		$offset = htmlspecialchars(intval($_GET['offset'])); if( $offset < 0 ) $offset = 0;
		$title = trim($queryString, ', ');

		// page / ajax start
		// --------------------------------------------------------------------

		if( intval($_GET['ajax']) )
		{
			header('Content-type: text/html; charset=utf-8');
		}
		else
		{
			echo $this->framework->getPrologue(array(
												'title'		=>	$title,
												'bodyClass'	=>	'wisyp_search',
											));
			echo $this->framework->getSearchField();
			flush();
		}

		// result out
		// --------------------------------------------------------------------
		
		// Suche und Ergebnisliste auf Startseite optional abschalten
		
		if(intval(trim($this->framework->iniRead('search.startseite.disable'))) && $this->framework->getPageType() == "startseite") {
			echo '<h1 class="wisyr_seitentitel">' . utf8_encode($wisyPortalName) . '</h1>';
		} else {
			$searcher =& createWisyObject('WISY_INTELLISEARCH_CLASS', $this->framework);
			$searcher->prepare(mysql_real_escape_string($queryString));
		
			echo $this->framework->replacePlaceholders( $this->framework->iniRead('spalten.above', '') );
		
			echo '<div id="wisy_resultarea" class="' .$this->framework->getAllowFeedbackClass(). '" role="main">';
		
				if( $_GET['show'] == 'tags' )
				{
					$this->renderTagliste($queryString);
				}
				else if( $searcher->ok() )
				{
					$info = $searcher->getInfo();
					if( $info['show'] == 'kurse' )
					{
						$this->renderKursliste($searcher, $queryString, $offset);
					}
					else
					{
						$this->renderAnbieterliste($searcher, $queryString, $offset);
					}
				}
				else
				{
					$info  = $searcher->getInfo();
					$error = $info['error'];

					$this->render_emptysearchresult_message($info, $info['error']);
				}

				if( $this->framework->editSessionStarted )
				{
					$loggedInAnbieterId = $this->framework->getEditAnbieterId();
				
					$editor =& createWisyObject('WISY_EDIT_RENDERER_CLASS', $this->framework);
					$adminAnbieterUserIds = $editor->getAdminAnbieterUserIds();
				
					// get a list of all offers that are not "gesperrt"
					$temp = '0';
					$titles = array();
					$db = new DB_Admin;
					$db->query("SELECT id, titel FROM kurse WHERE anbieter=$loggedInAnbieterId AND user_created IN (".implode(',',$adminAnbieterUserIds).") AND freigeschaltet!=2;");
					while( $db->next_record() )
					{ 
						$currId = intval($db->f8('id')); $titles[ $currId ] = $db->f8('titel'); $temp .= ', ' . $currId;
					}
				
					// compare the 'offers that are not "gesperrt"' against the ones that are in the search index
					$liveIds = array();
					$db->query("SELECT kurs_id FROM x_kurse WHERE kurs_id IN($temp)");
					while( $db->next_record() )
					{
						$liveIds[ $db->f8('kurs_id') ] = 1;
					}
				
					// show 'offers that are not "gesperrt"' which are _not_ in the search index (eg. just created offers) below the normal search result
					echo '<p><span class="wisy_edittoolbar" title="Um einen neuen Kurs hinzuzufügen, klicken Sie oben auf &quot;Neuer Kurs&quot;">Kurse in Vorbereitung:</span>&nbsp; ';
						$out = 0;
						reset( $titles );
						while( list($currId, $currTitel) = each($titles) )
						{
							if( !$liveIds[ $currId ] )
							{
								echo $out? ' &ndash; ' : '';
								echo '<a href="k'.$currId.'">' . htmlspecialchars($currTitel) . '</a>';

								$out++; 
							}
						}
						if( $out == 0 ) echo '<i title="Um einen neuen Kurs hinzuzufügen, klicken Sie oben auf &quot;Neuer Kurs&quot;">keine</i>';
						//echo ' &ndash; <a href="edit?action=ek&amp;id=0">Neuer Kurs...</a>';
					echo '</p>';
				}
		
			echo '</div>';
		}
		
		echo $this->framework->replacePlaceholders( $this->framework->iniRead('spalten.below', '') );
		
		// page / ajax end
		// --------------------------------------------------------------------
		
		if( intval($_GET['ajax']) )
		{
			;
		}
		else
		{
			echo $this->framework->getEpilogue();
		}
	}
};
