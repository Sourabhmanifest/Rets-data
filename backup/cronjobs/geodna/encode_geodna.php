<?php 
//exit("in");
$alphabet = array(); 


// Helper functions used by GeoDNA functions
function _mod ( $x, $m) 
{
    return ( $x % $m + $m ) % $m;
}

function encode( $latitude, $longitude) 
{
	$alphabet = array( "g", "a", "t", "c"); 
	$precision = 22;
	
	$geodna = '';
	$loni = array();
	$lati = array();

	/*$bits = normalise( $latitude, $longitude );
	$latitude = $bits[0];
	$longitude = $bits[1];*/

	if ( $longitude < 0 ) 
	{
		$geodna = $geodna.'w';
		$loni = array("-180.0"," 0.0") ;
	} 
	else 
	{
		$geodna = $geodna.'e';
		$loni =array( "0.0", "180.0");
	}

	$lati = array("-90.0","90.0");

	while (strlen($geodna) < $precision ) 
	{
		$ch = 0;
		$mid = ( $loni[0] + $loni[1] ) / 2;
		if ( $longitude > $mid ) 
		{
			$ch = $ch | 2;
			$loni = array($mid, $loni[1] );
		}
		else 
		{
			$loni = array($loni[0], $mid );
		}
		
		$mid = ( $lati[0] + $lati[1] ) / 2;

		if ( $latitude > $mid ) 
		{
			$ch = $ch | 1;
			$lati = array( $mid, $lati[1] );
		} 
		else 
		{
			$lati = array($lati[0], $mid );
		}
		$geodna = $geodna.$alphabet[$ch];
	}
	return $geodna;
}
function normalise( $lat, $lon ) 
{
	return array( _mod(( $lat + 90.0 ), 180.0 ) - 90.0,_mod(( $lon + 180.0 ), 360.0 ) - 180.0);
}


?>