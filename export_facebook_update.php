
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="de" lang="de">
<head>
  <meta http-equiv="content-type" content="text/html; charset=utf-8" />
  <title></title>
</head>

<body>
<script type="text/javascript">

if (opener==null) {;
	var uri=window.parent.location.toString();
	uri=uri.split("#")[0];
	window.parent.location=uri+'<?PHP echo empty($_REQUEST['idarticle'])?'':'&idarticle='+$_REQUEST['idarticle']; ?>'+'&fbpostid=<?PHP echo empty($_REQUEST['post_id'])?'0':$_REQUEST['post_id']; ?>';
	top.window.FB.Dialog.remove(top.window.FB.Dialog._active);
} else {
	var uri=opener.location.toString();
	uri=uri.split("#")[0];
	opener.location=uri+'<?PHP echo empty($_REQUEST['idarticle'])?'':'&idarticle='+$_REQUEST['idarticle']; ?>'+'&fbpostid=<?PHP echo empty($_REQUEST['post_id'])?'0':$_REQUEST['post_id']; ?>';
	window.close();
}
</script>

</body>
</html>

