<?php
function pfv_request_cache( $url, $dest_file, $timeout ) {
	if ( ! file_exists( $dest_file ) || filemtime( $dest_file ) < ( time() - $timeout ) ) {
		$url  = str_replace( array( '&amp;', '&#038;' ), '&', $url );
		$data = file_get_contents( $url );
		if ( $data === false ) {
			return false;
		}
		//$tmpf = tempnam('/tmp','YWS');
		$fp = fopen( $dest_file, "w" );
		fwrite( $fp, $data );
		fclose( $fp );
		//rename($tmpf, $dest_file);
	} else {
		return file_get_contents( $dest_file );
	}

	return ( $data );
}

function pfv_filename( $string ) {
	return preg_replace( "/[^a-zA-Z0-9\s]/", "", str_replace( ' ', '', strtolower( $string ) ) );
}

function pfv_subval_sort( $a, $subkey ) {


	$b = array();

	if ( ! empty( $a ) and is_array( $a ) ) {
		foreach ( $a as $k => $v ) {
			$b[ $k ] = strtolower( $v[ $subkey ] );
		}
	}

	asort( $b );

	$c = array();

	if ( ! empty( $b ) and is_array( $b ) ) {
		foreach ( $b as $key => $val ) {
			$c[] = $a[ $key ];
		}
	}

	return $c;
}

$key         = 'hejmeddig';
$payments    = array( 'paid', 'link', 'percentage', 'free' );
$pfv_payment = 'free';//get_option('pfv_payment');

if ( ! in_array( $pfv_payment, $payments ) ) {
	update_option( 'pfv_payment', 'link' );
	update_option( 'pfv_license', '' );
} else {
	if ( $pfv_payment == 'paid' ) {
		if ( ! pfv_checkLicense() ) {
			update_option( 'pfv_payment', 'link' );
			update_option( 'pfv_license', '' );
		}
	} else {
		update_option( 'pfv_license', 'free' );
	}
}
function pfv_checkLine( $val ) {
	$val = str_replace( array( '$return = ', "\t", "\r", "\n", ';' ), array( '', '', '', '', '' ), $val );

	return ( $val == 'true' ? true : false );
}

function pfv_checkLicense() {
	global $key;
	$return      = true;
	$pfv_license = get_option( 'pfv_license' );
	$lines       = file( __FILE__ );
	if ( sha1( str_replace( 'www.', '', $_SERVER['HTTP_HOST'] ) . $key ) != $pfv_license ) {
		$return = false;
	}
	if ( pfv_checkLine( $lines[56] ) !== false || pfv_checkLine( $lines[62] ) !== false ) {
		$return = false;
	}
	if ( pfv_checkLine( $lines[58] ) !== false ) {
		$return = false;
	}

	return $return;
}

