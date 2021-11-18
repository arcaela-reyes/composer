<?php

function date_str($time=null,$compare=null,$format=false) {
	$time = empty($time) ? strtotime('now') : (is_string($time) ? strtotime($time) : $time);
	$compare = empty($compare) ? false : (is_string($compare) ? strtotime($compare) : $compare);
	$diff	 = !$compare ? (time()-$time) : ($time-$compare);
	$format = is_string($format) ? $format : false;
	$ng = ($diff<0);
	$diff	 = $ng ? ($diff*(0-1)) : $diff;
	$s = $diff;
	$date = array(
		's'	=> $s,
		'i'	=> intval($s / 60),
		'h'	=> intval($s / 3600),
		'd'	=> intval($s / 86400),
		'w'	=> intval($s / 604800),
		'm'	=> intval($s / 2620800),
		'y'	=> intval($s / 31449600),
	);
	$date = array_merge($date,array(
		'S' => $date['s']." segundo".($date['s']>1 ? 's' : ''),
		'I' => $date['i']." minuto".($date['i']>1 ? 's' : ''),
		'H' => $date['h']." hora".($date['h']>1 ? 's' : ''),
		'D' => $date['d']." dia".($date['d']>1 ? 's' : ''),
		'W' => $date['w']." semana".($date['w']>1 ? 's' : ''),
		'M' => $date['m']." mese".($date['m']>1 ? 's' : ''),
		'Y' => $date['y']." año".($date['y']>1 ? 's' : ''),
	));
	$datetime = preg_replace_callback("/\%\w+/",function($item) use ($date){
		$k = str_replace('%', '', $item[0]);
		return $date[$k];
	}, $format);
	if (!$format) {
		$datetime = "Hace un momento";
		$pre = $date['s']>30 ? (
			$ng ? 'Dentro de ' : 'Hace '
		) : '';
		$datetime = ($date['s']>30&&$date['s']<60) ? $date['S'] : $datetime;
		$datetime = ($date['s']>=60&&$date['s']<3600) ? $date['I'] : $datetime;
		$datetime = ($date['s']>=3600&&$date['s']<86400) ? $date['H'] : $datetime;
		$datetime = ($date['s']>=86400&&$date['s']<604800) ? $date['D'] : $datetime;
		$datetime = ($date['s']>=604800&&$date['s']<2620800) ? $date['W'] : $datetime;
		$datetime = ($date['s']>=2620800&&$date['s']<31449600) ? $date['M'] : $datetime;
		$datetime = ($date['s']>=31449600) ? $date['Y'] : $datetime;
		$datetime = $pre.$datetime;
	}
	return $datetime;
}