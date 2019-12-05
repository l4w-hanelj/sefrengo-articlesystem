//    window.name="main";

    //Callback for Calendar start date
//    function callback_turnus_start(date, month, year)
//    {
//        if (String(month).length == 1)
//        {
//            month = '0' + month;
//        }
//
//        if (String(date).length == 1)
//        {
//            date = '0' + date;
//        }
//        document.getElementById('turnus_start_day').value = date;
//        document.getElementById('turnus_start_month').value = month;
//        document.getElementById('turnus_start_year').value = year;
//    }
//
//    Callback for Calendar start date
//    function callback_turnus_end(date, month, year)
//    {
//        if (String(month).length == 1)
//        {
//            month = '0' + month;
//        }
//
//        if (String(date).length == 1)
//        {
//            date = '0' + date;
//        }
//        document.getElementById('turnus_end_day').value = date;
//        document.getElementById('turnus_end_month').value = month;
//        document.getElementById('turnus_end_year').value = year;
//    }

    //Callback for Calendar start date
    function callback_article_start(date, month, year)
    {
        if (String(month).length == 1)
        {
            month = '0' + month;
        }

        if (String(date).length == 1)
        {
            date = '0' + date;
        }
        document.getElementById('article_start_day').value = date;
        document.getElementById('article_start_month').value = month;
        document.getElementById('article_start_year').value = year;

				var chk=document.getElementById('article_startdate_yn');
				chk.checked=true;
        DisableField( chk, 'article_start_day');DisableField( chk, 'article_start_month');DisableField( chk, 'article_start_year');
        
    }

    //Callback for Calendar start date
    function callback_article_end(date, month, year)
    {
        if (String(month).length == 1)
        {
            month = '0' + month;
        }

        if (String(date).length == 1)
        {
            date = '0' + date;
        }
        document.getElementById('article_end_day').value = date;
        document.getElementById('article_end_month').value = month;
        document.getElementById('article_end_year').value = year;

				var chk=document.getElementById('article_enddate_yn');
				chk.checked=true;
        DisableField( chk, 'article_end_day');DisableField( chk, 'article_end_month');DisableField( chk, 'article_end_year');

    }