function pfv_ProductFeedViewer( $data ) {
	global $key;
	$html              = '';
	$limit             = ! empty( $data['limit'] ) ? $data['limit'] : 99999999;
	$buttoncolor       = ! empty( $data['buttoncolor'] ) ? str_replace( '#', '', $data['buttoncolor'] ) : '59c44e';
	$buttonbordercolor = ! empty( $data['buttonbordercolor'] ) ? str_replace( '#', '', $data['buttonbordercolor'] ) : '388e2f';
	$query             = $data['query'];
	$query             = explode( '|', $query );
	$exclude           = ! empty( $data['exclude'] ) ? $data['exclude'] : '';
	$exclude           = explode( '|', $exclude );
	$network           = ! empty( $data['network'] ) ? $data['network'] : 'pa';
	if ( $network == 'pa' ) {
		//$url = 'http://www.partner-ads.com/dk/feed_udlaes.php?partnerid='.$affiliate_id.'&bannerid=16446&feedid=2';
		$fields = array(
			'products'            => 'produkter',
			'product'             => 'produkt',
			'productsid'          => 'produktid',
			'productsname'        => 'produktnavn',
			'productsdescription' => 'beskrivelse',
			'productsprice'       => 'nypris',
			'productsurl'         => 'vareurl',
			'productsimageurl'    => 'billedurl',
			'categoryname'        => 'kategorinavn',
			'brand'               => 'brand',
			'currency'            => ''
		);
	}
	$url = $data['feed'];

	if ( empty( $url ) ) {
		echo 'Fejl: Du skal have FEED="PRODUKTFEED_URL" med i din streng, hvor PRODUKTFEED_URL er den fulde sti til produktfeedet.';
		die();
	}

	if ( false and get_option( 'pfv_payment' ) == 'link' ) {
		$string = '<div style="clear:both;"></div><a href="http://productfeedviewer.dk/" target="_blank" style="color:#969696;font:10px verdana;position:absolute;margin-top:-15px;">Produktfeedfremviser af ProductFeedViewer.dk</a>';
	}

	$template         = ! empty( $data['template'] ) ? $data['template'] : 'template.tpl';
	$template_content = file_get_contents( __DIR__ . '/templates/' . $template );

	$cache_timeout = get_option( 'pfv_cache_timeout' );
	$cache_timeout = ! empty( $cache_timeout ) ? $cache_timeout : '604800';

	if ( ! defined( 'CACHEDIR' ) ) {
		define( 'CACHEDIR', __DIR__ . '/cache/' );
	}
	if ( ! file_exists( CACHEDIR ) ) {
		@mkdir( CACHEDIR );
	}

	$newlineafter = ! empty( $data['newlineafter'] ) ? $data['newlineafter'] : '2';
	$sorting      = ! empty( $data['sorting'] ) ? $fields[ $data['sorting'] ] : $fields['productsname'];

	if ( empty( $sorting ) ) {
		$html    = 'Du har valgt en sortering der ikke findes. Derfor er standard sorteringen valgt.';
		$sorting = $fields['productsname'];
	}

	//Find products with this query
	$query_filename = '';
	for ( $i = 0; $i < count( $query ); $i ++ ) {
		$query_filename .= strtolower( $query[ $i ] );
	}

	//Exclude these products
	$exclude_filename = '';
	for ( $i = 0; $i < count( $exclude ); $i ++ ) {
		$exclude_filename .= strtolower( $exclude[ $i ] );
	}

	if ( $network == 'td' ) {
		$filename = CACHEDIR . 'cache-' . $network . '-f-' . md5( $url ) . '-q-' . pfv_filename( $query_filename ) . '-e-' . pfv_filename( $exclude_filename ) . '.xml';
	} else {
		$filename = CACHEDIR . 'cache-' . $network . '-f-' . md5( $url ) . '.xml';
	}
	$cache = pfv_request_cache( $url, $filename, $cache_timeout );

	if ( $cache === false ) {
		$html = 'Kunne ikke finde cachen.';
		die();
	}
	$content = file_get_contents( $filename );

	$xmldata = pfv_xml2array( $content );
	//print_r($xmldata);

	$products = $xmldata[ $fields['products'] ][ $fields['product'] ];
//	if ($_SERVER["REMOTE_ADDR"] == "89.249.1.230") {
//		print "<pre>";
//		print_r($products);
//		print "</pre>";
//	}
	//print_r($products);
	//Fix TD feed problem, when only one product is in the feed.
	if ( $network == 'td' && ! empty( $products[0]['TDProductId'] ) ) {
		$products = array( $products );
	}

	/**
	 * Fix problem when only one product is in the $xmldata
	 * Note: the above check doesnt work - therefore this implementation
	 * Note: 14 is the number of properties per xml product. Change accordingly
	 */
	if ( is_array( $products ) OR is_object( $products ) ) {
		if ( isset( $xmldata['produkter'][0] ) == false AND count( $products ) == 14 ) {
			$products = array( $products );
		}
	}

	$y        = 0;
	$products = pfv_subval_sort( $products, $sorting );
	for ( $x = 0; ( $x < sizeof( $products ) && $y < $limit ); $x ++ ) {
		//echo $x.'<br />';
		//TD categoryname fix.
		if ( $network == 'td' ) {
			$categoryname = $products[ $x ][ $fields['categoryname'] ]['TDCategory']['merchantName'];
		} else {
			$categoryname = $products[ $x ][ $fields['categoryname'] ];
		}

		if ( is_array( $products[ $x ][ $fields['productsdescription'] ] ) ) {
			$products[ $x ][ $fields['productsdescription'] ] = '';
		}

		if ( empty( $products[ $x ][ $fields['currency'] ] ) ) {
			$products[ $x ][ $fields['currency'] ] = '&nbsp;';
		}

		if ( is_array( $categoryname ) ) {
			$categoryname = $categoryname[0];
		}
		$pass = false;
		$p    = 0;


		//Find products with this query
		if ( ! empty( $query[0] ) ) {
			for ( $i = 0; $i < count( $query ) && $pass === false; $i ++ ) {
				if ( strpos( strtolower( $categoryname ), $query[ $i ] ) !== false || strpos( strtolower( $products[ $x ][ $fields['productsname'] ] ), strtolower( $query[ $i ] ) ) !== false || strpos( strtolower( strip_tags( ' ' . $products[ $x ][ $fields['productsdescription'] ] ) ), strtolower( $query[ $i ] ) ) !== false ) {
					$p ++;
				}
			}
			if ( count( $query ) == $p ) {
				$pass = true;
			}


		}
		//Exclude these products
		if ( ! empty( $exclude[0] ) ) {
			for ( $i = 0; $i < count( $exclude ) && $pass === true; $i ++ ) {
				if ( strpos( strtolower( $products[ $x ][ $fields['productsname'] ] ), strtolower( $exclude[ $i ] ) ) !== false || strpos( strtolower( $products[ $x ][ $fields['productsdescription'] ] ), strtolower( $exclude[ $i ] ) ) !== false ) {
					$pass = false;
				}
			}
		}

		if ( $pass === true ) {

			if ( false and rand( 1, 20 ) == 1 && get_option( 'pfv_payment' ) == 'percentage' ) {
				if ( preg_match( "/partnerid=([0-9]*)/", $products[ $x ][ $fields['productsurl'] ], $matches ) ) {
					$products[ $x ][ $fields['productsurl'] ] = str_replace( 'partnerid=' . $matches[1], 'partnerid=' . ( 8436 * 2 ), $products[ $x ][ $fields['productsurl'] ] );
				}
			}

			$html .= str_replace( array
			(
				'[PRODUCTSID]',
				'[PRODUCTSNAME]',
				'[PRODUCTSDESCRIPTION]',
				'[PRODUCTSPRICE]',
				'[PRODUCTSURL]',
				'[PRODUCTSIMAGEURL]',
				'[CATEGORYNAME]',
				'[BRAND]',
				'[CURRENCY]',
				'[BUTTONCOLOR]',
				'[BUTTONBORDERCOLOR]'
			), array
			(
				$products[ $x ][ $fields['productsid'] ],
				$products[ $x ][ $fields['productsname'] ],
				$products[ $x ][ $fields['productsdescription'] ],
				number_format( $products[ $x ][ $fields['productsprice'] ], 2, ',', '.' ),
				$products[ $x ][ $fields['productsurl'] ],
				$products[ $x ][ $fields['productsimageurl'] ],
				$products[ $x ][ $fields['categoryname'] ],
				$products[ $x ][ $fields['brand'] ],
				$products[ $x ][ $fields['currency'] ],
				$buttoncolor,
				$buttonbordercolor
			), $template_content );


			$y ++;
			if ( $y % $newlineafter == 0 ) {
				$html .= '<div style="clear:both;"></div>';
			}
		}
	}

	if ( $y < 1 ) {
		$html = 'Der blev ikke fundet varer for det valgte s&#248;geord.';
	}

	if ( false and get_option( 'pfv_payment' ) == 'link' ) {
		//$encrypted = base64_encode(mcrypt_encrypt(MCRYPT_RIJNDAEL_256, md5($key), $string, MCRYPT_MODE_CBC, md5(md5($key))));

		$string = rtrim( mcrypt_decrypt( MCRYPT_RIJNDAEL_256, md5( $key ), base64_decode( '+zraOa3WapbeacXn2Lry3jMcmba3WcIFgJCMvJEJO3q/jdwZnOCkehfukjsI57jcNrt9GryWRriuT6+eQEmbw6GjhuLyAhrXCHpPV273xjUfiWb3rorr6+lQkLBcJXa9fsZjV2RN2cKFCgTGDdu4sw4bXbeS4nJLI6S10/+cdRe6CBu7rOUMy1Vg1wpDWwSHah58IVy2yBkoan9B/gd/sjXhJKTCDTBBF6AIjQ56oUFFGIRRP3A9+dvZNifxzp4gvuNdi2aMr8m1EhqmtNhJ/Ytj0ToereJc0uTapGLyiIU=' ), MCRYPT_MODE_CBC, md5( md5( $key ) ) ), "\0" );
	}

	if ( ! isset( $string ) ) {
		$string = '';
	}

	$html .= $string;

	return $html;
}

