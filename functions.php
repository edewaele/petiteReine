<?php

function getLocale($LOCALES)
{
	foreach(explode(",",$_SERVER['HTTP_ACCEPT_LANGUAGE']) as $lang)
	{
		foreach($LOCALES as $supportedLangs => $localeID)
		{
			if(strpos($lang,$supportedLangs) == 0)
			{
				return $localeID;
			}
		}
	}
	return 'default';
}

?>