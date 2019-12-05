
FB.init({	appId: applicationid, 
					status: true, 
					cookie: true,
         	xfbml: true});

            /* All the events registered */
            FB.Event.subscribe('auth.login', function(response) {
                // do something with response
                login();
            });
            FB.Event.subscribe('auth.logout', function(response) {
                // do something with response
                logout();
            });

            FB.getLoginStatus(function(response) {
                if (response.session) {
                    // logged in and connected user, someone you know
                    login();
                }
            });


        function login(){
            FB.api('/me', function(response) {
                document.getElementById('login').style.display = "block";
                document.getElementById('login').innerHTML = response.name + " succsessfully logged in!";
            });
        }
        function logout(){
            document.getElementById('login').style.display = "none";
        }

        //stream publish method
        function streamPublish(linkname, link, caption, description, picture, media, aid,from,to){

          FB.ui({
              method: 'feed',
              message: '',  
              from : from,
              to : to,
              link: link,
              name: linkname, 
              caption: caption, 
              description: description, 
              source: media,
              picture:  picture,
              next: null,
              redirect_uri: redirecturi+'?idarticle='+aid
          });
				}
        //stream publish method
            function deletePost(postid){

						FB.api('/'+postid.toString(), 'delete', function(response) {
					  if (!response || response.error) {
					    alert('Error occured');
					  } else {
					    alert('Post was deleted');
					  }
					});	
				}					
						
function getDateTime() {
	var now = new Date();
	
	var year=now.getFullYear();
	var month=now.getMonth()+1;
	var day=now.getDate();
	var minute=now.getMinutes()
	var hour=now.getHours();
	var seconds=now.getSeconds();
	
	if (month<10)
		month='0'+month;

	if (day<10)
		day='0'+day;

	if (minute<10)
		minute='0'+minute;

	if (hour<10)
		hour='0'+hour;

	if (seconds<10)
		seconds='0'+seconds;

	return year+"-"+month+"-"+day+" "+hour+":"+minute+":"+seconds;
																	
}
    