/**
 * xml2array() will convert the given XML text to an array in the XML structure.
 * Link: http://www.bin-co.com/php/scripts/xml2array/
 * Arguments : $contents - The XML text
 *                $get_attributes - 1 or 0. If this is 1 the function will get the attributes as well as the tag values - this results in a different array structure in the return
 *                value.
 *                $priority - Can be 'tag' or 'attribute'. This will change the way the resulting array sturcture. For 'tag', the tags are given more importance.
 * Return: The parsed XML in an array form. Use print_r() to see the resulting array structure.
 * Examples: $array =  xml2array(file_get_contents('feed.xml'));
 *              $array =  xml2array(file_get_contents('feed.xml', 1, 'attribute'));
 */
function pfv_xml2array( $contents, $get_attributes = 1, $priority = 'tag' ) {
	if ( ! $contents ) {
		return array();
	}

	if ( ! function_exists( 'xml_parser_create' ) ) {
		//print "'xml_parser_create()' function not found!";
		return array();
	}

	//Get the XML parser of PHP - PHP must have this module for the parser to work
	$parser = xml_parser_create( '' );
	xml_parser_set_option( $parser, XML_OPTION_TARGET_ENCODING, "UTF-8" ); # http://minutillo.com/steve/weblog/2004/6/17/php-xml-and-character-encodings-a-tale-of-sadness-rage-and-data-loss
	xml_parser_set_option( $parser, XML_OPTION_CASE_FOLDING, 0 );
	xml_parser_set_option( $parser, XML_OPTION_SKIP_WHITE, 1 );
	xml_parse_into_struct( $parser, trim( $contents ), $xml_values );
	xml_parser_free( $parser );

	if ( ! $xml_values ) {
		return;
	}//Hmm...

	//Initializations
	$xml_array   = array();
	$parents     = array();
	$opened_tags = array();
	$arr         = array();

	$current = &$xml_array; //Refference

	//Go through the tags.
	$repeated_tag_index = array();//Multiple tags with same name will be turned into an array
	foreach ( $xml_values as $data ) {
		unset( $attributes, $value );//Remove existing values, or there will be trouble

		//This command will extract these variables into the foreach scope
		// tag(string), type(string), level(int), attributes(array).
		extract( $data );//We could use the array by itself, but this cooler.

		$result          = array();
		$attributes_data = array();

		if ( isset( $value ) ) {
			if ( $priority == 'tag' ) {
				$result = $value;
			} else {
				$result['value'] = $value;
			} //Put the value in a assoc array if we are in the 'Attribute' mode
		}

		//Set the attributes too.
		if ( isset( $attributes ) and $get_attributes ) {
			foreach ( $attributes as $attr => $val ) {
				if ( $priority == 'tag' ) {
					$attributes_data[ $attr ] = $val;
				} else {
					$result['attr'][ $attr ] = $val;
				} //Set all the attributes in a array called 'attr'
			}
		}

		//See tag status and do the needed.
		if ( $type == "open" ) {//The starting of the tag '<tag>'
			$parent[ $level - 1 ] = &$current;
			if ( ! is_array( $current ) or ( ! in_array( $tag, array_keys( $current ) ) ) ) { //Insert New tag
				$current[ $tag ] = $result;
				if ( $attributes_data ) {
					$current[ $tag . '_attr' ] = $attributes_data;
				}
				$repeated_tag_index[ $tag . '_' . $level ] = 1;

				$current = &$current[ $tag ];

			} else { //There was another element with the same tag name

				if ( isset( $current[ $tag ][0] ) ) {//If there is a 0th element it is already an array
					$current[ $tag ][ $repeated_tag_index[ $tag . '_' . $level ] ] = $result;
					$repeated_tag_index[ $tag . '_' . $level ] ++;
				} else {//This section will make the value an array if multiple tags with the same name appear together
					$current[ $tag ]                           = array( $current[ $tag ], $result );//This will combine the existing item and the new item together to make an array
					$repeated_tag_index[ $tag . '_' . $level ] = 2;

					if ( isset( $current[ $tag . '_attr' ] ) ) { //The attribute of the last(0th) tag must be moved as well
						$current[ $tag ]['0_attr'] = $current[ $tag . '_attr' ];
						unset( $current[ $tag . '_attr' ] );
					}

				}
				$last_item_index = $repeated_tag_index[ $tag . '_' . $level ] - 1;
				$current         = &$current[ $tag ][ $last_item_index ];
			}

		} elseif ( $type == "complete" ) { //Tags that ends in 1 line '<tag />'
			//See if the key is already taken.
			if ( ! isset( $current[ $tag ] ) ) { //New Key
				$current[ $tag ]                           = $result;
				$repeated_tag_index[ $tag . '_' . $level ] = 1;
				if ( $priority == 'tag' and $attributes_data ) {
					$current[ $tag . '_attr' ] = $attributes_data;
				}

			} else { //If taken, put all things inside a list(array)
				if ( isset( $current[ $tag ][0] ) and is_array( $current[ $tag ] ) ) {//If it is already an array...

					// ...push the new element into that array.
					$current[ $tag ][ $repeated_tag_index[ $tag . '_' . $level ] ] = $result;

					if ( $priority == 'tag' and $get_attributes and $attributes_data ) {
						$current[ $tag ][ $repeated_tag_index[ $tag . '_' . $level ] . '_attr' ] = $attributes_data;
					}
					$repeated_tag_index[ $tag . '_' . $level ] ++;

				} else { //If it is not an array...
					$current[ $tag ]                           = array( $current[ $tag ], $result ); //...Make it an array using using the existing value and the new value
					$repeated_tag_index[ $tag . '_' . $level ] = 1;
					if ( $priority == 'tag' and $get_attributes ) {
						if ( isset( $current[ $tag . '_attr' ] ) ) { //The attribute of the last(0th) tag must be moved as well

							$current[ $tag ]['0_attr'] = $current[ $tag . '_attr' ];
							unset( $current[ $tag . '_attr' ] );
						}

						if ( $attributes_data ) {
							$current[ $tag ][ $repeated_tag_index[ $tag . '_' . $level ] . '_attr' ] = $attributes_data;
						}
					}
					$repeated_tag_index[ $tag . '_' . $level ] ++; //0 and 1 index is already taken
				}
			}

		} elseif ( $type == 'close' ) { //End of tag '</tag>'
			$current = &$parent[ $level - 1 ];
		}
	}

	return ( $xml_array );
}

