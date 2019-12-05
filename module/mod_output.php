<?

//
// init
//

include_once $_AS['basedir'] . 'inc/fnc.articlesystem_utilities.php'; //Basisklasse
include_once $_AS['basedir'] . 'inc/fnc.articlesystem_generate.php'; //Basisklasse
include_once $_AS['basedir'] . 'inc/class.articlesystem.php'; //Basisklasse
include_once $_AS['basedir'] . 'inc/class.lang.php'; //Sprachobjekt
include_once $_AS['basedir'] . 'inc/paginator.php';
    
$_AS['temp']=array();
if (empty($mvars[800]))
	$_AS['modkey']=$cms_mod['key'];
else
	$_AS['modkey']=as_cleanstring($mvars[800]);


//CMS Webrequest erstllen
$_AS['cms_wr'] =& $GLOBALS['sf_factory']->getObject('HTTP', 'WebRequest');

//AdoDB initialtisieren
$adodb =& $GLOBALS['sf_factory']->getObject('DATABASE', 'Ado');

//Articlesystem initializieren
$_AS['artsys_obj'] = new Articlesystem;

//Collectionklasse laden
include_once $_AS['basedir'] . 'inc/class.articlecollection.php';
include_once $_AS['basedir'] . 'inc/class.elementcollection.php';

if (file_exists($_AS['basedir'] . '../snippet_replacement/inc/class.SnippetReplacement.php')){
	include_once $_AS['basedir'] . '../snippet_replacement/inc/class.SnippetReplacement.php';
	if(class_exists('snippetReplacement')){
		  $_AS['sr']  = new snippetReplacement();
  		$_AS['temp']['sr_arr'] = $_AS['sr']->getDataByGroup('sr_lang', $client,$lang);
	}	
}

//Externe Variablen per CMS WebRequest holen
$_AS['idarticle'] = $_AS['cms_wr']->getVal($_AS['modkey'].'idarticle');

$_AS['temp']['idcatsideback']=$_AS['cms_wr']->getVal($_AS['modkey'].'idcatsideback');
if ($_AS['temp']['idcatsideback']==$idcatside)
	unset($_AS['temp']['idcatsideback']);
	
//
// get article from article select 
//
$_AS['temp']['article']=$_AS['cms_wr']->getVal($_AS['modkey'].'article');

// get, set and priorize db stored article
if (strpos($mvars[5],'{set_article_form}')!==false) {
	$_AS['temp']['setarticle']=$_AS['cms_wr']->getVal($_AS['modkey'].'setarticle');
	$_AS['temp']['article_mem']=as_get_val('as_'.$_AS['modkey'].'article_mem');
	
	if ($_AS['temp']['setarticle']!="") {
		as_set_val('as_'.$_AS['modkey'].'article_mem',$_AS['temp']['setarticle']);
		$_AS['temp']['article_mem']=$_AS['temp']['setarticle'];
	}
	
	if (!empty($_AS['temp']['article_mem']))
		$_AS['temp']['article']=$_AS['temp']['article_mem'];
	else if ($_AS['temp']['article_mem']!='0')
		$_AS['temp']['article']=-1;
}

// priorize frontend article selection
if (strpos($mvars[5],'{article_form}')!==false) {
	$_AS['temp']['article_recheck']=$_AS['cms_wr']->getVal($_AS['modkey'].'article');
	if (!empty($_AS['temp']['article_recheck'])) {
		$_AS['temp']['article']=$_AS['temp']['article_recheck'];
	}
}

if ((int) $_AS['temp']['article']>0)
	$_AS['idarticle']=$_AS['temp']['article'];



$_AS['temp']['idcatsideback']=$_AS['cms_wr']->getVal($_AS['modkey'].'idcatsideback');
if ($_AS['temp']['idcatsideback']==$idcatside)
	unset($_AS['temp']['idcatsideback']);
	
#for ($i=1;$i<11;$i++)
#	$_AS['temp']['customfilterselected'.$i] = $_AS['cms_wr']->getVal($_AS['modkey'].'cf'.$i);
#	
//Einige Config-Vars direkt holen
$_AS['config']['date'] = str_replace( array('{day}', '{month}', '{year}'), array('d', 'm', 'Y'),$mvars[10]);
$_AS['config']['time'] = str_replace( array('{hour}', '{minute}'), array('%H', '%M'), $mvars[11]);
$_AS['config']['time12'] = str_replace( array('{hour}', '{minute}'), array('%I', '%M'), $mvars[11]);
$_AS['config']['time24'] = str_replace( array('{hour}', '{minute}'), array('%H', '%M'), $mvars[11]);



// create category id<->name array for later use
$adodb =& $GLOBALS['sf_factory']->getObject('DATABASE', 'Ado');
$sql = "SELECT idcategory, name FROM ".$cfg_cms['db_table_prefix']."plug_articlesystem_category WHERE idclient='".$client."' AND idlang='".$lang."' ORDER BY name,hash ASC"; // AND idlang='".$idlang."'
$rs = $adodb->Execute($sql);
$_AS['temp']['categories']=array();
while (!$rs->EOF) {
    $_AS['temp']['categories'][$rs->fields[0]] = $rs->fields[1];
    $rs->MoveNext();
}
$rs->Close();

$_AS['temp']['cat_links'] = $mvars[760];	
$_AS['temp']['sort_links'] = $mvars[780];


//
// routing preperation
//

$_AS['cat_routing']['routings'] = array();		

if(!empty($mvars[300]) && empty($_AS['idarticle']) ) {

	$_AS['cat_routing']['idcatside'] = $idcatside;
	$_AS['cat_routing']['idcat'] = $idcat;

	$_AS['cat_routing']['category_temp'] = '';
	$_AS['cat_routing']['raw'] = trim( str_replace(' ', '',$mvars[300]));

  $_AS['cat_routing']['raw_vals'] = explode("\n", $_AS['cat_routing']['raw']);

  foreach ($_AS['cat_routing']['raw_vals'] AS $v) {
  	$v=trim($v);
    $_AS['cat_routing_pieces'] = explode('>', $v);
    $_AS['cat_routing']['routings'][ $_AS['cat_routing_pieces']['0'] ] = $_AS['cat_routing_pieces']['1'];
  }
  
	//source idcatside
	if (array_key_exists('idcatside:'.$idcatside, $_AS['cat_routing']['routings'])) {
	  //idcatside to as cat
		$_AS['cat_routing']['category_temp'] = $_AS['cat_routing']['routings']['idcatside:'.$idcatside];
		if ($_AS['cat_routing']['category_temp'] > 0) 
		  $_AS['routed']['category'] = $_AS['cat_routing']['category_temp'];
	// source idcat
	} else if (array_key_exists('idcat:'.$idcat, $_AS['cat_routing']['routings'])) { 
	  //idcat to as cat
	  $_AS['cat_routing']['category_temp'] = $_AS['cat_routing']['routings']['idcat:'.$idcat];
	  if ($_AS['cat_routing']['category_temp'] > 0) 
			$_AS['routed']['category'] = $_AS['cat_routing']['category_temp'];
	}

}

//
// sorting preperation
//
if(!empty($mvars[400])) {

	$_AS['sorting']['array'] = array();		

	$_AS['sorting']['raw'] = trim( str_replace(' ', '',$mvars[400]));

  $_AS['sorting']['raw_vals'] = explode("\n", $_AS['sorting']['raw']);
  foreach ($_AS['sorting']['raw_vals'] AS $v) {
    $_AS['sorting_pieces'] = explode('>', $v);
    
    if (strpos($_AS['sorting_pieces']['0'],'date')!==false || strpos($_AS['sorting_pieces']['0'],'time'))
    	$_AS['sorting_pieces']['0']='article_'.$_AS['sorting_pieces']['0'];

    if (strpos($_AS['sorting_pieces']['0'],'category')!==false)
    	$_AS['sorting_pieces']['0']='id'.$_AS['sorting_pieces']['0'];
  
    $_AS['sorting']['array'][$_AS['sorting_pieces']['0']]=$_AS['sorting_pieces']['1'];
  }

}

//
// get sort from links
//

$_AS['temp']['sortlinkelements']=array();

$_AS['temp']['sortlinkelements']['SDT']='article_startdate';
$_AS['temp']['sortlinkelements']['EDT']='article_enddate';
$_AS['temp']['sortlinkelements']['TXT']='text';
$_AS['temp']['sortlinkelements']['TSR']='teaser';
$_AS['temp']['sortlinkelements']['TTL']='title';
for ($i=1;$i<10;$i++)
	$_AS['temp']['sortlinkelements']['CT'.$i]='custom'.$i;
$_AS['temp']['sortlinkelements']['CT0']='custom10';
	
$_AS['temp']['sortlinkvalsold']=array();
$_AS['temp']['sortlinkvalsoldtemp']=array();
$_AS['temp']['sortlinkvalsoldstring'] = $_AS['cms_wr']->getVal($_AS['modkey'].'sort');

if (!empty($_AS['temp']['sortlinkvalsoldstring'])) {
	$_AS['sorting']['array']=array();
	$_AS['temp']['sortlinkvalsoldtemp']=explode(':',$_AS['temp']['sortlinkvalsoldstring']);
	foreach ($_AS['temp']['sortlinkvalsoldtemp'] as $v)
		$_AS['temp']['sortlinkvalsold'][substr($v,0,3)]=substr($v,0,4);
		if (substr($v,3,4)=='A')
			$_AS['sorting']['array'][$_AS['temp']['sortlinkelements'][substr($v,0,3)]]='ASC';
		else if (substr($v,3,4)=='D')
			$_AS['sorting']['array'][$_AS['temp']['sortlinkelements'][substr($v,0,3)]]='DESC';
}

//
// get current category
//

$_AS['temp']['category']=$_AS['cms_wr']->getVal($_AS['modkey'].'category');

// get, set and priorize db stored category
if (strpos($mvars[5],'{set_category_form}')!==false) {
	$_AS['temp']['setcategory']=$_AS['cms_wr']->getVal($_AS['modkey'].'setcategory');
	$_AS['temp']['category_mem']=as_get_val('as_'.$_AS['modkey'].'category_mem');
	
	if ($_AS['temp']['setcategory']!="") {
		as_set_val('as_'.$_AS['modkey'].'category_mem',$_AS['temp']['setcategory']);
		$_AS['temp']['category_mem']=$_AS['temp']['setcategory'];
	}
	
	if (!empty($_AS['temp']['category_mem']))
		$_AS['temp']['category']=$_AS['temp']['category_mem'];
	else if ($_AS['temp']['category_mem']!='0')
		$_AS['temp']['category']=-1;
}

// priorize frontend category selection
if (strpos($mvars[5],'{category_form}')!==false) {
	$_AS['temp']['category_recheck']=$_AS['cms_wr']->getVal($_AS['modkey'].'category');
	if (!empty($_AS['temp']['category_recheck'])) {
		$_AS['temp']['category']=$_AS['temp']['category_recheck'];
	}
}

if ((empty($_AS['temp']['category']) && $_AS['temp']['category']!=="0") && empty($_AS['routed']['category'])) 
	$_AS['temp']['category']=$mvars[8];
elseif (!empty($_AS['routed']['category']))
	$_AS['temp']['category']=$_AS['routed']['category'];


//
// get current search phrase
//

$_AS['temp']['searchstring']=trim(stripslashes($_AS['cms_wr']->getVal($_AS['modkey'].'searchstring')));
// deactivates time range
if (!empty($_AS['temp']['searchstring']))
	$mvars[1]='-1';
	
	
	
//
// get custom filter
//

$_AS['temp']['customfilters']=array();
$_AS['temp']['customfilters2']=array();
for ($i=1;$i<36;$i++){
	$_AS['temp']['customfilterselected'.$i] = $_AS['cms_wr']->getVal($_AS['modkey'].'cf'.$i);

	if (strpos($_AS['temp']['customfilterselected'.$i],'||')!==false){	
		if (substr($_AS['temp']['customfilterselected'.$i],0,2)=='||' && substr($_AS['temp']['customfilterselected'.$i],-2,2)=='||'){
			$_AS['temp']['customfilterselected'.$i]='%%'.substr($_AS['temp']['customfilterselected'.$i],2,strlen($_AS['temp']['customfilterselected'.$i])-4).'%%';
		}
	}
	if (!empty($_AS['temp']['customfilterselected'.$i]))
		$_AS['temp']['customfilters']['custom'.$i]=stripslashes($_AS['temp']['customfilterselected'.$i]);
		$_AS['temp']['customfilters2']['custom'.$i]=urlencode(str_replace('%%','||',$_AS['temp']['customfilters']['custom'.$i]));
}

//
// get startmonth and rangee
//

$_AS['temp']['startmonth2'] = $_AS['cms_wr']->getVal($_AS['modkey'].'startmonth2');
$_AS['temp']['startmonth'] = $_AS['cms_wr']->getVal($_AS['modkey'].'startmonth');
if (is_numeric($_AS['temp']['startmonth2'])) 
	$_AS['temp']['startmonth'] = $_AS['temp']['startmonth2'];
$_AS['config']['startmonth'] = (empty($_AS['temp']['startmonth']) && !is_numeric($_AS['temp']['startmonth'])) ? date("m") : $_AS['temp']['startmonth'];

$_AS['temp']['monthback'] = $_AS['cms_wr']->getVal($_AS['modkey'].'monthback');
$_AS['config']['monthback'] = (empty($_AS['temp']['monthback'])) ? $mvars[1] : $_AS['temp']['monthback'];
$_AS['config_static']['monthback']=$mvars[1];

if (empty($_AS['temp']['monthback']))
	 $_AS['config']['startmonth']= $_AS['config']['startmonth']- $_AS['config']['monthback'];


//
// special output-mode "teaser"
//  

