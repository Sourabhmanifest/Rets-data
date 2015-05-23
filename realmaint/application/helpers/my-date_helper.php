<?php
// Adds a third argument to timespan() that stops display of minutes/
// seconds in the final output
function timespan($seconds = 1, $time = '', $display_mins_secs = true)
{
    $CI =& get_instance();
    $CI->lang->load('date');

    if ( ! is_numeric($seconds))
    {
        $seconds = 1;
    }

    if ( ! is_numeric($time))
    {
        $time = time();
    }
	$str='';
    $days = floor($seconds / 86400);

    if ($days > 0)
    {
        if ($days > 0)
        {   
            $str .= $days.' '.$CI->lang->line((($days   > 1) ? 'date_days' : 'date_day')).', ';
        }

        $seconds -= $days * 86400;
    }

    $hours = floor($seconds / 3600);

    

    // don't display minutes/seconds unless $display_mins_secs
    // == true
    if ($display_mins_secs)
    {
        $minutes = floor($seconds / 60);

        if ($days > 0 OR $hours > 0 OR $minutes > 0)
        {
            if ($minutes > 0)
            {   
                $str .= $minutes.' '.$CI->lang->line((($minutes > 1) ? 'date_minutes' : 'date_minute')).', ';
            }

            $seconds -= $minutes * 60;
        }

        if ($str == '')
        {
            $str .= $seconds.' '.$CI->lang->line((($seconds > 1) ? 'date_seconds' : 'date_second')).', ';
        }
    }

    return substr(trim($str), 0, -1);
}

?>