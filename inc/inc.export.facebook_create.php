<?PHP
	$_AS['TMP'] = array();

	$_AS['fbpostid'] = $_AS['cms_wr']->getVal('fbpostid');	
		
	$_AS['artsys_obj'] =& $_AS['article_obj'];
	
	if(is_numeric($_AS['idarticle']) && $_AS['fbpostid']=="") {
		
		$_AS['singlearticle_obj'] = new SingleArticle;	
		 
		$_AS['singlearticle_obj']->loadById($_AS['idarticle']);
		$id=$_AS['idarticle'];
		
		$_AS['TMP']['tpl_main'] = $_AS['artsys_obj']->getSetting('spfnc_'.$_AS['TMP']['exportmode'].'_tpl');

				$_AS['TMP']['config']['date'] = str_replace( array('{day}', '{month}', '{year}'), 
																			array('d', 'm', 'Y'),
																			$_AS['artsys_obj']->getSetting('spfnc_'.$_AS['TMP']['exportmode'].'_tpl_cfg_date'));
		$_AS['TMP']['config']['time'] = str_replace( array('{hour}', '{minute}'), 
																			array('%H', '%M'), 
																			$_AS['artsys_obj']->getSetting('spfnc_'.$_AS['TMP']['exportmode'].'_tpl_cfg_time'));
		$_AS['TMP']['config']['time12'] = str_replace( array('{hour}', '{minute}'),
																				array('%I', '%M'),
																				$_AS['artsys_obj']->getSetting('spfnc_'.$_AS['TMP']['exportmode'].'_tpl_cfg_time'));
		$_AS['TMP']['config']['time24'] = str_replace( array('{hour}', '{minute}'),
																				array('%H', '%M'),
																				$_AS['artsys_obj']->getSetting('spfnc_'.$_AS['TMP']['exportmode'].'_tpl_cfg_time'));
		
		$_AS['TMP']['config']['filesize_str_b'] = 'Byte';
		$_AS['TMP']['config']['filesize_str_kb'] = 'KByte';
		$_AS['TMP']['config']['filesize_str_mb'] = 'MByte';
		$_AS['TMP']['config']['filesize_decplaces'] = 2;
		$_AS['TMP']['config']['day'] = 'd';
		$_AS['TMP']['config']['month'] = 'm';
		$_AS['TMP']['config']['year'] =  'Y';
		$_AS['TMP']['config']['day2'] 	= '%A';
		$_AS['TMP']['config']['month2'] = '%B';
		
		$_AS['TMP']['api_key'] = $_AS['artsys_obj']->getSetting('spfnc_facebook_app_key');
						 
		 
		$_AS['TMP']['publish']=array();
		$_AS['TMP']['publish']['name']=$_AS['singlearticle_obj']->getDataByKey($_AS['artsys_obj']->getSetting('spfnc_facebook_url_name'),$txttransform); 
		$_AS['TMP']['publish']['name']=strip_tags($_AS['TMP']['publish']['name']);
		
		$_AS['TMP']['publish']['caption']=$_AS['singlearticle_obj']->getDataByKey($_AS['artsys_obj']->getSetting('spfnc_facebook_url_caption'),$txttransform); 
		$_AS['TMP']['publish']['caption']=strip_tags($_AS['TMP']['publish']['caption']);
		
		$_AS['TMP']['publish']['description']=$_AS['TMP']['output']; 
		$_AS['TMP']['publish']['description']=strip_tags($_AS['TMP']['publish']['description']);
		
		$_AS['TMP']['publish']['link_man']=$_AS['singlearticle_obj']->getDataByKey($_AS['artsys_obj']->getSetting('spfnc_facebook_url_man'));
		$_AS['TMP']['value_arr']=explode("\n",$_AS['TMP']['publish']['link_man']);
		if ($_AS['artsys_obj']->getSetting('spfnc_facebook_url_man')=='' ||
				$_AS['TMP']['publish']['link_man']=='' ||
				trim($_AS['TMP']['value_arr'][0])=='' ) {
			$_AS['TMP']['publish']['link']=$_AS['artsys_obj']->getSetting('spfnc_facebook_url'); 
			$_AS['TMP']['publish']['link']=str_replace('{idlang}',$lang,$_AS['TMP']['publish']['link']);
			$_AS['TMP']['publish']['link']=str_replace('{idarticle}',$id,$_AS['TMP']['publish']['link']);
			$_AS['TMP']['publish']['link']=str_replace('{idcategory}',$_AS['singlearticle_obj']->getDataByKey('idcategory'),$_AS['TMP']['publish']['link']);
			$_AS['TMP']['publish']['link']=str_replace('{baseurl}',$cfg_client['htmlpath'],$_AS['TMP']['publish']['link']);
			$_AS['TMP']['publish']['link']=$_AS['TMP']['publish']['link'];
		}	else if ($_AS['artsys_obj']->getSetting('article_'.$_AS['artsys_obj']->getSetting('spfnc_facebook_url_man').'_type')=='link') {
				$_AS['TMP']['publish']['link_man']=trim($_AS['TMP']['value_arr'][0]);
				$_AS['TMP']['publish']['name']=trim($_AS['TMP']['value_arr'][1]);
				$_AS['TMP']['value_arr'][0]='';
				$_AS['TMP']['value_arr'][1]='';
				$_AS['TMP']['publish']['caption']=trim(implode("\n",$_AS['TMP']['value_arr']));		
				$_AS['TMP']['publish']['link']=$_AS['TMP']['publish']['link_man'];
				if (strpos($_AS['TMP']['publish']['link'],'cms://')!==false)
					$_AS['TMP']['publish']['link']=str_replace('cms://',$cfg_client['htmlpath'].'index.php?',	$_AS['TMP']['publish']['link']).'&lang='.$lang;
				$_AS['TMP']['publish']['link']=urldecode(trim($_AS['TMP']['publish']['link']));
		} else 
				$_AS['TMP']['publish']['link']=urldecode(trim($_AS['TMP']['publish']['link_man']));
		
		$_AS['TMP']['media_elmtype']=$_AS['artsys_obj']->getSetting('article_'.$_AS['artsys_obj']->getSetting('spfnc_facebook_media').'_type');
		if($_AS['TMP']['media_elmtype']=='text')
			$_AS['TMP']['publish']['media_raw']=$_AS['singlearticle_obj']->getDataByKey($_AS['artsys_obj']->getSetting('spfnc_facebook_media'));
		else {
			$_AS['TMP']['media_raw']=$_AS['singlearticle_obj']->getDataByKey($_AS['artsys_obj']->getSetting('spfnc_facebook_media'));
			$_AS['TMP']['media_raw_arr']=explode("\n",$_AS['TMP']['media_raw']);
			$_AS['TMP']['publish']['media_raw']=$cfg_client['htmlpath'].trim($_AS['TMP']['media_raw_arr'][0]);
		}
		
		$_AS['TMP']['media']['ext']=substr(trim($_AS['TMP']['publish']['media_raw']), strrpos(trim($_AS['TMP']['publish']['media_raw']),".")+1);
		
		
		if (in_array($_AS['TMP']['media']['ext'],array('jpeg','jpg','png','gif','JPEG','JPG','PNG','GIF')))
			$_AS['TMP']['publish']['mediatype']='image';
		else if (in_array($_AS['TMP']['media_raw_arr']['ext'],array('mp3','MP3')))
			$_AS['TMP']['publish']['mediatype']='mp3';
		else if (in_array($_AS['TMP']['media_raw_arr']['ext'],array('swf','SWF')))
			$_AS['TMP']['publish']['mediatype']='flash';
		else 
			$_AS['TMP']['publish']['mediatype']='';
		
		if (!empty($_AS['TMP']['publish']['mediatype']))	
			$_AS['TMP']['publish']['media']=urldecode($_AS['TMP']['publish']['media_raw']);
		else
			$_AS['TMP']['publish']['media']='';

		
		$_AS['TMP']['picture_elmtype']=$_AS['artsys_obj']->getSetting('article_'.$_AS['artsys_obj']->getSetting('spfnc_facebook_picture').'_type');
		if($_AS['TMP']['picture__elmtype']=='text')
			$_AS['TMP']['publish']['picture_raw']=$_AS['singlearticle_obj']->getDataByKey($_AS['artsys_obj']->getSetting('spfnc_facebook_picture'));
		else {
			$_AS['TMP']['picture_raw']=$_AS['singlearticle_obj']->getDataByKey($_AS['artsys_obj']->getSetting('spfnc_facebook_picture'));
			$_AS['TMP']['picture_raw_arr']=explode("\n",$_AS['TMP']['picture_raw']);
			$_AS['TMP']['publish']['picture_raw']=$cfg_client['htmlpath'].trim($_AS['TMP']['picture_raw_arr'][0]);
		}
		
		$_AS['TMP']['picture']['ext']=substr(trim($_AS['TMP']['publish']['picture_raw']), strrpos(trim($_AS['TMP']['publish']['picture_raw']),".")+1);
		
		
			if (in_array($_AS['TMP']['picture']['ext'],array('jpeg','jpg','png','gif','JPEG','JPG','PNG','GIF')))
			$_AS['TMP']['publish']['picture']=urldecode($_AS['TMP']['publish']['picture_raw']);
		else
			$_AS['TMP']['publish']['picture']='';
			
		if (empty($_AS['TMP']['publish']['name']))
			$_AS['TMP']['publish']['name']=$_AS['TMP']['publish']['link'];
		
		$_AS['TMP']['publish']['name']=json_encode($_AS['TMP']['publish']['name']);
		$_AS['TMP']['publish']['link']=json_encode($_AS['TMP']['publish']['link']);
		$_AS['TMP']['publish']['media']=json_encode($_AS['TMP']['publish']['media']);
		$_AS['TMP']['publish']['picture']=json_encode($_AS['TMP']['publish']['picture']);
		$_AS['TMP']['publish']['caption']=json_encode($_AS['TMP']['publish']['caption']);
		$_AS['TMP']['publish']['description']=json_encode($_AS['TMP']['publish']['description']);
		
		
		echo '<div id="fb-root"></div>'."\n";
		echo '<script src="http://connect.facebook.net/en_US/all.js"></script>'."\n";
		echo '<script type="text/javascript">'."\n";
		echo 'var applicationid = '.$_AS['TMP']['api_key'].';'."\n";
		echo 'var redirecturi = "'.$cfg_cms['cms_html_path'].'plugins/articlesystem/export_facebook_update.php"'."\n";
		echo '</script>'."\n";
		
		echo '<script type="text/javascript" src="plugins/articlesystem/js/export.facebook.js"></script>'."\n";
		
		echo '<script type="text/javascript">'."\n";
		echo 'var name'.$id.' = '.$_AS['TMP']['publish']['name'].';'."\n";
		echo 'var caption'.$id.' = '.$_AS['TMP']['publish']['caption'].';'."\n";
		echo 'var description'.$id.' = '.$_AS['TMP']['publish']['description'].';'."\n";
		echo 'var link'.$id.' = '.$_AS['TMP']['publish']['link'].';'."\n";
		echo 'var media'.$id.' = '.$_AS['TMP']['publish']['media'].';'."\n";
		echo 'var picture'.$id.' = '.$_AS['TMP']['publish']['picture'].';'."\n";
		echo 'window.onload = function (){streamPublish(name'.$id.', link'.$id.', caption'.$id.', description'.$id.', picture'.$id.', media'.$id.','.$id.',\''.$_AS['artsys_obj']->getSetting('spfnc_facebook_from').'\',\''.$_AS['artsys_obj']->getSetting('spfnc_facebook_to').'\')}'."\n";
		echo '</script>'."\n";


	}
	if (is_numeric($_AS['idarticle']) && !empty($_AS['fbpostid'])) {

		$_AS['singlearticle_obj'] = new SingleArticle;	
		 
		$_AS['singlearticle_obj']->loadById($_AS['idarticle']);

		$_AS['TMP']['updatedateelement']=$_AS['artsys_obj']->getSetting('spfnc_facebook_lastsent_date_cf');
		$_AS['TMP']['updatepostidelement']=$_AS['artsys_obj']->getSetting('spfnc_facebook_lastsent_postid_cf');
		$_AS['TMP']['sentdatetime']=date("Y-m-d H:i:s");
		$_AS['TMP']['sentpostid']=$_AS['cms_wr']->getVal('fbpostid');
		date("Y-m-d H:i:s");
		$_AS['singlearticle_obj']->setData($_AS['TMP']['updatedateelement'], $_AS['TMP']['sentdatetime']);
		$_AS['singlearticle_obj']->setData($_AS['TMP']['updatepostidelement'], $_AS['TMP']['sentpostid']);
		$_AS['singlearticle_obj']->save();
		
	}

/*
	if(strpos($_AS['action'],'export_facebook_delete')!==false && !empty($_AS['fbpostid'])) {
		echo '<div id="fb-root"></div>'."\n";
		echo '<script src="http://connect.facebook.net/en_US/all.js"></script>'."\n";
		echo '<script type="text/javascript" src="plugins/articlesystem/js/export.facebook.js"></script>'."\n";
	
		echo '<script type="text/javascript">'."\n";
		echo 'window.onload = function (){deletePost("'.$_AS['fbpostid'].'")}'."\n";
		echo '</script>'."\n";
		
	}  
*/  

?>