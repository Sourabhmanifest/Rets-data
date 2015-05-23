<?php 

$version= "0.4";
$radius_of_earth = 6378100;
$alphabet = array( "g", "a", "t", "c");
$decode_map = array("g"=>0, "a"=>1, "t"=>2,"c"=>3);

// Helper functions used by GeoDNA functions
function _deg2rad ( degrees ) 
{
    return degrees * ( Math.PI / 180 );
}

function _rad2deg ( radians ) 
{
    return radians * ( 180 / Math.PI );
}

function _mod ( x, m) 
{
    return ( x % m + m ) % m;
}

GeoDNA = 
{
    function encode( $latitude, $longitude, $options ) 
	{
		$options   = $options || {};
		/*if( count($options) )
		{
			 $options   = $options ;
		}
		else
		{
			$options   ="{}";
		}*/
        
        $precision = options['precision'] || 22;
		/*if( isset($options['precision']))
		{
			 $precision = $options['precision'];
		}
		else
		{
			$precision = 22;
		}*/

        $geodna = '';
        $loni = [];
        $lati = [];

        if ( isset($options['radians']) ) 
		{
            $latitude  = _rad2deg( $latitude );
            $longitude = _rad2deg( $longitude );
        }

		$bits = normalise( $latitude, $longitude );
        $latitude = bits[0];
        $longitude = bits[1];

        if ( $longitude < 0 ) 
		{
            $geodna = $geodna.'w';
            $loni = "-180.0, 0.0" ;
        } 
		else 
		{
            $geodna = $geodna.'e';
            $loni = "0.0, 180.0";
        }

        $lati = "-90.0, 90.0";

		while ( strlen($geodna) < $precision ) 
		{
            $ch = 0;
/*************************************************/
            $mid = (( $loni[0] + $loni[1] ) / 2);
            if ( $longitude > $mid ) 
			{
                $ch = $ch | 2;
                $loni = [ $mid, $loni[1] ];
            }
			else 
			{
                $loni = [ $loni[0], $mid ];
            }
			$mid = (( $lati[0] + $lati[1] ) / 2);
            if ( $latitude > $mid ) {
                $ch = $ch | 1;
                $lati = [ $mid, $lati[1] ];
            } else {
                $lati = [ $lati[0], $mid ];
            }

            $geodna = $geodna + $alphabet['ch'];
        }
        return $geodna;
    }
/***************************************************/
	function decode( $geodna,$options ) 
	{
        $options = $options || {};

		$bits = boundingBox( $geodna );
        $lati = $bits[0];
        $loni = $bits[1];

        $lat = (( $lati[0] + $lati[1] ) / 2.0);
        $lon = (( $loni[0] + $loni[1] ) / 2.0);

        if ( $options['radians'] ) 
		{
            return [ _deg2rad( $lat ), _deg2rad( $lon ) ];
        }
        return [ $lat, $lon ];
    }

	// locates the min/max lat/lons around the geo_dna
    function boundingBox( $geodna ) 
	{
        $chars = explode(new RegExp(''));

       $loni;
       $lati = "-90.0, 90.0";

        $first = $chars[0];

        if ( $first == 'w' ) 
		{
            $loni = "-180.0, 0.0";
        } 
		else if ( $first == 'e' ) 
		{
            $loni = "0.0, 180.0";
        }

        for ( var i = 1; i < count($chars); i++ ) 
		{
            $c  = $chars[i];
            $cd = $decode_map['c'];
            if ( $cd & 2 ) 
			{
                $loni = [ ( $loni[0] + $loni[1] ) / 2.0, $loni[1] ];
            }
			else 
			{
                $loni = [ $loni[0],  ( $loni[0] + $loni[1] ) / 2.0 ];
            }
            if ( $cd & 1 )
			{
                $lati = [ ( $lati[0] + $lati[1] ) / 2.0, $lati[1] ];
            }
			else 
			{
                $lati = [ $lati[0],  ( $lati[0] + $lati[1] ) / 2.0 ];
            }
        }
        return [ $lati, $loni ];
    }

	 function addVector ( $geodna, $dy, $dx ) 
	{
		$bits = decode( $geodna );
        $lat = $bits[0];
        $lon = $bits[1];
        return [
            _mod(( $lat + 90.0 + $dy ), 180.0 ) - 90.0,
            _mod(( $lon + 180.0 + $dx ), 360.0 ) - 180.0
        ];
    }

    function normalise( $lat, $lon ) 
	{
        return [
            _mod(( $lat + 90.0 ), 180.0 ) - 90.0,
            _mod(( $lon + 180.0 ), 360.0 ) - 180.0,
        ];
    }

	function pointFromPointBearingAndDistance( $geodna, $bearing, $distance, $options ) 
	{
		$options   = $options || {};
		$distance = $distance * 1000; // make it metres instead of kilometres
		$precision = $options['precision'] || strlen($geodna);
		$bits = decode( $geodna, { $radians= true } );
		$lat1 = $bits[0];
		$lon1 = $bits[1];
		$lat2 = Math.asin( Math.sin( $lat1 ) * Math.cos( $distance / $radius_of_earth ) + Math.cos( $lat1 ) * Math.sin( $distance / $radius_of_earth ) * Math.cos( $bearing ) );
		$lon2 = $lon1 + Math.atan2( Math.sin( $bearing ) * Math.sin( $distance / $radius_of_earth ) * Math.cos( $lat1 ), Math.cos( $distance /$radius_of_earth ) - Math.sin( $lat1 ) * Math.sin( $lat2 ));
		
		return encode( $lat2, $lon2, { $precision: $precision, $radians= true } );
	}

	function distanceInKm( $ga, $gb ) 
	{
		$a = decode( $ga );
        $b = decode( $gb );

        // if a[1] and b[1] have different signs, we need to translate
        // everything a bit in order for the formulae to work.
        if ( $a[1] * $b[1] < 0.0 && Math.abs( $a[1] - $b[1] ) > 180.0 ) {
            $a = addVector( $ga, 0.0, 180.0 );
            $b = addVector( $gb, 0.0, 180.0 );
        }
        $x = ( _deg2rad($b[1]) - _deg2rad($a[1]) ) * Math.cos( ( _deg2rad($a[0]) + _deg2rad($b[0])) / 2 );
        $y = ( _deg2rad($b[0]) - _deg2rad($a[0]) );
        $d = Math.sqrt( $x*$x + $y*$y ) * $radius_of_earth;
        return $d / 1000;
    }

	function neighbours( $geodna ) 
	{
		$bits = boundingBox( $geodna );
		$lati = $bits[0];
		$loni = $bits[1];
		$width  = Math.abs( $loni[1] - $loni[0] );
		$height = Math.abs( $lati[1] - $lati[0] );
		$neighbours = array();

		for ($i = -1; $i <= 1; $i++ ) 
		{
			for ( $j = -1; $j <= 1; $j++ ) 
			{
				if ( $i || $j ) 
				{
					$bits = addVector ( $geodna, $height * $i, $width * $j );
					$neighbours[count($neighbours)] = encode( $bits[0], $bits[1], $precision= strlen($geodna));
				}
			}
		}
		return $neighbours;
	}

	// This is experimental!!
    // Totally unoptimised - use at your peril!
	function neighboursWithinRadius( $geodna, $radius, $options) 
	{
		$options = $options || {};
		$options.precision = $options['precision'] || 12;

		$neighbours = array();
		$rh = $radius * Math.SQRT2;

		$start = GeoDNA.pointFromPointBearingAndDistance( $geodna, -( Math.PI / 4 ), $rh, $options );
		$end = pointFromPointBearingAndDistance( $geodna, Math.PI / 4, $rh, $options );
		$bbox = boundingBox( $start );
		$bits = decode( $start );
		$slon = $bits[1];
		$bits = decode( $end );
		$elon = $bits[1];
		$dheight = Math.abs( $bbox[0][1] - $bbox[0][0] );
		$dwidth  = Math.abs( $bbox[1][1] - $bbox[1][0] );
		$n = GeoDNA.normalise( 0.0, Math.abs( $elon - $slon ) );
		$delta = Math.abs($n[1]);
		$tlat = 0.0;
		$tlon = 0.0;
		$current = $start;

		while ( $tlat <= $delta ) 
		{
			while ( $tlon <= $delta ) 
			{
				$cbits = addVector( $current, 0.0, $dwidth );
				$current = encode( $cbits[0], $cbits[1], $options );
				$d = distanceInKm( $current, $geodna );
				if ( $d <= $radius ) 
				{
					$neighbours[count($neighbours)] = $current;
				}
				$tlon = $tlon + $dwidth;
			}

			$tlat = $tlat + $dheight;
			$bits = addVector( $start, -$tlat , 0.0 );
			$current = encode( $bits[0], $bits[1], $options );
			$tlon = 0.0;
		}
		return $neighbours;
	 }

	// This takes an array of GeoDNA codes and reduces it to its
	// minimal set of codes covering the same area.
	// Needs a more optimal impl.
	function reduce( $geodna_codes ) 
	{
		// hash all the codes
		$codes = {};
		for ($i = 0; $i < count($geodna_codes); $i++ ) 
		{
			$codes[ $geodna_codes[$i] ] = 1;
		}

		$reduced = array();
		$code;
		for ($i = 0; $i < count($geodna_codes); $i++ ) 
		{
			$code = $geodna_codes[$i];
			if ( $codes[ $code ] ) 
			{
				$parent = $code.substr( 0, count($code) - 1 );

				if ( $codes [ $parent + 'a' ] && $codes [ $parent + 't' ] && $codes [ $parent + 'g' ] && $codes [ $parent + 'c' ]) 
				{
					$codes[ $parent + 'a' ] = null;
					$codes[ $parent + 't' ] = null;
					$codes[$parent + 'g' ] = null;
					$codes[ $parent + 'c' ] = null;
					$reduced.push($parent );
				} 
				else
				{
					$reduced.push( $code );
				}
			}
		}
		if ( count($geodna_codes) == count($reduced) ) 
		{
			return $reduced;
		}
		return reduce( $reduced );
	}
	 // ********************************
    // Google Maps support functions
    // ********************************

	function encodeGoogleLatLng( $latlng, $options )
	{
        $options = $options || {};
        $lat = latlng.lat();
        $lon = latlng.lng();
        return encode( $lat, $lon, $options );
    },

    function decodeGoogleLatLng( $geodna ) 
	{
        $bits = decode( $geodna );
        return new google.maps.LatLng( $bits[0], $bits[1] );
    }

    function boundingBoxPolygon( $geodna, $options ) 
	{
        $options = $options || {};
        $bbox = boundingBox( $geodna );
        $vertices = array(
            new google.maps.LatLng( bbox[0][0], bbox[1][0] ),
            new google.maps.LatLng( bbox[0][0], bbox[1][1] ),
            new google.maps.LatLng( bbox[0][1], bbox[1][1] ),
            new google.maps.LatLng( bbox[0][1], bbox[1][0] )
        );

        $options['paths'] = $vertices;
        return new google.maps.Polygon( $options );
    }

    function map( $geodna, $element, $options )
	{
        $options = $options || {};
        $mapOptions = {
            center: GeoDNA.decodeGoogleLatLng( $geodna ),
            mapTypeId: google.maps.MapTypeId.ROADMAP
        };
        for (var key in options) 
		{
            $mapOptions[key] = $options[key];
        }

        $map = new google.maps.Map($element, $mapOptions);

        return $map;
    }
}

?>