if ($mvars['74']=='teaser' && !$_AS['idarticle']) {

  $_AS['collection'] = new ArticleCollection();
	$_AS['elements'] = new ArticleElements;

	// set category
	if($_AS['temp']['category']) 
  		$_AS['collection']->setIdcategory($_AS['temp']['category']);

	if ($mvars['74103']!='false') {
		$cms_url_side_addon='cms://idcatside=';
		$cms_url_cat_addon='cms://idcat=';
		$cms_url_cr_addon="\n";
		$exact_search=false;
	} else {
		$cms_url_cr_addon='';
		$cms_url_side_addon='';
		$cms_url_cat_addon='';
		$exact_search=true;
	}
	
	if ($mvars['74101']=='idcatside' || empty($mvars['74101']))
	  $_AS['collection']->setSearchString($cms_url_side_addon.$idcatside.$cms_url_cr_addon,array($mvars['74001']),$exact_search);
			
	if ($mvars['74101']=='idcat')
	  $_AS['collection']->setSearchString($cms_url_cat_addon.$idcat.$cms_url_cr_addon,array($mvars['74001']),$exact_search);
				
	if ($mvars['74101']=='idcat_r') {
		$_AS['temp']['tm_idcatparents']=as_get_idcat_parent_cats($idcat,$lang,$client);
	  $_AS['collection']->setSearchString($cms_url_cat_addon.implode($cms_url_cr_addon.' '.$cms_url_cat_addon,$_AS['temp']['tm_idcatparents']).$cms_url_cr_addon,array($mvars['74001']),$exact_search);
	}


	if ($_AS['collection']->countitems()>1 && $mvars['74102']=='true')
		$rnd=rand(1,$_AS['collection']->countitems());
	else if ($_AS['collection']->countitems()>=1)
		$rnd=1;
	else
		$_AS['idarticle']=0;


	if ($_AS['collection']->countitems()>=1) {

	  $_AS['collection']->generate();
	
	  $ic=0;

	  for($iter = $_AS['collection']->get(); $iter->valid(); $iter->next() ) {
	
			$ic++;
	
			$_AS['item'] =& $iter->current();

			// custom-field lf-seperation extraction & cms://-removement
			$_AS['temp']['cf_value_arr']=explode("\n",trim($_AS['item']->getDataByKey($mvars['74001'])));
			$_AS['temp']['cf_value']=trim($_AS['temp']['cf_value_arr'][0]);//url

			$_AS['temp']['cf_value']=str_replace('cms://idcat=','',$_AS['temp']['cf_value']);
			$_AS['temp']['cf_value']=str_replace('cms://idcatside=','',$_AS['temp']['cf_value']);
			$_AS['temp']['cf_value']=(int) $_AS['temp']['cf_value'];

		 	if (($mvars['74102']=='false' && $mvars['74101']=='idcat' &&
		 			 $_AS['temp']['cf_value']==$idcat ) ||
		 			($mvars['74102']=='false' && $mvars['74101']=='idcat_r' &&
		 			 in_array($_AS['temp']['cf_value'],$_AS['temp']['tm_idcatparents']) ) ||
		 	    ($mvars['74102']=='false' && $mvars['74101']=='idcatside' && 
		 	      $_AS['temp']['cf_value']==$idcatside ) ||
		 	    ($mvars['74102']=='true' && $ic==$rnd) )  {
				if (is_object($_AS['item'])) {
				
					if (as_is_side_in_cat($idcatside,$_AS['temp']['cf_value'],$lang,$client,(($mvars['74101']=='idcat_r') ? true:false)) &&
							$mvars['74101']!='idcatside')
						$_AS['idarticle'] = $_AS['item']->getDataByKey('idarticle');
				
					if ($mvars['74101']=='idcatside')
						$_AS['idarticle'] = $_AS['item']->getDataByKey('idarticle');
		
				}	 	
		 	
		 	}
	
		}
		
	}

}


//
// 
// detail view
//
//

if(is_numeric($_AS['idarticle']) && ($mvars['72']!='list' || $mvars['74']=='teaser')) {

	$_AS['temp']['searchstring']=htmlentities($_AS['temp']['searchstring'],ENT_COMPAT,'UTF-8');

  //Termin intialisieren
  $_AS['item'] = new SingleArticle;
  $_AS['elements'] = new ArticleElements;
  
  //Termin laden
  $_AS['item']->loadById($_AS['idarticle']);
	$_AS['item_elements']=$_AS['elements']->loadById($_AS['idarticle']);
  //Offline geschaltete Termine anzeigen? NEIN!


#  $_AS['collection']->setHideOffline(true);
#  
#  if ($mvars['73']=='true')
#		$_AS['collection']->setHideArchived(false);

		
  //Tpl in Tmp-Var kopieren
  $_AS['temp']['detail'] = $mvars[7];

	$_AS['config']['day'] = $mvars[10210];
	$_AS['config']['month'] = $mvars[10211];
	$_AS['config']['year'] = $mvars[10212];

	$_AS['temp']['data']	=		as_element_replacement(	$_AS['item'],
																										$_AS['artsys_obj'],
																										$_AS['item_elements']['image'],
																										$_AS['item_elements']['file'],
																										$_AS['item_elements']['link'],
																										$_AS['item_elements']['date'],
																										$mvars[7],
																										$mvars[700],
																										$mvars[720],
																										$mvars[740],
																										$mvars[750],
																										$_AS['temp']['categories'],
																										$_AS['config']);


#    $_AS['temp']['data']['organizer']				=	$_AS['item']->getDataByKey('organizer');

  //Url
	$_AS['page_nav_current_page']=$_AS['cms_wr']->getVal($_AS['modkey'].'page');

	$_AS['temp']['data']['url_back'] = as_url_creator ( $con_side[(empty($_AS['temp']['idcatsideback'])?$idcatside:$_AS['temp']['idcatsideback'])]['link'],
																							array (	'startmonth' => $_AS['config']['startmonth'],
																											'monthback' => $_AS['config']['monthback'],
																											'category' => $_AS['temp']['category'],
																											'searchstring' => $_AS['temp']['searchstring'],
																											'page' => $_AS['page_nav_current_page'],
																											'cf1' => $_AS['temp']['customfilters2']['custom1'],
																											'cf2' => $_AS['temp']['customfilters2']['custom2'],
																											'cf3' => $_AS['temp']['customfilters2']['custom3'],
																											'cf4' => $_AS['temp']['customfilters2']['custom4'],
																											'cf5' => $_AS['temp']['customfilters2']['custom5'],
																											'cf6' => $_AS['temp']['customfilters2']['custom6'],
																											'cf7' => $_AS['temp']['customfilters2']['custom7'],
																											'cf8' => $_AS['temp']['customfilters2']['custom8'],
																											'cf9' => $_AS['temp']['customfilters2']['custom9'],
																											'cf10' => $_AS['temp']['customfilters2']['custom10'],
																											'sort' => $_AS['cms_wr']->getVal($_AS['modkey'].'sort')) 
																										);		

  //Ausgeben, wenn nur aktuelle gew?nscht oder alle
  if($_AS['item']->available()) {
	
		//fill template
		foreach ($_AS['temp']['data'] as $k => $v){
			// global if-statement
			if(strpos($_AS['temp']['detail'],'{if_'.$k.'}')!==false)
				if (empty($v))
				  $_AS['temp']['detail'] = preg_replace('#\{if_'.$k.'\}(.*)\{/if_'.$k.'\}#sU','',$_AS['temp']['detail']);
				else
				  $_AS['temp']['detail'] = str_replace(array('{if_'.$k.'}','{/if_'.$k.'}'), array('',''), $_AS['temp']['detail']);

			// global if-not-statement
			if(strpos($_AS['temp']['detail'],'{if_not_'.$k.'}')!==false)
				if (empty($v))
			  	$_AS['temp']['detail'] = str_replace(array('{if_not_'.$k.'}','{/if_not_'.$k.'}'), array('',''), $_AS['temp']['detail']);
				else
				 	$_AS['temp']['detail'] = preg_replace('#\{if_not_'.$k.'\}(.*)\{/if_not_'.$k.'\}#sU','',$_AS['temp']['detail']);
											
			$_AS['temp']['detail']=str_replace('{'.$k.'}',$v,$_AS['temp']['detail']);

			// global if-value-statement
			if(strpos($_AS['temp']['detail'],'{if_'.$k.'=')!==false) {
				preg_match_all('/\{if_'.$k.'=(.*?)\}/',$_AS['temp']['detail'],$_AS['temp']['temp_results']);
				foreach ($_AS['temp']['temp_results'][0] as $ek => $ev) {
					if ($v!=$_AS['temp']['temp_results'][1][$ek]) {
					  $_AS['temp']['detail'] = preg_replace('#\{if_'.$k.'='.$_AS['temp']['temp_results'][1][$ek].'\}(.*)\{/if_'.$k.'='.$_AS['temp']['temp_results'][1][$ek].'\}#sU','',$_AS['temp']['detail']);
					} else {
					  $_AS['temp']['detail'] = str_replace(array('{if_'.$k.'='.$_AS['temp']['temp_results'][1][$ek].'}','{/if_'.$k.'='.$_AS['temp']['temp_results'][1][$ek].'}'), array('',''), $_AS['temp']['detail']);
					}
				}
			}		
	
			// global if-value-statement
			if(strpos($_AS['temp']['detail'],'{if_not_'.$k.'=')!==false) {
				preg_match_all('/\{if_not_'.$k.'=(.*?)\}/',$_AS['temp']['detail'],$_AS['temp']['temp_results']);
				foreach ($_AS['temp']['temp_results'][0] as $ek => $ev) {
					if ($v!=$_AS['temp']['temp_results'][1][$ek]) {
					  $_AS['temp']['detail'] = str_replace(array('{if_not_'.$k.'='.$_AS['temp']['temp_results'][1][$ek].'}','{/if_not_'.$k.'='.$_AS['temp']['temp_results'][1][$ek].'}'), array('',''), $_AS['temp']['detail']);
					} else {
					  $_AS['temp']['detail'] = preg_replace('#\{if_not_'.$k.'='.$_AS['temp']['temp_results'][1][$ek].'\}(.*)\{/if_not_'.$k.'='.$_AS['temp']['temp_results'][1][$ek].'\}#sU','',$_AS['temp']['detail']);
					}
				}
			}


  	}
  	
		// global if-backend
		if(strpos($_AS['temp']['detail'],'{if_backend}')!==false)
			if ($sess->name == 'sefrengo' && ($view == 'preview' || $view == 'edit')) {
			  $_AS['temp']['detail'] = str_replace(array('{if_backend}','{/if_backend}'), array('',''), $_AS['temp']['detail']);
			} else {
				$_AS['temp']['detail'] = preg_replace('#\{if_backend\}(.*)\{/if_backend\}#sU','',$_AS['temp']['detail']);
			}
		// global if-backend
		if(strpos($_AS['temp']['detail'],'{if_backend_edit}')!==false)
			if ($sess->name == 'sefrengo' && ($view != 'preview' && $view == 'edit')) {
			  $_AS['temp']['detail'] = str_replace(array('{if_backend_edit}','{/if_backend_edit}'), array('',''), $_AS['temp']['detail']);
			} else {
				$_AS['temp']['detail'] = preg_replace('#\{if_backend_edit\}(.*)\{/if_backend_edit\}#sU','',$_AS['temp']['detail']);
			}
		// global if-backend
		if(strpos($_AS['temp']['detail'],'{if_backend_preview}')!==false)
			if ($sess->name == 'sefrengo' && ($view == 'preview' && $view != 'edit')) {
			  $_AS['temp']['detail'] = str_replace(array('{if_backend_preview}','{/if_backend_preview}'), array('',''), $_AS['temp']['detail']);
			} else {
				$_AS['temp']['detail'] = preg_replace('#\{if_backend_preview\}(.*)\{/if_backend_preview\}#sU','',$_AS['temp']['detail']);
			} 	
		// global if-frontend
		if(strpos($_AS['temp']['detail'],'{if_frontend}')!==false)
			if (($sess->name != 'sefrengo')) {
			  $_AS['temp']['detail'] = str_replace(array('{if_frontend}','{/if_frontend}'), array('',''), $_AS['temp']['detail']);
			} else {
				$_AS['temp']['detail'] = preg_replace('#\{if_frontend\}(.*)\{/if_frontend\}#sU','',$_AS['temp']['detail']);
			}  	
  	

		if (strpos($_AS['temp']['detail'],'{chop}')!==false){
    	preg_match_all('#\{chop\}(.*)\{/chop\}#sU',$_AS['temp']['detail'],$_AS['temp']['chopparts']);
    	if (!empty($_AS['temp']['chopparts']))
	    	foreach ($_AS['temp']['chopparts'][1] as $k => $v)
	    		$_AS['temp']['detail']=str_replace(	$_AS['temp']['chopparts'][0][$k],
	    																				as_str_chop($v, $mvars['1003'], $mvars['1004'], $mvars['1005']),
	    																				$_AS['temp']['detail']);
	    else
	    	$_AS['temp']['detail']=str_replace(array('{chop}','{/chop}'), array('',''), $_AS['temp']['detail']);
		}

    
    if($mvars[70]!='true')
    	echo stripslashes($_AS['temp']['detail']);

  }	else
  	$_AS['temp']['detail']='';

  


}

//
// 
// list view
//
//


if ($mvars['72']=='list'){
	$_AS['idarticlemem']=$_AS['idarticle'];
	unset($_AS['idarticle']);
}

if((!is_numeric($_AS['idarticle']) || $mvars[70]=="true") && $mvars['72']!='detail') {



  //init
  $_AS['collection'] = new ArticleCollection();
	$_AS['elements'] = new ArticleElements;
	
#	if (empty($mvars[3]))
#		$_AS['collection']->setLegal(mktime(0,0,0,date('m'),date('d'),date('Y')));
#	else
		$_AS['collection']->setLegal(mktime(date('H'),date('i'),0,date('m'),date('d'),date('Y')));
		
	// set category
	if($_AS['temp']['category']) 
  		$_AS['collection']->setIdcategory($_AS['temp']['category']);

  //Offline geschaltete Termine anzeigen? NEIN!
  $_AS['collection']->setHideOffline(true);
  
  if ($mvars['73']=='true')
		$_AS['collection']->setHideArchived(false);
		
  //set sorting
	$_AS['collection']->setSorting();

	// set custom filters
	if (count($_AS['temp']['customfilters'])>0)
		$_AS['collection']->setCustomFilters($_AS['temp']['customfilters']);

	$_AS['collection']->setCustomWhere($mvars[10015]);

	// time rangee switch 		
	if($mvars[1]!='-1'){
	
		// next-link check
		if (mktime(	date('H'),date('i'),date('s'),
								date('m',mktime(date('H'),date('i'),date('s'),$_AS['config']['startmonth']+$_AS['config']['monthback'])),
								date('d',mktime(date('H'),date('i'),date('s'),$_AS['config']['startmonth']+$_AS['config']['monthback'])),
								date('Y',mktime(date('H'),date('i'),date('s'),$_AS['config']['startmonth']+$_AS['config']['monthback']))) <
				mktime(	date('H'),date('i'),date('s'),
								date('m'),
								date('d'),
								date('Y'))) {
			if (empty($mvars[80])){
					$_AS['collection']->setDateRange( mktime( 0,0,0,
																										date('m',mktime(date('H'),date('i'),date('s'),$_AS['config']['startmonth']+$_AS['config']['monthback'])),
																										date('d',mktime(date('H'),date('i'),date('s'),$_AS['config']['startmonth']+$_AS['config']['monthback'])),
																										date('Y',mktime(date('H'),date('i'),date('s'),$_AS['config']['startmonth']+$_AS['config']['monthback']))
																									),
																						mktime(
																										date('H'),date('i'),date('s'),date('m'),date('d'),date('Y') 
																									)
																					);
			} else {
					$_AS['collection']->setDateRange( mktime( 0,0,0,
																										date('m',mktime(0,0,0,$_AS['config']['startmonth']+$_AS['config']['monthback']+1,1)),



																										date('d',mktime(0,0,0,$_AS['config']['startmonth']+$_AS['config']['monthback']+1,1)),
																										date('Y',mktime(0,0,0,$_AS['config']['startmonth']+$_AS['config']['monthback']+1,1))
																									),
																						mktime( 
																										date('H'),date('i'),date('s'),date('m'),date('d'),date('Y') 
																									)
																					);		
			}
	
			  $_AS['collection']->generate();
				$iter = $_AS['collection']->get();
				$_AS['temp']['is_next']=$iter->valid();		
			} else {
				$_AS['temp']['is_next']=false;				
			}

			if (empty($mvars[80])){
				$_AS['collection']->setDateRange( mktime(
																									0,0,0,0,0,1971
																								),
																					mktime(
																									0,0,0,
																									date('m',mktime(date('H'),date('i'),date('s'),$_AS['config']['startmonth'])),
																									date('d',mktime(date('H'),date('i'),date('s'),$_AS['config']['startmonth'])),
																									date('Y',mktime(date('H'),date('i'),date('s'),$_AS['config']['startmonth']))
																								)
																				);
			} else {
				$_AS['collection']->setDateRange( mktime(
																									0,0,0,0,0,1971
																								),
																					mktime(
																									0,0,0,
																									date('m',mktime(0,0,0,$_AS['config']['startmonth'])),
																									date('d',mktime(0,0,0,$_AS['config']['startmonth'])),
																									date('Y',mktime(0,0,0,$_AS['config']['startmonth']))
																								)
																				);
			}


	  $_AS['collection']->generate();
		$iter = $_AS['collection']->get();
		$_AS['temp']['is_prev']=$iter->valid();
	} else {
		$_AS['temp']['is_next']=false;	
		$_AS['temp']['is_prev']=false;	
	}

		// set custom filters
		if (count($_AS['temp']['customfilters'])>0)
			$_AS['collection']->setCustomFilters($_AS['temp']['customfilters']);

		// if searchstring is set
		if (!empty($_AS['temp']['searchstring'])) {
			$_AS['collection']->setDateRange();
			$_AS['config']['monthback']=-1;
			
			if (!empty($mvars[16]))
				$_AS['collection']->setLimit($mvars[16]);
			else
				$_AS['collection']->setLimit(50);
				
			if (!empty($mvars[15]))
				$_AS['collection']->setSearchString($_AS['temp']['searchstring'],explode(',',$mvars[15]));
			else
				$_AS['collection']->setSearchString($_AS['temp']['searchstring']);
		}

	$_AS['temp']['searchstring']=htmlentities($_AS['temp']['searchstring'],ENT_COMPAT,'UTF-8');

	// set time-range to view
	if($mvars[1]!='-1' && $_AS['config']['monthback']!=-1){
		if (empty($mvars[80])){
			if (empty($mvars[3])){
				$_AS['timestamp_rangestart']=mktime(
																							0,0,0,
																							date('m',mktime(date('H'),date('i'),date('s'),$_AS['config']['startmonth'])),
																							date('d',mktime(date('H'),date('i'),date('s'),$_AS['config']['startmonth'])),
																							date('Y',mktime(date('H'),date('i'),date('s'),$_AS['config']['startmonth']))
																						);
				$_AS['timestamp_rangeend']	=	mktime(
																							23,59,59,
																							date('m',mktime(date('H'),date('i'),date('s'),$_AS['config']['startmonth']+$_AS['config']['monthback'])),
																							date('d',mktime(date('H'),date('i'),date('s'),$_AS['config']['startmonth']+$_AS['config']['monthback'])),
																							date('Y',mktime(date('H'),date('i'),date('s'),$_AS['config']['startmonth']+$_AS['config']['monthback']))
																						);	
			} else {
				$_AS['timestamp_rangestart']=mktime(
																							0,0,0,
																							date('m',mktime(date('H'),date('i'),date('s'),$_AS['config']['startmonth'])),
																							date('d',mktime(date('H'),date('i'),date('s'),$_AS['config']['startmonth'])),
																							date('Y',mktime(date('H'),date('i'),date('s'),$_AS['config']['startmonth']))
																						);
				$_AS['timestamp_rangeend']	=	mktime(
																							date('H'),date('i'),59,
																							date('m',mktime(date('H'),date('i'),date('s'),$_AS['config']['startmonth']+$_AS['config']['monthback'])),
																							date('d',mktime(date('H'),date('i'),date('s'),$_AS['config']['startmonth']+$_AS['config']['monthback'])),
																							date('Y',mktime(date('H'),date('i'),date('s'),$_AS['config']['startmonth']+$_AS['config']['monthback']))
																						);	
			}
		} else {
			$_AS['timestamp_rangestart']=mktime(
																						0,0,0,
																						date('m',mktime(0,0,0,$_AS['config']['startmonth']+1,1)),
																						date('d',mktime(0,0,0,$_AS['config']['startmonth']+1,1)),
																						date('Y',mktime(0,0,0,$_AS['config']['startmonth']+1,1))
																					);
			if ($_AS['config']['startmonth']+$_AS['config']['monthback']>date('m')-1) {
				if (empty($mvars[3])){
					$_AS['timestamp_rangeend']	=	mktime(
																								23,59,59,
																								date('m',mktime(date('H'),date('i'),date('s'),$_AS['config']['startmonth']+$_AS['config']['monthback'])),
																								date('d',mktime(date('H'),date('i'),date('s'),$_AS['config']['startmonth']+$_AS['config']['monthback'])),
																								date('Y',mktime(date('H'),date('i'),date('s'),$_AS['config']['startmonth']+$_AS['config']['monthback']))
																							);
				} else {
					$_AS['timestamp_rangeend']	=	mktime(
																								date('H'),date('i'),59,
																								date('m',mktime(date('H'),date('i'),date('s'),$_AS['config']['startmonth']+$_AS['config']['monthback'])),
																								date('d',mktime(date('H'),date('i'),date('s'),$_AS['config']['startmonth']+$_AS['config']['monthback'])),
																								date('Y',mktime(date('H'),date('i'),date('s'),$_AS['config']['startmonth']+$_AS['config']['monthback']))
																							);
				}
			} else {
				$_AS['timestamp_rangeend']	=	mktime(
																							23,59,59,
																							date('m',mktime(23,59,59,$_AS['config']['startmonth']+$_AS['config']['monthback']+1,0)),
																							date('d',mktime(23,59,59,$_AS['config']['startmonth']+$_AS['config']['monthback']+1,0)),
																							date('Y',mktime(23,59,59,$_AS['config']['startmonth']+$_AS['config']['monthback']+1,0))
																						);				
			}
		}		
		$_AS['collection']->setDateRange($_AS['timestamp_rangestart'],$_AS['timestamp_rangeend']);
	} else {
		$_AS['timestamp_rangestart']=mktime(0,0,0,0,0,1971);	
		if (empty($mvars[3])){
			$_AS['timestamp_rangeend']	=	mktime(
																						23,59,59,
																						date('m',mktime(date('H'),date('i'),date('s'),$_AS['config']['startmonth']+$_AS['config']['monthback'])),
																						date('d',mktime(date('H'),date('i'),date('s'),$_AS['config']['startmonth']+$_AS['config']['monthback'])),
																						date('Y',mktime(date('H'),date('i'),date('s'),$_AS['config']['startmonth']+$_AS['config']['monthback']))
																					);
		} else {
			$_AS['timestamp_rangeend']	=	mktime(
																						date('H'),date('i'),59,
																						date('m',mktime(date('H'),date('i'),date('s'),$_AS['config']['startmonth']+$_AS['config']['monthback'])),
																						date('d',mktime(date('H'),date('i'),date('s'),$_AS['config']['startmonth']+$_AS['config']['monthback'])),
																						date('Y',mktime(date('H'),date('i'),date('s'),$_AS['config']['startmonth']+$_AS['config']['monthback']))
																					);
		}

		$_AS['collection']->setDateRange($_AS['timestamp_rangestart'],$_AS['timestamp_rangeend']);
		$_AS['timestamp_rangestart']='';
		$_AS['timestamp_rangeend']='';
	}	

	$_AS['collection']->setSorting( $_AS['sorting']['array']);

	$_AS['collection']->setCustomWhere($mvars[10015]);


  //load data
#  $_AS['collection']->generate();
#
#	$_AS['page_nav_items']=count($_AS['collection']->_data);
	$_AS['page_nav_items']=$_AS['collection']->countitems();
	$mvars['48']=(int) $mvars['48'];
	if ($_AS['page_nav_items']>$mvars['48'] && $mvars['48']>0){
		// submited page no
		$_AS['page_nav_current_page']=$_AS['cms_wr']->getVal($_AS['modkey'].'page');
		// no submited page-no, calc the last

		if ($mvars[2]==1){
			$_AS['page_nav_pages']=ceil($_AS['page_nav_items']/$mvars['48']);
			if (empty($_AS['page_nav_current_page']))
				$_AS['page_nav_current_page']=$_AS['page_nav_pages'];
		} elseif (empty($_AS['page_nav_current_page']))
				$_AS['page_nav_current_page']=1;

		if ($mvars[2]==1)
			$_AS['collection']->setLimit($mvars['48'],-(($_AS['page_nav_current_page']*$mvars['48'])-($_AS['page_nav_pages']*$mvars['48'])));
		else
			$_AS['collection']->setLimit($mvars['48'],$mvars['48']*($_AS['page_nav_current_page']-1));			

		$_AS['pager'] = new Paginator($_AS['page_nav_current_page'],$_AS['page_nav_items']);
		//sets the number of records displayed
		//defaults to five			
		$_AS['pager']->set_Limit($mvars['48']);
		$_AS['pager']->set_Links(floor($mvars['40'])); 
		//if using numbered links this will set the number before and behind 
		//the current page.

		//gets starting point.
		$_AS['pager_limit1'] = $_AS['pager']->getRange1();	 
		//gets number of items displayed on page.
		$_AS['pager_limit2'] = $_AS['pager']->getRange2();	 
		
		$_AS['pager_links'] = $_AS['pager']->getLinkArr();
		$_AS['pager_current']=$_AS['pager']->getCurrent();			

		$_AS['pager_base_url']=	as_url_creator( $con_side[$idcatside]['link'], 
																	array(	'startmonth' => $_AS['config']['startmonth'],
																					'monthback' => $_AS['config']['monthback'],
																					'category' => $_AS['temp']['category'],
																					'searchstring' => $_AS['temp']['searchstring'],
																					'page' => $_AS['page_nav_current_page'],
																					'cf1' => $_AS['temp']['customfilters2']['custom1'],
																					'cf2' => $_AS['temp']['customfilters2']['custom2'],
																					'cf3' => $_AS['temp']['customfilters2']['custom3'],
																					'cf4' => $_AS['temp']['customfilters2']['custom4'],
																					'cf5' => $_AS['temp']['customfilters2']['custom5'],
																					'cf6' => $_AS['temp']['customfilters2']['custom6'],
																					'cf7' => $_AS['temp']['customfilters2']['custom7'],
																					'cf8' => $_AS['temp']['customfilters2']['custom8'],
																					'cf9' => $_AS['temp']['customfilters2']['custom9'],
																					'cf10' => $_AS['temp']['customfilters2']['custom10'],
																					'sort' => $_AS['cms_wr']->getVal($_AS['modkey'].'sort') 
																				)  
																);

		$_AS['page_nav']='';
		$_AS['page_nav_array']=array();
		if ($_AS['pager']->getTotalPages()>1 && ((!is_numeric($_AS['idarticle']) || $mvars[70]=="true") && $mvars['72']!='detail') ) {

			if ($mvars[2]==1)	{		

				if($_AS['pager']->getPrevious()){
					if (!empty($mvars['43']))
						$_AS['page_nav_first'] = '<a href="'.$_AS['pager_base_url'].'&amp;'.$_AS['modkey'].'page='.$_AS['pager']->getFirst().'" '.$mvars['4010'].'>'.$mvars['43'].'</a>';
					$_AS['page_nav_array'][] = $_AS['page_nav_first'];
					if (!empty($mvars['41']))
						$_AS['page_nav_prev'] = '<a href="'.$_AS['pager_base_url'].'&amp;'.$_AS['modkey'].'page='.$_AS['pager']->getPrevious().'" '.$mvars['4010'].'>'.$mvars['41'].'</a>';
					$_AS['page_nav_array'][] = $_AS['page_nav_prev'];
				}
				for($i=0;$i<count($_AS['pager_links']);$i++) {
					$_AS['pager_link']=$_AS['pager_links'][$i];
					if($_AS['pager_link'] == $_AS['page_nav_current_page'])
						$_AS['page_nav_array'][] = '<a href="'.$_AS['pager_base_url'].'&amp;'.$_AS['modkey'].'page='.$_AS['pager_link'].'" '.$mvars['4011'].'>'.($_AS['pager_link']).'</a>';
					else
						$_AS['page_nav_array'][] = '<a href="'.$_AS['pager_base_url'].'&amp;'.$_AS['modkey'].'page='.$_AS['pager_link'].'" '.$mvars['4010'].'>'.($_AS['pager_link']).'</a>';
				}

				if($_AS['pager']->getNext()){
					if (!empty($mvars['42']))
						 $_AS['page_nav_next'] = '<a href="'.$_AS['pager_base_url'].'&amp;'.$_AS['modkey'].'page='.$_AS['pager']->getNext().'" '.$mvars['4010'].'>'.$mvars['42'].'</a>';
					$_AS['page_nav_array'][] = $_AS['page_nav_next'];
					if (!empty($mvars['44']))
						 $_AS['page_nav_last'] = '<a href="'.$_AS['pager_base_url'].'&amp;'.$_AS['modkey'].'page='.($_AS['pager']->getLast()).'" '.$mvars['4010'].'>'.$mvars['44'].'</a>';
					$_AS['page_nav_array'][] = $_AS['page_nav_last'];
				}

			} else {

			if($_AS['pager']->getPrevious()){	
					if (!empty($mvars['43']))
						$_AS['page_nav_first'] = '<a href="'.$_AS['pager_base_url'].'&amp;'.$_AS['modkey'].'page='.($_AS['pager']->getFirst()).'" '.$mvars['4010'].'>'.$mvars['43'].'</a>';
					$_AS['page_nav_array'][] = $_AS['page_nav_first'];
					if (!empty($mvars['41']))
						$_AS['page_nav_prev'] = '<a href="'.$_AS['pager_base_url'].'&amp;'.$_AS['modkey'].'page='.$_AS['pager']->getPrevious().'" '.$mvars['4010'].'>'.$mvars['41'].'</a>';
					$_AS['page_nav_array'][] = $_AS['page_nav_prev'];
				}
				for($i=0;$i<count($_AS['pager_links']);$i++) {
					$_AS['pager_link']=$_AS['pager_links'][$i];
					if($_AS['pager_link'] == $_AS['page_nav_current_page'])
						$_AS['page_nav_array'][] = '<a href="'.$_AS['pager_base_url'].'&amp;'.$_AS['modkey'].'page='.$_AS['pager_link'].'" '.$mvars['4011'].'>'.($_AS['pager_link']).'</a>';
					else
						$_AS['page_nav_array'][] = '<a href="'.$_AS['pager_base_url'].'&amp;'.$_AS['modkey'].'page='.$_AS['pager_link'].'" '.$mvars['4010'].'>'.($_AS['pager_link']).'</a>';
				}
				if($_AS['pager']->getNext()){	
					if (!empty($mvars['42']))
						$_AS['page_nav_next'] = '<a href="'.$_AS['pager_base_url'].'&amp;'.$_AS['modkey'].'page='.$_AS['pager']->getNext().'" '.$mvars['4010'].'>'.$mvars['42'].'</a>';
					$_AS['page_nav_array'][] = $_AS['page_nav_next'];
					if (!empty($mvars['44']))
						$_AS['page_nav_last'] = '<a href="'.$_AS['pager_base_url'].'&amp;'.$_AS['modkey'].'page='.($_AS['pager']->getLast()).'" '.$mvars['4010'].'>'.$mvars['44'].'</a>';
					$_AS['page_nav_array'][] = $_AS['page_nav_last'];
				}
				
			}			

		}

		$_AS['page_nav']=implode($mvars['39'],$_AS['page_nav_array']);


	}

  $_AS['collection']->generate();

  $ic=0;

	if(!is_numeric($_AS['idarticle'])) {
  //Für jeden geladenenen Eintrag durchlaufen

	$_AS['config']['day'] = $mvars[10110];
	$_AS['config']['month'] = $mvars[10111];
	$_AS['config']['year'] = $mvars[10112];

  for($iter = $_AS['collection']->get(); $iter->valid(); $iter->next() ) {

    //Aktuellen Eintrag als Objekt bereitstellen
    $_AS['item'] =& $iter->current();

		$_AS['item_elements']=$_AS['elements']->loadById($_AS['item']->getDataByKey('idarticle'));
		
    //Tpl in Tmp-Var kopieren
    $_AS['temp']['list_output'] = $mvars[6];


		$_AS['temp']['data']	=	as_element_replacement(	$_AS['item'],
																										$_AS['artsys_obj'],
																										$_AS['item_elements']['image'],
																										$_AS['item_elements']['file'],
																										$_AS['item_elements']['link'],
																										$_AS['item_elements']['date'],
																										$mvars[6],
																										$mvars[700],
																										$mvars[720],
																										$mvars[740],
																										$mvars[750],
																										$_AS['temp']['categories'],
																										$_AS['config']);


		$_AS['temp']['first_routed_side']=array_slice(array_filter(explode('|',$_AS['item']->getDataByKey('idcategory'))),0,1);

    //Url
    if($mvars[70]=='true') {
    	//name='.urlencode($_AS['temp']['data']['title']).
			$_AS['temp']['data']['url'] = as_url_creator( $con_side[$idcatside]['link'],
																						 array(	'startmonth' => $_AS['config']['startmonth'],
																										'monthback' => $_AS['config']['monthback'],
																										'idarticle' => $_AS['item']->getDataByKey('idarticle'),
																										'category' => $_AS['temp']['category'],
																										'searchstring' => $_AS['temp']['searchstring'],
																										'page' => $_AS['page_nav_current_page'],
																										'cf1' => $_AS['temp']['customfilters2']['custom1'],
																										'cf2' => $_AS['temp']['customfilters2']['custom2'],
																										'cf3' => $_AS['temp']['customfilters2']['custom3'],
																										'cf4' => $_AS['temp']['customfilters2']['custom4'],
																										'cf5' => $_AS['temp']['customfilters2']['custom5'],
																										'cf6' => $_AS['temp']['customfilters2']['custom6'],
																										'cf7' => $_AS['temp']['customfilters2']['custom7'],
																										'cf8' => $_AS['temp']['customfilters2']['custom8'],
																										'cf9' => $_AS['temp']['customfilters2']['custom9'],
																										'cf10' => $_AS['temp']['customfilters2']['custom10'],
																										'sort' => $_AS['cms_wr']->getVal($_AS['modkey'].'sort') 
																									) 


																									);
		} else if($mvars[71]==0) {
			
    	$_AS['temp']['routed_idcatside']=(int) str_replace('idcatside:','',array_search($_AS['temp']['first_routed_side'][0],$_AS['cat_routing']['routings']));	
			if (!$_AS['temp']['routed_idcatside'])
				$_AS['temp']['routed_idcatside']=$idcatside;
			$_AS['temp']['data']['url'] = as_url_creator( $con_side[$_AS['temp']['routed_idcatside']]['link'],
																						 array(	'idcatsideback' => $idcatside,
																						 				'startmonth' => $_AS['config']['startmonth'],
																										'monthback' => $_AS['config']['monthback'],
																										'idarticle' => $_AS['item']->getDataByKey('idarticle'),
																										'category' => $_AS['temp']['category'],
																										'searchstring' => $_AS['temp']['searchstring'],
																										'page' => $_AS['page_nav_current_page'],
																										'cf1' => $_AS['temp']['customfilters2']['custom1'],
																										'cf2' => $_AS['temp']['customfilters2']['custom2'],
																										'cf3' => $_AS['temp']['customfilters2']['custom3'],
																										'cf4' => $_AS['temp']['customfilters2']['custom4'],
																										'cf5' => $_AS['temp']['customfilters2']['custom5'],
																										'cf6' => $_AS['temp']['customfilters2']['custom6'],
																										'cf7' => $_AS['temp']['customfilters2']['custom7'],
																										'cf8' => $_AS['temp']['customfilters2']['custom8'],
																										'cf9' => $_AS['temp']['customfilters2']['custom9'],
																										'cf10' => $_AS['temp']['customfilters2']['custom10'],
																											'sort' => $_AS['cms_wr']->getVal($_AS['modkey'].'sort') 
																									) 
																									);
		} else {
			$_AS['temp']['data']['url'] = as_url_creator( $con_side[$mvars[71]]['link'],
																						 array(	'idcatsideback' => $idcatside,
																						 				'startmonth' => $_AS['config']['startmonth'],
																										'monthback' => $_AS['config']['monthback'],
																										'idarticle' => $_AS['item']->getDataByKey('idarticle'),
																										'category' => $_AS['temp']['category'],
																										'searchstring' => $_AS['temp']['searchstring'],
																										'page' => $_AS['page_nav_current_page'],
																										'cf1' => $_AS['temp']['customfilters2']['custom1'],
																										'cf2' => $_AS['temp']['customfilters2']['custom2'],
																										'cf3' => $_AS['temp']['customfilters2']['custom3'],
																										'cf4' => $_AS['temp']['customfilters2']['custom4'],
																										'cf5' => $_AS['temp']['customfilters2']['custom5'],
																										'cf6' => $_AS['temp']['customfilters2']['custom6'],
																										'cf7' => $_AS['temp']['customfilters2']['custom7'],
																										'cf8' => $_AS['temp']['customfilters2']['custom8'],
																										'cf9' => $_AS['temp']['customfilters2']['custom9'],
																										'cf10' => $_AS['temp']['customfilters2']['custom10'],
																										'sort' => $_AS['cms_wr']->getVal($_AS['modkey'].'sort') 
																									)  
																									);
		}
		

    //Hinzufügen, wenn nur aktuelle gew?nscht oder alle
#    if($_AS['item']->available())  {

			//fill template
			foreach ($_AS['temp']['data'] as $k => $v){

        if ($k=='category')
          if ($v!=$v_mem || $ic==0){
	         	$v_mem=$v;
  	       	$_AS['list']['row_temp']=$mvars[17];

				// global if-statement
				if(strpos($_AS['list']['row_temp'],'{if_category}')!==false)
					if (empty($v))
					  $_AS['list']['row_temp'] = preg_replace('#\{if_category\}(.*)\{/if_category\}#sU','',$_AS['list']['row_temp']);
					else
					  $_AS['list']['row_temp'] = str_replace(array('{if_category}','{/if_category}'), array('',''), $_AS['list']['row_temp']);
				
				// global if-not-statement
				if(strpos($_AS['list']['row_temp'],'{if_not_category}')!==false)
					if (empty($v))
				  	$_AS['list']['row_temp'] = str_replace(array('{if_not_category}','{/if_not_category}'), array('',''), $_AS['list']['row_temp']);
					else
					 	$_AS['list']['row_temp'] = preg_replace('#\{if_not_category\}(.*)\{/if_not_category\}#sU','',$_AS['list']['row_temp']);

					$_AS['list']['row'][]=str_replace('{category}',$v,$_AS['list']['row_temp']);
				}
	
				// global if-statement
				if(strpos($_AS['temp']['list_output'],'{if_'.$k.'}')!==false){	
					if (empty($v))
					  $_AS['temp']['list_output'] = preg_replace('#\{if_'.$k.'\}(.*)\{/if_'.$k.'\}#sU','',$_AS['temp']['list_output']);
					else
					  $_AS['temp']['list_output'] = str_replace(array('{if_'.$k.'}','{/if_'.$k.'}'), array('',''), $_AS['temp']['list_output']);
				}
				// global if-not-statement
				if(strpos($_AS['temp']['list_output'],'{if_not_'.$k.'}')!==false)
					if (empty($v))
				  	$_AS['temp']['list_output'] = str_replace(array('{if_not_'.$k.'}','{/if_not_'.$k.'}'), array('',''), $_AS['temp']['list_output']);
					else
					 	$_AS['temp']['list_output'] = preg_replace('#\{if_not_'.$k.'\}(.*)\{/if_not_'.$k.'\}#sU','',$_AS['temp']['list_output']);

					$_AS['temp']['list_output']=str_replace('{'.$k.'}',$v,$_AS['temp']['list_output']);


				// global if-value-statement
				if(strpos($_AS['temp']['list_output'],'{if_'.$k.'=')!==false) {
					preg_match_all('/\{if_'.$k.'=(.*?)\}/',$_AS['temp']['list_output'],$_AS['temp']['temp_results']);
					foreach ($_AS['temp']['temp_results'][0] as $ek => $ev) {
						if ($v!=$_AS['temp']['temp_results'][1][$ek]) {
						  $_AS['temp']['list_output'] = preg_replace('#\{if_'.$k.'='.$_AS['temp']['temp_results'][1][$ek].'\}(.*)\{/if_'.$k.'='.$_AS['temp']['temp_results'][1][$ek].'\}#sU','',$_AS['temp']['list_output']);
						} else {
						  $_AS['temp']['list_output'] = str_replace(array('{if_'.$k.'='.$_AS['temp']['temp_results'][1][$ek].'}','{/if_'.$k.'='.$_AS['temp']['temp_results'][1][$ek].'}'), array('',''), $_AS['temp']['list_output']);
						}
					}
				}		
		
				// global if-value-statement
				if(strpos($_AS['temp']['list_output'],'{if_not_'.$k.'=')!==false) {
					preg_match_all('/\{if_not_'.$k.'=(.*?)\}/',$_AS['temp']['list_output'],$_AS['temp']['temp_results']);
					foreach ($_AS['temp']['temp_results'][0] as $ek => $ev) {
						if ($v!=$_AS['temp']['temp_results'][1][$ek]) {
						  $_AS['temp']['list_output'] = str_replace(array('{if_not_'.$k.'='.$_AS['temp']['temp_results'][1][$ek].'}','{/if_not_'.$k.'='.$_AS['temp']['temp_results'][1][$ek].'}'), array('',''), $_AS['temp']['list_output']);
						} else {
						  $_AS['temp']['list_output'] = preg_replace('#\{if_not_'.$k.'='.$_AS['temp']['temp_results'][1][$ek].'\}(.*)\{/if_not_'.$k.'='.$_AS['temp']['temp_results'][1][$ek].'\}#sU','',$_AS['temp']['list_output']);
						}
					}
				}

			}
			
			// global if-backend
			if(strpos($_AS['temp']['list_output'],'{if_backend}')!==false)
				if ($sess->name == 'sefrengo' && ($view == 'preview' || $view == 'edit')) {
				  $_AS['temp']['list_output'] = str_replace(array('{if_backend}','{/if_backend}'), array('',''), $_AS['temp']['list_output']);
				} else {
					$_AS['temp']['list_output'] = preg_replace('#\{if_backend\}(.*)\{/if_backend\}#sU','',$_AS['temp']['list_output']);
				}
			// global if-backend
			if(strpos($_AS['temp']['list_output'],'{if_backend_edit}')!==false)
				if ($sess->name == 'sefrengo' && ($view != 'preview' && $view == 'edit')) {
				  $_AS['temp']['list_output'] = str_replace(array('{if_backend_edit}','{/if_backend_edit}'), array('',''), $_AS['temp']['list_output']);
				} else {
					$_AS['temp']['list_output'] = preg_replace('#\{if_backend_edit\}(.*)\{/if_backend_edit\}#sU','',$_AS['temp']['list_output']);
				}
			// global if-backend
			if(strpos($_AS['temp']['list_output'],'{if_backend_preview}')!==false)
				if ($sess->name == 'sefrengo' && ($view == 'preview' && $view != 'edit')) {
				  $_AS['temp']['list_output'] = str_replace(array('{if_backend_preview}','{/if_backend_preview}'), array('',''), $_AS['temp']['list_output']);
				} else {
					$_AS['temp']['list_output'] = preg_replace('#\{if_backend_preview\}(.*)\{/if_backend_preview\}#sU','',$_AS['temp']['list_output']);
				}
			// global if-frontend
			if(strpos($_AS['temp']['list_output'],'{if_frontend}')!==false)
				if (($sess->name != 'sefrengo')) {
				  $_AS['temp']['list_output'] = str_replace(array('{if_frontend}','{/if_frontend}'), array('',''), $_AS['temp']['list_output']);
				} else {
					$_AS['temp']['list_output'] = preg_replace('#\{if_frontend\}(.*)\{/if_frontend\}#sU','',$_AS['temp']['list_output']);
				}  	
	  					
      $_AS['list']['row'][] = $_AS['temp']['list_output'];

  		$ic++;
		  		
#      }

    }
	}

	if(count($_AS['list']['row']) < 1)
		$_AS['temp']['rows'] = $mvars[9];
	else if(count($_AS['list']['row']) == 1)
	  $_AS['temp']['rows'] = $_AS['list']['row'][0];
	else
    $_AS['temp']['rows'] = implode("\n",$_AS['list']['row']);

	$_AS['output']['list_body']=$mvars[5];



if(is_numeric($_AS['idarticle'])) 
  $_AS['output']['list_body'] = preg_replace('#\{if_listview\}(.*)\{/if_listview\}#sU','',$_AS['output']['list_body']);
else
  $_AS['output']['list_body'] = str_replace(array('{if_listview}','{/if_listview}'), array('',''), $_AS['output']['list_body']);

if(!is_numeric($_AS['idarticle'])) 
  $_AS['output']['list_body'] = preg_replace('#\{if_detailview\}(.*)\{/if_detailview\}#sU','',$_AS['output']['list_body']);
else
  $_AS['output']['list_body'] = str_replace(array('{if_detailview}','{/if_detailview}'), array('',''), $_AS['output']['list_body']);

// global if-backend
if(strpos($_AS['output']['list_body'],'{if_backend}')!==false)
	if ($sess->name == 'sefrengo' && ($view == 'preview' || $view == 'edit')) {
	  $_AS['output']['list_body'] = str_replace(array('{if_backend}','{/if_backend}'), array('',''), $_AS['output']['list_body']);
	} else {
		$_AS['output']['list_body'] = preg_replace('#\{if_backend\}(.*)\{/if_backend\}#sU','',$_AS['output']['list_body']);
	}
// global if-backend
if(strpos($_AS['output']['list_body'],'{if_backend_edit}')!==false)
	if ($sess->name == 'sefrengo' && ($view != 'preview' && $view == 'edit')) {
	  $_AS['output']['list_body'] = str_replace(array('{if_backend_edit}','{/if_backend_edit}'), array('',''), $_AS['output']['list_body']);
	} else {
		$_AS['output']['list_body'] = preg_replace('#\{if_backend_edit\}(.*)\{/if_backend_edit\}#sU','',$_AS['output']['list_body']);
	}
// global if-backend
if(strpos($_AS['output']['list_body'],'{if_backend_preview}')!==false)
	if ($sess->name == 'sefrengo' && ($view == 'preview' && $view != 'edit')) {
	  $_AS['output']['list_body'] = str_replace(array('{if_backend_preview}','{/if_backend_preview}'), array('',''), $_AS['output']['list_body']);
	} else {
		$_AS['output']['list_body'] = preg_replace('#\{if_backend_preview\}(.*)\{/if_backend_preview\}#sU','',$_AS['output']['list_body']);
	}
// global if-frontend
if(strpos($_AS['output']['list_body'],'{if_frontend}')!==false)
	if (($sess->name != 'sefrengo')) {
	  $_AS['output']['list_body'] = str_replace(array('{if_frontend}','{/if_frontend}'), array('',''), $_AS['output']['list_body']);
	} else {
		$_AS['output']['list_body'] = preg_replace('#\{if_frontend\}(.*)\{/if_frontend\}#sU','',$_AS['output']['list_body']);
	}  


// global if-value-statement
if(strpos($_AS['output']['list_body'],'{if_idlang=')!==false) {
	preg_match_all('/\{if_idlang=(.*?)\}/',$_AS['output']['list_body'],$_AS['temp']['temp_results']);
	foreach ($_AS['temp']['temp_results'][0] as $ek => $ev) {
		if ($lang!=$_AS['temp']['temp_results'][1][$ek]) {
		  $_AS['output']['list_body'] = preg_replace('#\{if_idlang='.$_AS['temp']['temp_results'][1][$ek].'\}(.*)\{/if_idlang='.$_AS['temp']['temp_results'][1][$ek].'\}#sU','',$_AS['output']['list_body']);
		} else {
		  $_AS['output']['list_body'] = str_replace(array('{if_idlang='.$_AS['temp']['temp_results'][1][$ek].'}','{/if_idlang='.$_AS['temp']['temp_results'][1][$ek].'}'), array('',''), $_AS['output']['list_body']);
		}
	}
}		

// global if-value-statement
if(strpos($_AS['output']['list_body'],'{if_not_idlang=')!==false) {
	preg_match_all('/\{if_not_idlang=(.*?)\}/',$_AS['output']['list_body'],$_AS['temp']['temp_results']);
	foreach ($_AS['temp']['temp_results'][0] as $ek => $ev) {
		if ($lang!=$_AS['temp']['temp_results'][1][$ek]) {
		  $_AS['output']['list_body'] = str_replace(array('{if_not_idlang='.$_AS['temp']['temp_results'][1][$ek].'}','{/if_not_idlang='.$_AS['temp']['temp_results'][1][$ek].'}'), array('',''), $_AS['output']['list_body']);
		} else {
		  $_AS['output']['list_body'] = preg_replace('#\{if_not_idlang='.$_AS['temp']['temp_results'][1][$ek].'\}(.*)\{/if_not_idlang='.$_AS['temp']['temp_results'][1][$ek].'\}#sU','',$_AS['output']['list_body']);
		}
	}
}

	 if($mvars[70]=='true' && is_numeric($_AS['idarticle']))
			$_AS['output']['list_body'] = str_replace( '{content}',$_AS['temp']['detail'],$_AS['output']['list_body']);
	elseif($_AS['collection']->count() > 0)
	    $_AS['output']['list_body'] = str_replace( '{content}',$_AS['temp']['rows'],$_AS['output']['list_body']);
	else
	    $_AS['output']['list_body'] = str_replace( '{content}',$mvars[9],$_AS['output']['list_body']);
	// replace page nav
  $_AS['output']['list_body'] = str_replace( '{page_nav}',$_AS['page_nav'],$_AS['output']['list_body']);	
  $_AS['output']['list_body'] = str_replace( '{page_nav_prev}',$_AS['page_nav_prev'],$_AS['output']['list_body']);	
  $_AS['output']['list_body'] = str_replace( '{page_nav_next}',$_AS['page_nav_next'],$_AS['output']['list_body']);	
  $_AS['output']['list_body'] = str_replace( '{page_nav_first}',$_AS['page_nav_first'],$_AS['output']['list_body']);	
  $_AS['output']['list_body'] = str_replace( '{page_nav_last}',$_AS['page_nav_last'],$_AS['output']['list_body']);

	if ($mvars['48'] > 0)
	  if (ceil($_AS['page_nav_items']/$mvars['48'])>1){	
		  $_AS['output']['list_body'] = str_replace( '{pages_current}',$_AS['pager_current'],$_AS['output']['list_body']);	
		  $_AS['output']['list_body'] = str_replace( '{pages_total}',ceil($_AS['page_nav_items']/$mvars['48']),$_AS['output']['list_body']);	
		} else {
		  $_AS['output']['list_body'] = str_replace( '{pages_current}','',$_AS['output']['list_body']);	
		  $_AS['output']['list_body'] = str_replace( '{pages_total}','',$_AS['output']['list_body']);	
		}
	else {
		  $_AS['output']['list_body'] = str_replace( '{pages_current}','',$_AS['output']['list_body']);	
		  $_AS['output']['list_body'] = str_replace( '{pages_total}','',$_AS['output']['list_body']);	
		}	

	$_AS['config']['day'] = $mvars[10010];
	$_AS['config']['month'] = $mvars[10011];
	$_AS['config']['year'] = $mvars[10012];

	if(	!empty($_AS['timestamp_rangestart']) && !empty($_AS['timestamp_rangeend'])){
		$_AS['output']['list_body'] = str_replace( '{range}',$mvars[21],$_AS['output']['list_body']);
	  $_AS['output']['list_body'] = str_replace( '{range_time_from}',strftime($_AS['config']['time'], $_AS['timestamp_rangestart']),$_AS['output']['list_body']);	
	  $_AS['output']['list_body'] = str_replace( '{range_time24_from}',strftime($_AS['config']['time24'], $_AS['timestamp_rangestart']),$_AS['output']['list_body']);	
	  $_AS['output']['list_body'] = str_replace( '{range_time12_from}',strftime($_AS['config']['time12'], $_AS['timestamp_rangestart']),$_AS['output']['list_body']);	
	  $_AS['output']['list_body'] = str_replace( '{range_date_from}',date($_AS['config']['date'], $_AS['timestamp_rangestart']),$_AS['output']['list_body']);	
	  $_AS['output']['list_body'] = str_replace( '{range_date_from:day}',date($_AS['config']['day'], $_AS['timestamp_rangestart']),$_AS['output']['list_body']);	
	  $_AS['output']['list_body'] = str_replace( '{range_date_from:month}',date($_AS['config']['month'], $_AS['timestamp_rangestart']),$_AS['output']['list_body']);	
	  $_AS['output']['list_body'] = str_replace( '{range_date_from:year}',date($_AS['config']['year'], $_AS['timestamp_rangestart']),$_AS['output']['list_body']);	
	  $_AS['output']['list_body'] = str_replace( '{range_time_to}',strftime($_AS['config']['time'],$_AS['timestamp_rangeend']),$_AS['output']['list_body']);	
	  $_AS['output']['list_body'] = str_replace( '{range_time24_to}',strftime($_AS['config']['time24'],$_AS['timestamp_rangeend']),$_AS['output']['list_body']);	
	  $_AS['output']['list_body'] = str_replace( '{range_time12_to}',strftime($_AS['config']['time12'],$_AS['timestamp_rangeend']),$_AS['output']['list_body']);	
	  $_AS['output']['list_body'] = str_replace( '{range_date_to}',date($_AS['config']['date'],$_AS['timestamp_rangeend']),$_AS['output']['list_body']);	
	  $_AS['output']['list_body'] = str_replace( '{range_date_to:day}',date($_AS['config']['day'],$_AS['timestamp_rangeend']),$_AS['output']['list_body']);	
	  $_AS['output']['list_body'] = str_replace( '{range_date_to:month}',date($_AS['config']['month'],$_AS['timestamp_rangeend']),$_AS['output']['list_body']);	
	  $_AS['output']['list_body'] = str_replace( '{range_date_to:year}',date($_AS['config']['year'],$_AS['timestamp_rangeend']),$_AS['output']['list_body']);	
	} else {
		$_AS['output']['list_body'] = str_replace( '{range}','',$_AS['output']['list_body']);
	}
	
if (!empty($_AS['temp']['searchstring'])) {
	
	   $_AS['output']['list_body'] = str_replace( array(
		'{link_rangebackward}',
		'{link_rangeforward}',
		'{month_form}',
		'{year_form}'
		),
		array(
			'',
	    '',
	    '',
	    ''
		),
		$_AS['output']['list_body']);

}


		
	if ($_AS['temp']['is_prev']==true) {
		$_AS['temp']['url'] = as_url_creator( $con_side[$idcatside]['link'],

																					 array(	'idarticle' => (($mvars['72']=='list')?$_AS['idarticlemem']:''),
																									'idcatsideback' => (($mvars['72']=='list' && !empty($_AS['temp']['idcatsideback']))?$_AS['temp']['idcatsideback']:''),
																									'startmonth' => ($_AS['config']['startmonth']-$_AS['config_static']['monthback']),
																									'monthback' => $_AS['config_static']['monthback'],
																									'category' => $_AS['temp']['category'],
																									'cf1' => $_AS['temp']['customfilters2']['custom1'],
																									'cf2' => $_AS['temp']['customfilters2']['custom2'],
																									'cf3' => $_AS['temp']['customfilters2']['custom3'],
																									'cf4' => $_AS['temp']['customfilters2']['custom4'],
																									'cf5' => $_AS['temp']['customfilters2']['custom5'],
																									'cf6' => $_AS['temp']['customfilters2']['custom6'],
																									'cf7' => $_AS['temp']['customfilters2']['custom7'],
																									'cf8' => $_AS['temp']['customfilters2']['custom8'],
																									'cf9' => $_AS['temp']['customfilters2']['custom9'],
																									'cf10' => $_AS['temp']['customfilters2']['custom10'],
																									'sort' => $_AS['cms_wr']->getVal($_AS['modkey'].'sort')
																									)  
																					);
    $_AS['output']['list_body'] = str_replace( '{link_rangebackward}',
    																		str_replace('{url}',$_AS['temp']['url'],$mvars[14]),
																				$_AS['output']['list_body']);
	} else
		$_AS['output']['list_body'] = str_replace( '{link_rangebackward}','',$_AS['output']['list_body']);	


	if ($_AS['temp']['is_next']==true) {
		$_AS['temp']['url'] = as_url_creator( $con_side[$idcatside]['link'],
																					 array(	'idarticle' => (($mvars['72']=='list')?$_AS['idarticlemem']:''),
																									'idcatsideback' => (($mvars['72']=='list' && !empty($_AS['temp']['idcatsideback']))?$_AS['temp']['idcatsideback']:''),
																									'startmonth' => ($_AS['config']['startmonth']+$_AS['config_static']['monthback']),
																									'monthback' => $_AS['config_static']['monthback'],
																									'category' => $_AS['temp']['category'],
																									'cf1' => $_AS['temp']['customfilters2']['custom1'],
																									'cf2' => $_AS['temp']['customfilters2']['custom2'],
																									'cf3' => $_AS['temp']['customfilters2']['custom3'],
																									'cf4' => $_AS['temp']['customfilters2']['custom4'],
																									'cf5' => $_AS['temp']['customfilters2']['custom5'],
																									'cf6' => $_AS['temp']['customfilters2']['custom6'],
																									'cf7' => $_AS['temp']['customfilters2']['custom7'],
																									'cf8' => $_AS['temp']['customfilters2']['custom8'],
																									'cf9' => $_AS['temp']['customfilters2']['custom9'],
																									'cf10' => $_AS['temp']['customfilters2']['custom10'],
																									'sort' => $_AS['cms_wr']->getVal($_AS['modkey'].'sort')																									
																									) 
																					);
    $_AS['output']['list_body'] = str_replace( '{link_rangeforward}',
																							  str_replace('{url}',$_AS['temp']['url'],$mvars[13]),
																								$_AS['output']['list_body']);
	} else
		$_AS['output']['list_body'] = str_replace( '{link_rangeforward}','',$_AS['output']['list_body']);	




//
// additional body elements
//

	$_AS['output']['list_body'] = str_replace('{text_label}',$_AS['artsys_obj']->lang->get('article_text'),$_AS['output']['list_body']);
	$_AS['output']['list_body'] = str_replace('{teaser_label}',$_AS['artsys_obj']->lang->get('article_teaser'),$_AS['output']['list_body']);
	$_AS['output']['list_body'] = str_replace('{title_label}',$_AS['artsys_obj']->lang->get('article_title'),$_AS['output']['list_body']);
	$_AS['output']['list_body'] = str_replace('{category_label}',$_AS['artsys_obj']->lang->get('article_category'),$_AS['output']['list_body']);

	for ($i=1;$i<36;$i++)
		$_AS['output']['list_body'] = str_replace('{custom_label:'.$i.'}',$_AS['artsys_obj']->getSetting('article_custom'.$i.'_label'),$_AS['output']['list_body']);

//
// sort links
//

	$_AS['temp']['sortlinkvalsnew']=array();
	$_AS['temp']['sortlinkelements']=array();
	
	$_AS['temp']['sortlinkelements']['startdate_sortlink']='SDT';
	$_AS['temp']['sortlinkelements']['enddate_sortlink']='EDT';
	$_AS['temp']['sortlinkelements']['text_sortlink']='TXT';
	$_AS['temp']['sortlinkelements']['teaser_sortlink']='TSR';
	$_AS['temp']['sortlinkelements']['title_sortlink']='TTL';
	
	foreach($_AS['temp']['sortlinkelements'] as $v)
		$_AS['temp']['sortlinkvalsnew'][ $v]= $v.((substr($_AS['temp']['sortlinkvalsold'][$v],3,1)=='A')?'D':'A');
		
	for ($i=1;$i<36;$i++){
			if ($i<10)
				$_AS['temp']['sortlinkvalsnew']['CT'.$i]='CT'.$i.''.(($_AS['temp']['sortlinkvalsold']['CT'.$i]=='A')?'D':'A');
			else
				$_AS['temp']['sortlinkvalsnew']['CT0']='CT0'.(($_AS['temp']['sortlinkvalsold']['CT0']=='A')?'D':'A');
		}
	$_AS['temp']['sortlinkvalsmem']=implode(':',$_AS['temp']['sortlinkvalsnew']);

	foreach($_AS['temp']['sortlinkelements'] as $k => $v) {
		$_AS['output']['list_body'] = str_replace('{'.$k.'}',
																							'<a href="'.
																							as_url_creator( $con_side[$idcatside]['link'],
																															array('idarticle' => (($mvars['72']=='list')?$_AS['idarticlemem']:''),
																																		'idcatsideback' => (($mvars['72']=='list' && !empty($_AS['temp']['idcatsideback']))?$_AS['temp']['idcatsideback']:''),
																																		'monthback' => $_AS['temp']['monthback'],
																														 				'startmonth' => $_AS['temp']['startmonth'],
																														 				'searchstring' => $_AS['temp']['searchstring'],
																																		'category' => $_AS['temp']['category'] ,
																																		'cf1' => $_AS['temp']['customfilters2']['custom1'],
																																		'cf2' => $_AS['temp']['customfilters2']['custom2'],
																																		'cf3' => $_AS['temp']['customfilters2']['custom3'],
																																		'cf4' => $_AS['temp']['customfilters2']['custom4'],
																																		'cf5' => $_AS['temp']['customfilters2']['custom5'],
																																		'cf6' => $_AS['temp']['customfilters2']['custom6'],
																																		'cf7' => $_AS['temp']['customfilters2']['custom7'],
																																		'cf8' => $_AS['temp']['customfilters2']['custom8'],
																																		'cf9' => $_AS['temp']['customfilters2']['custom9'],
																																		'cf10' => $_AS['temp']['customfilters2']['custom10'],
																																		'sort' => $v.((substr($_AS['temp']['sortlinkvalsold'][$v],3,1)=='A')?'D':'A')
																																	)  ).
																							'" '.((substr($_AS['temp']['sortlinkvalsold'][$v],3,1)=='A')?$mvars[781]:$mvars[780]).'>',
																							$_AS['output']['list_body']);
		$_AS['output']['list_body'] = str_replace('{/'.$k.'}','</a>',$_AS['output']['list_body']);
	}

	for ($i=1;$i<36;$i++) {
		if ($i<10){
			$v='CT'.$i;
		} else {
			$v='CT0';
		}
		$k='custom_sortlink:'.$i;
		$_AS['output']['list_body'] = str_replace('{'.$k.'}',
																							'<a href="'.
																							as_url_creator( $con_side[$idcatside]['link'],
																															array('idarticle' => (($mvars['72']=='list')?$_AS['idarticlemem']:''),
																																		'idcatsideback' => (($mvars['72']=='list' && !empty($_AS['temp']['idcatsideback']))?$_AS['temp']['idcatsideback']:''),
																																		'monthback' => $_AS['temp']['monthback'],
																														 				'startmonth' => $_AS['temp']['startmonth'],
																														 				'searchstring' => $_AS['temp']['searchstring'],
																																		'category' => $_AS['temp']['category'] ,
																																		'cf1' => $_AS['temp']['customfilters2']['custom1'],
																																		'cf2' => $_AS['temp']['customfilters2']['custom2'],
																																		'cf3' => $_AS['temp']['customfilters2']['custom3'],
																																		'cf4' => $_AS['temp']['customfilters2']['custom4'],
																																		'cf5' => $_AS['temp']['customfilters2']['custom5'],
																																		'cf6' => $_AS['temp']['customfilters2']['custom6'],
																																		'cf7' => $_AS['temp']['customfilters2']['custom7'],
																																		'cf8' => $_AS['temp']['customfilters2']['custom8'],
																																		'cf9' => $_AS['temp']['customfilters2']['custom9'],
																																		'cf10' => $_AS['temp']['customfilters2']['custom10'],
																																		'sort' => $v.((substr($_AS['temp']['sortlinkvalsold'][$v],3,1)=='A')?'D':'A')																																		
																																	)  ).
																							'" '.((substr($_AS['temp']['sortlinkvalsold'][$v],3,1)=='A')?$mvars[781]:$mvars[780]).'>',
																							$_AS['output']['list_body']);
		$_AS['output']['list_body'] = str_replace('{/'.$k.'}','</a>',$_AS['output']['list_body']);
	}	



	//
	// custom select
	//
	for ($i=1;$i<36;$i++){
		if (strpos($_AS['output']['list_body'],'{customfilter_form:'.$i.'}')!==false &&
				$_AS['artsys_obj']->getSetting('article_custom'.$i.'_label')!="" && (
				$_AS['artsys_obj']->getSetting('article_custom'.$i.'_type')=="select" ||
				$_AS['artsys_obj']->getSetting('article_custom'.$i.'_type')=="select2" ||
				$_AS['artsys_obj']->getSetting('article_custom'.$i.'_type')=="radio" ||
				$_AS['artsys_obj']->getSetting('article_custom'.$i.'_type')=="check") ){
			$_AS['temp']['customselect']['html']='';
			// start & end
			$_AS['temp']['customselect']['html']['form_start']	= '<form method="post" name="'.
																															$_AS['modkey'].'customfilterform'.$i.'" action="'.
																															as_url_creator( $con_side[(!empty($mvars[108023])?$mvars[108023]:$idcatside)]['link'],
																																							array('idarticle' => (($mvars['72']=='list')?$_AS['idarticlemem']:''),
																																										'idcatsideback' => (($mvars['72']=='list' && !empty($_AS['temp']['idcatsideback']))?$_AS['temp']['idcatsideback']:''),
																																										'monthback' => $_AS['temp']['monthback'],
																																						 				'startmonth' => $_AS['temp']['startmonth'],
																																						 				'searchstring' => $_AS['temp']['searchstring'],
																																										'category' => $_AS['temp']['category'] ,
																																										'cf1' => $_AS['temp']['customfilters2']['custom1'],
																																										'cf2' => $_AS['temp']['customfilters2']['custom2'],
																																										'cf3' => $_AS['temp']['customfilters2']['custom3'],
																																										'cf4' => $_AS['temp']['customfilters2']['custom4'],
																																										'cf5' => $_AS['temp']['customfilters2']['custom5'],
																																										'cf6' => $_AS['temp']['customfilters2']['custom6'],
																																										'cf7' => $_AS['temp']['customfilters2']['custom7'],
																																										'cf8' => $_AS['temp']['customfilters2']['custom8'],
																																										'cf9' => $_AS['temp']['customfilters2']['custom9'],
																																										'cf10' => $_AS['temp']['customfilters2']['custom10']																																										
																																									)  ).																											
																															'">';
			$_AS['temp']['customselect']['html']['form_end']		= '</form>';
			
			// select
			$_AS['temp']['customselect']['html']['form_select'][] =	'<select name="'.$_AS['modkey'].'cf'.$i.'" id="'.$cms_mod['key'].'customfilter'.$i.'" size="1" '.str_replace('{form_name}',$_AS['modkey'].'customfilterform'.$i,$mvars[103023]).'>';


			$_AS['temp']['customdataarray']=array();
    	$_AS['temp']['customdatastatic']=trim($_AS['artsys_obj']->getSetting('article_custom'.$i.'_select_values'),true);

    	if (!empty($_AS['temp']['customdatastatic'])) {
	    	$_AS['temp']['customarray']=explode("\n",$_AS['temp']['customdatastatic']);

				// fill option-array with static vals
    		foreach ($_AS['temp']['customarray'] as $v) {
    			$v=trim(stripslashes(str_replace('&amp;','&',$v)));
					//$v=htmlentities($v,ENT_COMPAT,'UTF-8');
					$v=htmlentities($v,ENT_COMPAT,'UTF-8');
					if (!empty($v)) {
		  			$va=explode('||',$v);
		  			$_AS['temp']['customdataarray'][trim($va[0]).' ']=empty($va[1])?trim($va[0]):trim($va[1]);
					}
    		}
    	}

			if (!empty($mvars[2000])) {
				unset($sql_cf_addon);
				for ($ii=1;$ii<36;$ii++){
				if (!empty($_AS['temp']['customfilters']['custom'.$ii]))
					$sql_cf_addon.=" AND custom".$ii."='".$_AS['temp']['customfilters']['custom'.$ii]."'";
				}
			}


			if ($_AS['artsys_obj']->getSetting('article_custom'.$i.'_type')=="select2") {
			
			
				$_AS['temp']['customdataarray2']=array();
				// fill option-array with vals from articles
				$sql = "SELECT custom".$i." FROM ".$cfg_cms['db_table_prefix']."plug_articlesystem 
								WHERE online=1 AND idclient=".$client." AND idlang=".$lang.$sql_cf_addon;
	   	
	
		    $db->query($sql);
				while($db -> next_record()) {
					$v=trim($db->f('custom'.$i));
					$_AS['temp']['customdataarray2'][htmlentities($v,ENT_COMPAT,'UTF-8').' ']=$v;
				}			
		    $_AS['temp']['customdataarray2']=array_filter($_AS['temp']['customdataarray2']);
	
	
				$_AS['temp']['customdataarray3']=array();
				foreach (array_merge($_AS['temp']['customdataarray'],$_AS['temp']['customdataarray2']) as $k1 => $v1){
						if (strpos($v1,'%%')!==false && count($_AS['temp']['sf_arr'])>0){	
		    			if (substr($v1,0,2)=='%%' && substr($v1,-2,2)=='%%'){
		    				$v1=str_replace('%%'.substr($v1,2,strlen($v1)-4).'%%',$_AS['temp']['sf_arr'][substr($v1,2,strlen($v1)-4)],$v1);
	    				}
	    			}
	    			
					$_AS['temp']['customdataarray3'][str_replace('%%','||',$k1)]=$v1;
				}
	
				$_AS['temp']['customdataarray']=array();
				$_AS['temp']['customdataarray']=array_unique($_AS['temp']['customdataarray3']);
				
			}
			
			$_AS['temp']['customselect']['html']['form_select'][]	=	'<option value="" '.
																																((htmlentities(stripslashes($_AS['temp']['customfilterselected'.$i]), ENT_COMPAT, 'UTF-8') == $k || stripslashes($_AS['temp']['customfilterselected'.$i]) == $k ) ? 'selected="selected"':'').
																																'>'.$mvars[105019].'</option>'."\n";
																						
			
#    	natcasesort($_AS['temp']['customdataarray']);

			if (is_array($_AS['temp']['customdataarray']))
				foreach ( $_AS['temp']['customdataarray'] as $k => $v){

					if (strpos($_AS['temp']['customfilterselected'.$i],'%%')!==false){	
						if (substr($_AS['temp']['customfilterselected'.$i],0,2)=='%%' && substr($_AS['temp']['customfilterselected'.$i],-2,2)=='%%'){
							$_AS['temp']['customfilterselected'.$i]='||'.substr($_AS['temp']['customfilterselected'.$i],2,strlen($_AS['temp']['customfilterselected'.$i])-4).'||';
						}
					}				
					$k=trim($k);
					$_AS['temp']['customselect']['html']['form_select'][]	=	'<option value="'. $k .'" '.
																																		((htmlentities(stripslashes($_AS['temp']['customfilterselected'.$i]), ENT_COMPAT, 'UTF-8') == $k || stripslashes($_AS['temp']['customfilterselected'.$i]) == $k ) ? 'selected="selected"':'').
																																		'>'.
																																		htmlentities($v, ENT_COMPAT, 'UTF-8').
																																		'</option>'."\n";
			}
			$_AS['temp']['customselect']['html']['form_select'][] = '</select>';

			// label
		  $_AS['temp']['customselect']['html']['form_label'] = '<label for="'.$cms_mod['key'].'customfilter'.$i.'" '.str_replace('{form_name}',$_AS['modkey'].'customfilterform'.$i,$mvars[104023]).'>'.str_replace('{label}',$_AS['artsys_obj']->getSetting('article_custom'.$i.'_label',true),$mvars[101023]).'</label>';
		
			// submit
		  $_AS['temp']['customselect']['html']['form_submit'] = '<input type="submit" value="'.$mvars[105023].'" '.str_replace('{form_name}',$_AS['modkey'].'customfilterform'.$i,$mvars[107023]).' />';
	
			// complete html
			$_AS['temp']['customselect']['html']['complete'] = $_AS['temp']['customselect']['html']['form_start'].
																										  	str_replace(
																										  			array(
																										          '{custom_select}',
																										          '{custom_label}',
																										          '{custom_submit}'
																										        ),
																										        array(
																										          "\n".implode("\n",$_AS['temp']['customselect']['html']['form_select']),
																										          $_AS['temp']['customselect']['html']['form_label'],
																										          $_AS['temp']['customselect']['html']['form_submit']."\n"
																										        ),
																										        $mvars[23]).
																									        $_AS['temp']['customselect']['html']['form_end'];
	
		  $_AS['output']['list_body'] = str_replace('{customfilter_form:'.$i.'}',$_AS['temp']['customselect']['html']['complete'],$_AS['output']['list_body']);
	
		} else {
			$_AS['output']['list_body'] = str_replace('{customfilter_form:'.$i.'}','',$_AS['output']['list_body']);
		
		}
	}
		
		

	//
	// month / year select
	//
	
	if (strpos($_AS['output']['list_body'],'{month_form}')!==false || strpos($_AS['output']['list_body'],'{year_form}')!==false){


		// month select preparations
		$_AS['collection']->setDateRange( mktime(	0,0,0,0,0,1971 ),	mktime( date('H'),date('i'),date('s'),date('m'),date('d'),date('Y') ) );

		$_AS['collection']->setLimit();
	  $_AS['collection']->generate();

		$_AS['temp']['mselect']['avail']=array();
    for($iter = $_AS['collection']->get(); $iter->valid(); $iter->next() ) {
      $_AS['item'] =& $iter->current();
#      if($_AS['item']->available())  {
					$_AS['temp']['mselect']['avail'][date('Y',  $_AS['item']->convDate2Timestamp(0,'article_startdate'))][ (int) date('m',  $_AS['item']->convDate2Timestamp(0,'article_startdate'))]= (int) date('m',  $_AS['item']->convDate2Timestamp(0,'article_startdate'));
#        }
    }

		// start & end
		$_AS['temp']['monthselect']['html']['form_start']	= '<form method="post" name="'.
																												$_AS['modkey'].'month" action="'.
																												as_url_creator( $con_side[(!empty($mvars[108020])?$mvars[108020]:$idcatside)]['link'],
																																			  array('idarticle' => (($mvars['72']=='list')?$_AS['idarticlemem']:''),
																																							'idcatsideback' => (($mvars['72']=='list' && !empty($_AS['temp']['idcatsideback']))?$_AS['temp']['idcatsideback']:''),
																																							'monthback' => 1,
																																							'category' => $_AS['temp']['category'] ,
																																							'cf1' => $_AS['temp']['customfilters2']['custom1'],
																																							'cf2' => $_AS['temp']['customfilters2']['custom2'],
																																							'cf3' => $_AS['temp']['customfilters2']['custom3'],
																																							'cf4' => $_AS['temp']['customfilters2']['custom4'],
																																							'cf5' => $_AS['temp']['customfilters2']['custom5'],
																																							'cf6' => $_AS['temp']['customfilters2']['custom6'],
																																							'cf7' => $_AS['temp']['customfilters2']['custom7'],
																																							'cf8' => $_AS['temp']['customfilters2']['custom8'],
																																							'cf9' => $_AS['temp']['customfilters2']['custom9'],
																																							'cf10' => $_AS['temp']['customfilters2']['custom10'],
																																							'sort' => $_AS['cms_wr']->getVal($_AS['modkey'].'sort')																																							
																																						)  ).
																												'">';
		$_AS['temp']['monthselect']['html']['form_end']		= '</form>';
		
		// start & end
		$_AS['temp']['yearselect']['html']['form_start']	= '<form method="post" name="'.
																												$_AS['modkey'].'year" action="'.
																												as_url_creator( $con_side[(!empty($mvars[108022])?$mvars[108022]:$idcatside)]['link'],
																																			  array('idarticle' => (($mvars['72']=='list')?$_AS['idarticlemem']:''),
																																							'idcatsideback' => (($mvars['72']=='list' && !empty($_AS['temp']['idcatsideback']))?$_AS['temp']['idcatsideback']:''),
																																							'monthback' => 1,
																																							'category' => $_AS['temp']['category'] ,
																																							'cf1' => $_AS['temp']['customfilters2']['custom1'],
																																							'cf2' => $_AS['temp']['customfilters2']['custom2'],
																																							'cf3' => $_AS['temp']['customfilters2']['custom3'],
																																							'cf4' => $_AS['temp']['customfilters2']['custom4'],
																																							'cf5' => $_AS['temp']['customfilters2']['custom5'],
																																							'cf6' => $_AS['temp']['customfilters2']['custom6'],
																																							'cf7' => $_AS['temp']['customfilters2']['custom7'],
																																							'cf8' => $_AS['temp']['customfilters2']['custom8'],
																																							'cf9' => $_AS['temp']['customfilters2']['custom9'],
																																							'cf10' => $_AS['temp']['customfilters2']['custom10'] ,
																																							'sort' => $_AS['cms_wr']->getVal($_AS['modkey'].'sort')																																							
																																						)  ).
																												'">';
		$_AS['temp']['yearselect']['html']['form_end']		= '</form>';



		// select
			$_AS['temp']['yearselect']['html']['form_select'][] ='<select name="'.$_AS['modkey'].'startmonth2" id="'.$cms_mod['key'].'startmonth2" size="1" '.str_replace('{form_name}',$_AS['modkey'].'year',$mvars[103022]).'>';
	
			// select
			$_AS['temp']['monthselect']['html']['form_select'][] ='<select name="'.$_AS['modkey'].'startmonth" id="'.$cms_mod['key'].'startmonth" size="1" '.str_replace('{form_name}',$_AS['modkey'].'month',$mvars[103020]).'>';
	

			foreach( $_AS['temp']['mselect']['avail'] as $y => $y_arr)
//			if (array_key_exists(date('Y',  mktime(0,0,0,$_AS['temp']['startmonth']+1)),$_AS['temp']['mselect']['avail']))
				foreach( $y_arr as $m) {
						$_AS['temp']['yearselect']['html']['form_select'][$y] =	'<option value="'.
																																			(in_array(date('m',  mktime(0,0,0,$_AS['temp']['startmonth']+1)),$y_arr)?
																																			((-(date('Y')-$y)*12)+date('m',  mktime(0,0,0,$_AS['temp']['startmonth']+1))-1):
																																			((-(date('Y')-$y)*12)+$m)-1).'" '.
																																			((-floor($_AS['config']['startmonth']/12) == date('Y')-$y ) ? 'selected="selected"':'').
																																			'>'.$y.'</option>';
	
				if (date('Y',  mktime(0,0,0,$_AS['temp']['startmonth']+1))==$y ||
						(date('Y',  mktime(0,0,0,$_AS['temp']['startmonth']+1))!=$y && $y==date('Y') &&
						 !array_key_exists(date('Y', mktime(0,0,0,$_AS['temp']['startmonth']+1)),$_AS['temp']['mselect']['avail'])) ) {
				if (empty($mvars[80]))
					$_AS['temp']['monthselect']['html']['form_select_from']=$_AS['artsys_obj']->lang->get('month_'.((($m-1<1)?12:$m-1))).' - ';
				else
					$_AS['temp']['monthselect']['html']['form_select_from']='';
	
					$_AS['temp']['monthselect']['html']['form_select'][] =	'<option value="'.
																																	((-(date('Y')-$y)*12)+$m-1).'" '.
																																	(($_AS['temp']['startmonth'] == ((-(date('Y')-$y)*12)+$m-1) || (empty($_AS['temp']['startmonth']) && $_AS['config']['startmonth']==((-(date('Y')-$y)*12)+$m-1))) ? 'selected="selected"':'').
																																	'>'.
																																	htmlentities(	$_AS['temp']['monthselect']['html']['form_select_from'].$_AS['artsys_obj']->lang->get('month_'.($m)).' ', ENT_COMPAT, 'UTF-8').
																																	'</option>';
				}
			}

			$_AS['temp']['yearselect']['html']['form_select'][] = '</select>';
			// label
		  $_AS['temp']['yearselect']['html']['form_label'] = '<label for="'.$cms_mod['key'].'startmonth2" '.str_replace('{form_name}',$_AS['modkey'].'year',$mvars[104022]).'>'.$mvars[101022].'</label>';
		
			// submit
		  $_AS['temp']['yearselect']['html']['form_submit'] = '<input type="submit" value="'.$mvars[105022].'" '.str_replace('{form_name}',$_AS['modkey'].'year',$mvars[107022]).' />';
	
			// complete html
			$_AS['temp']['yearselect']['html']['complete'] = $_AS['temp']['yearselect']['html']['form_start'].
																										  	str_replace(
																										  			array(
																										  				'{year_select}',
																										          '{year_label}',
																										          '{year_submit}'
																										        ),
																										        array(
																										        	"\n".implode("\n",$_AS['temp']['yearselect']['html']['form_select']),
																										          $_AS['temp']['yearselect']['html']['form_label'],
																										          $_AS['temp']['yearselect']['html']['form_submit']."\n"
																										        ),
																										        $mvars[22]).
																									        $_AS['temp']['yearselect']['html']['form_end'];

			if (count($_AS['temp']['yearselect']['html']['form_select'])>2)
		  	$_AS['output']['list_body'] = str_replace('{year_form}',$_AS['temp']['yearselect']['html']['complete'],$_AS['output']['list_body']);
			else
				$_AS['output']['list_body'] = str_replace('{year_form}','',$_AS['output']['list_body']);




			$_AS['temp']['monthselect']['html']['form_select'][] = '</select>';

			// label
		  $_AS['temp']['monthselect']['html']['form_label'] = '<label for="'.$cms_mod['key'].'startmonth" '.str_replace('{form_name}',$_AS['modkey'].'month',$mvars[104020]).'>'.$mvars[101020].'</label>';
		
			// submit
		  $_AS['temp']['monthselect']['html']['form_submit'] = '<input type="submit" value="'.$mvars[105020].'" '.str_replace('{form_name}',$_AS['modkey'].'month',$mvars[107020]).' />';
	
			// complete html
			$_AS['temp']['monthselect']['html']['complete'] = $_AS['temp']['monthselect']['html']['form_start'].
																										  	str_replace(
																										  			array(
																										          '{month_select}',
																										          '{month_label}',
																										          '{month_submit}'
																										        ),
																										        array(
																										          "\n".implode("\n",$_AS['temp']['monthselect']['html']['form_select']),
																										          $_AS['temp']['monthselect']['html']['form_label'],
																										          $_AS['temp']['monthselect']['html']['form_submit']."\n"
																										        ),
																										        $mvars[20]).
																									        $_AS['temp']['monthselect']['html']['form_end'];

			if (count($_AS['temp']['monthselect']['html']['form_select'])>2)
		 		$_AS['output']['list_body'] = str_replace('{month_form}',$_AS['temp']['monthselect']['html']['complete'],$_AS['output']['list_body']);
			else
				$_AS['output']['list_body'] = str_replace('{month_form}','',$_AS['output']['list_body']);
	}
	//
	// category select
	//
	if (strpos($_AS['output']['list_body'],'category_form}')!==false){

		// start & end
		$_AS['temp']['categoryselect']['html']['form_start']	= '<form method="post" name="'.
																														$_AS['modkey'].'category" action="'.
																														as_url_creator( $con_side[(!empty($mvars[108019])?$mvars[108019]:$idcatside)]['link'],
																																						array('idarticle' => (($mvars['72']=='list')?$_AS['idarticlemem']:''),
																																									'idcatsideback' => (($mvars['72']=='list' && !empty($_AS['temp']['idcatsideback']))?$_AS['temp']['idcatsideback']:''),
																																									'monthback' => $_AS['temp']['monthback'],
																																					 				'startmonth' => $_AS['temp']['startmonth'],
																																					 				'searchstring' => $_AS['temp']['searchstring'],
																																									'category' => $_AS['temp']['category'] ,
																																									'cf1' => $_AS['temp']['customfilters2']['custom1'],
																																									'cf2' => $_AS['temp']['customfilters2']['custom2'],
																																									'cf3' => $_AS['temp']['customfilters2']['custom3'],
																																									'cf4' => $_AS['temp']['customfilters2']['custom4'],
																																									'cf5' => $_AS['temp']['customfilters2']['custom5'],
																																									'cf6' => $_AS['temp']['customfilters2']['custom6'],
																																									'cf7' => $_AS['temp']['customfilters2']['custom7'],
																																									'cf8' => $_AS['temp']['customfilters2']['custom8'],
																																									'cf9' => $_AS['temp']['customfilters2']['custom9'],
																																									'cf10' => $_AS['temp']['customfilters2']['custom10'],
																																									'sort' => $_AS['cms_wr']->getVal($_AS['modkey'].'sort')																																									
																																								)  ).																											
																														'">';
		$_AS['temp']['categoryselect']['html']['form_end']		= '</form>';
		
		// select
		$_AS['temp']['categoryselect']['html']['form_select'][] =	'<select name="'.$_AS['modkey'].'category" id="'.$cms_mod['key'].'category" size="1" '.str_replace('{form_name}',$_AS['modkey'].'category',$mvars[103019]).'>';

		$_AS['temp']['setcategoryselect']['html']['form_select'] = $_AS['temp']['categoryselect']['html']['form_select'];

		$_AS['temp']['categoryselect']['html']['form_select'][] =	'<option value="0" '.
																															((empty($_AS['temp']['category'])) ? 'selected="selected"':'').
																															'>'.
																															$mvars[102019].
																															'</option>';

		$_AS['temp']['setcategoryselect']['html']['form_select'][] =	'<option value="-1" '.
																															((empty($_AS['temp']['category'])) ? 'selected="selected"':'').
																															'>'.
																															$mvars[101019].
																															'</option>';

		$_AS['temp']['setcategoryselect']['html']['form_select'][] =	'<option value="0" '.
																															((empty($_AS['temp']['category'])) ? 'selected="selected"':'').
																															'>'.
																															$mvars[102019].
																															'</option>';

																												
		if (is_array($_AS['temp']['categories']))
			foreach ( $_AS['temp']['categories'] as $k => $v) {
				$_AS['temp']['categoryselect']['html']['form_select'][]	=	'<option value="'. $k .'" '.
																																	(($_AS['temp']['category'] == $k ) ? 'selected="selected"':'').
																																	'>'.
																																	htmlentities($v, ENT_COMPAT, 'UTF-8').
																																	'</option>'."\n";
				$_AS['temp']['setcategoryselect']['html']['form_select'][]	=	'<option value="'. $k .'" '.
																																			(($_AS['temp']['category_mem'] == $k ) ? 'selected="selected"':'').
																																			'>'.
																																			htmlentities($v, ENT_COMPAT, 'UTF-8').
																																			'</option>'."\n";
		}
		
		$_AS['temp']['categoryselect']['html']['form_select'][] = '</select>';
		$_AS['temp']['setcategoryselect']['html']['form_select'][] = '</select>';
		
		// label
	  $_AS['temp']['categoryselect']['html']['form_label'] = '<label for="'.$cms_mod['key'].'category" '.str_replace('{form_name}',$_AS['modkey'].'category',$mvars[104019]).'>'.$mvars[101019].'</label>';
	
		// submit
	  $_AS['temp']['categoryselect']['html']['form_submit'] = '<input type="submit" value="'.$mvars[105019].'" '.str_replace('{form_name}',$_AS['modkey'].'category',$mvars[107019]).' />';

		// complete html
		$_AS['temp']['categoryselect']['html']['complete'] = $_AS['temp']['categoryselect']['html']['form_start'].
																									  	str_replace(
																									  			array(
																									          '{category_select}',
																									          '{category_label}',
																									          '{category_submit}'
																									        ),
																									        array(
																									          "\n".implode("\n",$_AS['temp']['categoryselect']['html']['form_select']),
																									          $_AS['temp']['categoryselect']['html']['form_label'],
																									          $_AS['temp']['categoryselect']['html']['form_submit']."\n"
																									        ),
																									        $mvars[19]).
																								        $_AS['temp']['categoryselect']['html']['form_end'];

		// complete html
		$_AS['temp']['setcategoryselect']['html']['complete'] = $_AS['temp']['categoryselect']['html']['form_start'].
																									  	str_replace(
																									  			array(
																									          '{category_select}',
																									          '{category_label}',
																									          '{category_submit}'
																									        ),
																									        array(
																									          "\n".implode("\n",$_AS['temp']['setcategoryselect']['html']['form_select']),
																									          $_AS['temp']['categoryselect']['html']['form_label'],
																									          $_AS['temp']['categoryselect']['html']['form_submit']."\n"
																									        ),
																									        $mvars[19]).
																								        $_AS['temp']['categoryselect']['html']['form_end'];

	  $_AS['output']['list_body'] = str_replace('{category_form}',$_AS['temp']['categoryselect']['html']['complete'],$_AS['output']['list_body']);
		if (as_backendmode()) {
		  $_AS['output']['list_body'] = str_replace('{set_category_form}',
		  																					str_replace($_AS['modkey'].'category',
		  																											$_AS['modkey'].'setcategory',
		  																											$_AS['temp']['setcategoryselect']['html']['complete']),
		  																					$_AS['output']['list_body']);
		}	else
			$_AS['output']['list_body'] = str_replace('{set_category_form}','',$_AS['output']['list_body']);
	}

	
 
	// 	
	// category links
	// 
	if (strpos($_AS['output']['list_body'],'{category_links}')!==false){
		$_AS['temp']['categorylinks']['html']='';
		if (is_array($_AS['temp']['categories']))
		
			$_AS['temp']['categorylinks']['html']	.=	str_replace(
																								  			array(
																								          '{url}',
																								          '{url_addon}',
																								          '{name}'
																								        ),

																								        array(
																														as_url_creator( $con_side[$idcatside]['link'], 
																																						array(	'idarticle' => (($mvars['72']=='list')?$_AS['idarticlemem']:''),
																																										'idcatsideback' => (($mvars['72']=='list' && !empty($_AS['temp']['idcatsideback']))?$_AS['temp']['idcatsideback']:''),
																																										'startmonth' => $_AS['config']['startmonth'],
																																										'monthback' => $_AS['config']['monthback'],
																																										'searchstring' => $_AS['temp']['searchstring'] ,
																																										'cf1' => $_AS['temp']['customfilters2']['custom1'],
																																										'cf2' => $_AS['temp']['customfilters2']['custom2'],
																																										'cf3' => $_AS['temp']['customfilters2']['custom3'],
																																										'cf4' => $_AS['temp']['customfilters2']['custom4'],
																																										'cf5' => $_AS['temp']['customfilters2']['custom5'],
																																										'cf6' => $_AS['temp']['customfilters2']['custom6'],
																																										'cf7' => $_AS['temp']['customfilters2']['custom7'],
																																										'cf8' => $_AS['temp']['customfilters2']['custom8'],
																																										'cf9' => $_AS['temp']['customfilters2']['custom9'],
																																										'cf10' => $_AS['temp']['customfilters2']['custom10'],
																																										'sort' => $_AS['cms_wr']->getVal($_AS['modkey'].'sort')																																										
																																									)  
																																					),
																														as_url_creator( '', 
																																						array(	'idarticle' => (($mvars['72']=='list')?$_AS['idarticlemem']:''),
																																										'idcatsideback' => (($mvars['72']=='list' && !empty($_AS['temp']['idcatsideback']))?$_AS['temp']['idcatsideback']:''),
																																										'startmonth' => $_AS['config']['startmonth'],
																																										'monthback' => $_AS['config']['monthback'],
																																										'searchstring' => $_AS['temp']['searchstring'] ,
																																										'cf1' => $_AS['temp']['customfilters2']['custom1'],
																																										'cf2' => $_AS['temp']['customfilters2']['custom2'],
																																										'cf3' => $_AS['temp']['customfilters2']['custom3'],
																																										'cf4' => $_AS['temp']['customfilters2']['custom4'],
																																										'cf5' => $_AS['temp']['customfilters2']['custom5'],
																																										'cf6' => $_AS['temp']['customfilters2']['custom6'],
																																										'cf7' => $_AS['temp']['customfilters2']['custom7'],
																																										'cf8' => $_AS['temp']['customfilters2']['custom8'],
																																										'cf9' => $_AS['temp']['customfilters2']['custom9'],
																																										'cf10' => $_AS['temp']['customfilters2']['custom10'],
																																										'sort' => $_AS['cms_wr']->getVal($_AS['modkey'].'sort')
																																									)  
																																					),
																								          $mvars[102019]
																								        ),
																								        $_AS['temp']['cat_links']);

			foreach ( $_AS['temp']['categories'] as $k => $v)
				$_AS['temp']['categorylinks']['html']	.=	str_replace(
																									  			array(
																									          '{url}',
																									          '{url_addon}',
																									          '{name}'
																									        ),

																									        array(
																															as_url_creator( $con_side[(!empty($mvars[108019])?$mvars[108019]:$idcatside)]['link'], 
																																							array(	'idarticle' => (($mvars['72']=='list')?$_AS['idarticlemem']:''),
																																											'idcatsideback' => (($mvars['72']=='list' && !empty($_AS['temp']['idcatsideback']))?$_AS['temp']['idcatsideback']:''),
																																											'startmonth' => $_AS['config']['startmonth'],
																																											'monthback' => $_AS['config']['monthback'],
																																											'category' => $k,
																																											'searchstring' => $_AS['temp']['searchstring'] ,
																																											'cf1' => $_AS['temp']['customfilters2']['custom1'],
																																											'cf2' => $_AS['temp']['customfilters2']['custom2'],
																																											'cf3' => $_AS['temp']['customfilters2']['custom3'],
																																											'cf4' => $_AS['temp']['customfilters2']['custom4'],
																																											'cf5' => $_AS['temp']['customfilters2']['custom5'],
																																											'cf6' => $_AS['temp']['customfilters2']['custom6'],
																																											'cf7' => $_AS['temp']['customfilters2']['custom7'],
																																											'cf8' => $_AS['temp']['customfilters2']['custom8'],
																																											'cf9' => $_AS['temp']['customfilters2']['custom9'],
																																											'cf10' => $_AS['temp']['customfilters2']['custom10'],
																																											'sort' => $_AS['cms_wr']->getVal($_AS['modkey'].'sort')
																																										)  
																																						),
																															as_url_creator( '', 
																																							array(	'idarticle' => (($mvars['72']=='list')?$_AS['idarticlemem']:''),
																																											'idcatsideback' => (($mvars['72']=='list' && !empty($_AS['temp']['idcatsideback']))?$_AS['temp']['idcatsideback']:''),
																																											'startmonth' => $_AS['config']['startmonth'],
																																											'monthback' => $_AS['config']['monthback'],
																																											'category' => $k,
																																											'searchstring' => $_AS['temp']['searchstring'] ,
																																											'cf1' => $_AS['temp']['customfilters2']['custom1'],
																																											'cf2' => $_AS['temp']['customfilters2']['custom2'],
																																											'cf3' => $_AS['temp']['customfilters2']['custom3'],
																																											'cf4' => $_AS['temp']['customfilters2']['custom4'],
																																											'cf5' => $_AS['temp']['customfilters2']['custom5'],
																																											'cf6' => $_AS['temp']['customfilters2']['custom6'],
																																											'cf7' => $_AS['temp']['customfilters2']['custom7'],
																																											'cf8' => $_AS['temp']['customfilters2']['custom8'],
																																											'cf9' => $_AS['temp']['customfilters2']['custom9'],
																																											'cf10' => $_AS['temp']['customfilters2']['custom10'],
																																											'sort' => $_AS['cms_wr']->getVal($_AS['modkey'].'sort')
																																										)  
																																						),
																									          htmlentities($v, ENT_COMPAT, 'UTF-8')
																									        ),
																									        $_AS['temp']['cat_links']);
				
	  $_AS['output']['list_body'] = str_replace('{category_links}',$_AS['temp']['categorylinks']['html'],$_AS['output']['list_body']);

	
	}
	// 
	// search form
	// 
	if (strpos($_AS['output']['list_body'],'{search_form}')!==false){

		// start & end
		$_AS['temp']['search']['html']['form_start']	= '<form name="'.
																										$_AS['modkey'].'search" method="post" action="'.
																										as_url_creator( $con_side[(!empty($mvars[108])?$mvars[108]:$idcatside)]['link'],
																																		array('category' => $_AS['temp']['category'] ,
																																					'cf1' => $_AS['temp']['customfilters2']['custom1'],
																																					'cf2' => $_AS['temp']['customfilters2']['custom2'],
																																					'cf3' => $_AS['temp']['customfilters2']['custom3'],
																																					'cf4' => $_AS['temp']['customfilters2']['custom4'],
																																					'cf5' => $_AS['temp']['customfilters2']['custom5'],
																																					'cf6' => $_AS['temp']['customfilters2']['custom6'],
																																					'cf7' => $_AS['temp']['customfilters2']['custom7'],
																																					'cf8' => $_AS['temp']['customfilters2']['custom8'],
																																					'cf9' => $_AS['temp']['customfilters2']['custom9'],
																																					'cf10' => $_AS['temp']['customfilters2']['custom10'],
																																					'sort' => $_AS['cms_wr']->getVal($_AS['modkey'].'sort'),
																																					'idarticle' => (($mvars['72']=='list')?$_AS['idarticlemem']:''),
																																					'idcatsideback' => (($mvars['72']=='list' && !empty($_AS['temp']['idcatsideback']))?$_AS['temp']['idcatsideback']:'')
																																				)  ).			
																										'">';
		$_AS['temp']['search']['html']['form_end']		= '</form>';
		
		// select
		$_AS['temp']['search']['html']['form_input']  = '<input type="text" id="'.$cms_mod['key'].'searchstring" name="'.$_AS['modkey'].'searchstring" value="'.$_AS['temp']['searchstring'].'" '.$mvars[103].' />';

		// label
	  $_AS['temp']['search']['html']['form_label'] = '<label for="'.$cms_mod['key'].'searchstring" '.str_replace('{form_name}',$_AS['modkey'].'month',$mvars[104]).'>'.$mvars[101].'</label>';
	
		// submit
	  $_AS['temp']['search']['html']['form_submit'] = '<input type="submit" value="'.$mvars[100].'" '.str_replace('{form_name}',$_AS['modkey'].'month',$mvars[107]).' />';

		// complete html
		$_AS['temp']['search']['html']['complete'] = $_AS['temp']['search']['html']['form_start'].
																									  	str_replace(
																									  			array(
																									          '{search_input}',
																									          '{search_label}',
																									          '{search_submit}'
																									        ),
																									        array(
																									          "\n".$_AS['temp']['search']['html']['form_input'],
																									          $_AS['temp']['search']['html']['form_label'],
																									          $_AS['temp']['search']['html']['form_submit']."\n"
																									        ),
																									        $mvars[18]).
																								        $_AS['temp']['search']['html']['form_end'];

	  $_AS['output']['list_body'] = str_replace('{search_form}',$_AS['temp']['search']['html']['complete'],$_AS['output']['list_body']);
	
	}

	//
	// article select
	//
	if (strpos($_AS['output']['list_body'],'article_form}')!==false){

		// start & end
		$_AS['temp']['articleselect']['html']['form_start']	= '<form method="post" name="'.
																														$_AS['modkey'].'article" action="'.
																														as_url_creator( $con_side[$idcatside]['link'],
																																						array('idarticle' => (($mvars['72']=='list')?$_AS['idarticlemem']:''),
																																									'idcatsideback' => (($mvars['72']=='list' && !empty($_AS['temp']['idcatsideback']))?$_AS['temp']['idcatsideback']:''),
																																									'monthback' => $_AS['temp']['monthback'],
																																					 				'startmonth' => $_AS['temp']['startmonth'],
																																					 				'searchstring' => $_AS['temp']['searchstring'],
																																									'category' => $_AS['temp']['category'] ,
																																									'cf1' => $_AS['temp']['customfilters2']['custom1'],
																																									'cf2' => $_AS['temp']['customfilters2']['custom2'],
																																									'cf3' => $_AS['temp']['customfilters2']['custom3'],
																																									'cf4' => $_AS['temp']['customfilters2']['custom4'],
																																									'cf5' => $_AS['temp']['customfilters2']['custom5'],
																																									'cf6' => $_AS['temp']['customfilters2']['custom6'],
																																									'cf7' => $_AS['temp']['customfilters2']['custom7'],
																																									'cf8' => $_AS['temp']['customfilters2']['custom8'],
																																									'cf9' => $_AS['temp']['customfilters2']['custom9'],
																																									'cf10' => $_AS['temp']['customfilters2']['custom10'],
																																									'sort' => $_AS['cms_wr']->getVal($_AS['modkey'].'sort')																																									
																																								)  ).																											
																														'">';
		$_AS['temp']['articleselect']['html']['form_end']		= '</form>';
		
		// select
		$_AS['temp']['articleselect']['html']['form_select'][] =	'<select name="'.$_AS['modkey'].'article" id="'.$cms_mod['key'].'article" size="1" '.str_replace('{form_name}',$_AS['modkey'].'article',$mvars[103024]).'>';

		$_AS['temp']['setarticleselect']['html']['form_select'] = $_AS['temp']['articleselect']['html']['form_select'];


		$_AS['temp']['setarticleselect']['html']['form_select'][] =	'<option value="-1" '.
																															((empty($_AS['temp']['article'])) ? 'selected="selected"':'').
																															'>'.
																															$mvars[101024].
																															'</option>';


		$_AS['temp']['articleselect']['html']['form_select'][] =	'<option value="-1" '.
																															((empty($_AS['temp']['article'])) ? 'selected="selected"':'').
																															'>'.
																															$mvars[101024].
																															'</option>';


  $_AS['collection'] = new ArticleCollection();
	$_AS['elements'] = new ArticleElements;
	
	$_AS['collection']->setLegal(mktime(date('H'),date('i'),0,date('m'),date('d'),date('Y')));
		
	// set category
	if($_AS['temp']['category']) 
  		$_AS['collection']->setIdcategory($_AS['temp']['category']);

  //Offline geschaltete Termine anzeigen? NEIN!
	if ($mvars[140024]=='1')
	  $_AS['collection']->setHideOffline(false);

	if ($mvars[130024]=='1')
		$_AS['collection']->_env['hide_archived']='';

	$_AS['collection']->setSorting( array('archived'=>'ASC','online'=>'desc','lastedit'=>'ASC','created'=>'ASC','title'=>'ASC'));

	if ((int) $mvars[120024]>0)
		$_AS['collection']->setLimit($mvars[120024]);

  $_AS['collection']->generate();


  $ic=0;

  for($iter = $_AS['collection']->get(); $iter->valid(); $iter->next() ) {

    //Aktuellen Eintrag als Objekt bereitstellen
    $_AS['item'] =& $iter->current();


				$_AS['temp']['articleselect']['entry_title'] = htmlentities($_AS['item']->getDataByKey('title',true), ENT_COMPAT, 'UTF-8');

				if ((int) $_AS['item']->getDataByKey('online')<1)
					$_AS['temp']['articleselect']['entry_title']='('.$_AS['artsys_obj']->lang->get('module_artsel_offline').') '.$_AS['temp']['articleselect']['entry_title'];

				if ((int) $_AS['item']->getDataByKey('archived')>0)
					$_AS['temp']['articleselect']['entry_title']='('.$_AS['artsys_obj']->lang->get('module_artsel_archived').') '.$_AS['temp']['articleselect']['entry_title'];

				if (strlen($_AS['temp']['articleselect']['entry_title'])>(int) $mvars[110024])
					$_AS['temp']['articleselect']['entry_title']=substr($_AS['temp']['articleselect']['entry_title'],0,(int) $mvars[110024]).' ...';



				$_AS['temp']['articleselect']['html']['form_select'][]	=	'<option value="'. $_AS['item']->getDataByKey('idarticle') .'" '.
																																	(($_AS['temp']['article'] == $_AS['item']->getDataByKey('idarticle') ) ? 'selected="selected"':'').
																																	'>'.
																																	$_AS['temp']['articleselect']['entry_title'].
																																	'</option>'."\n";
				$_AS['temp']['setarticleselect']['html']['form_select'][]	=	'<option value="'. $_AS['item']->getDataByKey('idarticle') .'" '.
																																			(($_AS['temp']['article_mem'] == $_AS['item']->getDataByKey('idarticle') ) ? 'selected="selected"':'').
																																			'>'.
																																			$_AS['temp']['articleselect']['entry_title'].
																																			'</option>'."\n";

		
	}



		
		$_AS['temp']['articleselect']['html']['form_select'][] = '</select>';
		$_AS['temp']['setarticleselect']['html']['form_select'][] = '</select>';
		
		// label
	  $_AS['temp']['articleselect']['html']['form_label'] = '<label for="'.$cms_mod['key'].'article" '.str_replace('{form_name}',$_AS['modkey'].'article',$mvars[104024]).'>'.$mvars[101024].'</label>';
	
		// submit
	  $_AS['temp']['articleselect']['html']['form_submit'] = '<input type="submit" value="'.$mvars[105024].'" '.str_replace('{form_name}',$_AS['modkey'].'article',$mvars[107024]).' />';

		// complete html
		$_AS['temp']['articleselect']['html']['complete'] = $_AS['temp']['articleselect']['html']['form_start'].
																									  	str_replace(
																									  			array(
																									          '{article_select}',
																									          '{article_label}',
																									          '{article_submit}'
																									        ),
																									        array(
																									          "\n".implode("\n",$_AS['temp']['articleselect']['html']['form_select']),
																									          $_AS['temp']['articleselect']['html']['form_label'],
																									          $_AS['temp']['articleselect']['html']['form_submit']."\n"
																									        ),
																									        $mvars[24]).
																								        $_AS['temp']['articleselect']['html']['form_end'];

		// complete html
		$_AS['temp']['setarticleselect']['html']['complete'] = $_AS['temp']['articleselect']['html']['form_start'].
																									  	str_replace(
																									  			array(
																									          '{article_select}',
																									          '{article_label}',
																									          '{article_submit}'
																									        ),
																									        array(
																									          "\n".implode("\n",$_AS['temp']['setarticleselect']['html']['form_select']),
																									          $_AS['temp']['articleselect']['html']['form_label'],
																									          $_AS['temp']['articleselect']['html']['form_submit']."\n"
																									        ),
																									        $mvars[24]).
																								        $_AS['temp']['articleselect']['html']['form_end'];

	  $_AS['output']['list_body'] = str_replace('{article_form}',$_AS['temp']['articleselect']['html']['complete'],$_AS['output']['list_body']);
		if (as_backendmode()) {
		  $_AS['output']['list_body'] = str_replace('{set_article_form}',
		  																					str_replace($_AS['modkey'].'article',
		  																											$_AS['modkey'].'setarticle',
		  																											$_AS['temp']['setarticleselect']['html']['complete']),
		  																					$_AS['output']['list_body']);
		}	else
			$_AS['output']['list_body'] = str_replace('{set_article_form}','',$_AS['output']['list_body']);
	}










	// 
	// chop
	// 
	if (strpos($_AS['output']['list_body'],'{chop}')!==false){
		preg_match_all('#\{chop\}(.*)\{/chop\}#sU',$_AS['output']['list_body'],$_AS['temp']['chopparts']);
		if (!empty($_AS['temp']['chopparts']))
	  	foreach ($_AS['temp']['chopparts'][1] as $k => $v)
	  		$_AS['output']['list_body']=str_replace(	$_AS['temp']['chopparts'][0][$k],
	  																				as_str_chop($v, $mvars['1003'], $mvars['1004'], $mvars['1005']),
	  																				$_AS['output']['list_body']);
	  else
	  	$_AS['output']['list_body']=str_replace(array('{chop}','{/chop}'), array('',''), $_AS['output']['list_body']);
	}
	
	//
	//output
	// 
	echo stripslashes($_AS['output']['list_body']);

}

  unset($adodb,$rs, $_AS, $mvars, $mod);

?>
