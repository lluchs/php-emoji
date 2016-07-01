<?php
	$in = file_get_contents('emoji-data/emoji.json');
	$catalog = json_decode($in, true);


	#
	# build the final maps
	#

	$maps = array();

	$maps['names']		= make_mapping($catalog, 'unified', 'name');
	$maps['kaomoji']	= get_all_kaomoji($catalog);

	#fprintf(STDERR, "fix Geta Mark ()  '〓' (U+3013)\n");
	#$catalog = fix_geta_mark($catalog);

	$maps["unified_to_docomo"]	= make_mapping($catalog, 'unified', 'docomo');
	$maps["unified_to_kddi"]	= make_mapping($catalog, 'unified', 'au');
	$maps["unified_to_softbank"]	= make_mapping($catalog, 'unified', 'softbank');
	$maps["unified_to_google"]	= make_mapping($catalog, 'unified', 'google');
	$maps["unified_to_shortname"]	= make_mapping($catalog, 'unified', 'short_name');

	$maps["docomo_to_unified"]	= make_mapping($catalog, 'docomo', 'unified');
	$maps["kddi_to_unified"]	= make_mapping($catalog, 'au', 'unified');
	$maps["softbank_to_unified"]	= make_mapping($catalog, 'softbank', 'unified');
	$maps["google_to_unified"]	= make_mapping($catalog, 'google', 'unified');
	$maps["shortname_to_unified"]	= make_mapping($catalog, 'short_names', 'unified');

	$maps["unified_to_html"]	= make_html_map($catalog);


	#
	# output
	# we could just use var_dump, but we get 'better' output this way
	#

	echo "<"."?php\n";

	echo "\n";
	echo "\t#\n";
	echo "\t# WARNING:\n";
	echo "\t# This code is auto-generated. Do not modify it manually.\n";
	echo "\t#\n";
	echo "\n";

	echo "\t\$GLOBALS['emoji_maps'] = array(\n";

	echo "\t\t'names' => array(\n";

	foreach ($maps['names'] as $k => $v){

		$key_enc = format_string($k);
		$name_enc = "'".AddSlashes($v)."'";
		echo "\t\t\t$key_enc => $name_enc,\n";
	}

	echo "\t\t),\n";

	foreach ($maps as $k => $v){

		if ($k == 'names') continue;

		echo "\t\t'$k' => array(\n";

		foreach ($v as $k2 => $v2){
			echo "\t\t\t".format_string($k2).'=>'.format_string($v2).",\n";
		}

		echo "\t\t),\n";
	}

	echo "\t);\n";


	echo file_get_contents('inc.php');



	##########################################################################################

	function get_all_kaomoji($mapping){
		$arr = array();

		foreach ($mapping as $map){
			if (isset($map['docomo']['kaomoji']) ) {
				$arr[ $map['docomo']['kaomoji'] ] = '1';
			}

			if (isset($map['au']['kaomoji']) ) {
				$arr[ $map['au']['kaomoji'] ] = '1';
			}

			if (isset($map['softbank']['kaomoji']) ) {
				$arr[ $map['softbank']['kaomoji'] ] = '1';
			}
		}

		return array_keys($arr);
	}

	function make_html_map($map){

		$out = array();
		foreach ($map as $row){

			$hex = unicode_hex_chars($row['unified']);
			$bytes = unicode_bytes($row['unified']);

			$out[$bytes] = "<span class=\"emoji-outer emoji-sizer\"><span class=\"emoji-inner emoji$hex\"></span></span>";
		}

		return $out;
	}

	function is_codepoint_field($field_name){
		return in_array($field_name, array('unified', 'variations', 'docomo', 'au', 'softbank', 'google'));
	}

	function make_mapping($mapping, $src, $dest){

		$result = array();

		foreach ($mapping as $map){
			if (empty($map[$src]))
				continue;

			$src_chars = is_array($map[$src]) ? $map[$src] : array($map[$src]);

			foreach ($src_chars as $src_char){

				if (is_codepoint_field($src))
					$src_char = unicode_bytes($src_char);

				if (!empty($map[$dest])){

					$dest_char = is_codepoint_field($dest) ? unicode_bytes($map[$dest]) : $map[$dest];
				}else{
					$dest_char = '';
				}

				$result[$src_char] = $dest_char;

			}
		}

		return $result;
	}

	function unicode_bytes($str){

		$out = '';

		$cps = explode('-', $str);
		foreach ($cps as $cp){
			$out .= emoji_utf8_bytes(hexdec($cp));
		}

		return $out;
	}

	function unicode_hex_chars($str){

		$out = '';

		$cps = explode('-', $str);
		foreach ($cps as $cp){
			$out .= sprintf('%x', hexdec($cp));
		}

		return $out;
	}

	function emoji_utf8_bytes($cp){

		if ($cp > 0x10000){
			# 4 bytes
			return	chr(0xF0 | (($cp & 0x1C0000) >> 18)).
				chr(0x80 | (($cp & 0x3F000) >> 12)).
				chr(0x80 | (($cp & 0xFC0) >> 6)).
				chr(0x80 | ($cp & 0x3F));
		}else if ($cp > 0x800){
			# 3 bytes
			return	chr(0xE0 | (($cp & 0xF000) >> 12)).
				chr(0x80 | (($cp & 0xFC0) >> 6)).
				chr(0x80 | ($cp & 0x3F));
		}else if ($cp > 0x80){
			# 2 bytes
			return	chr(0xC0 | (($cp & 0x7C0) >> 6)).
				chr(0x80 | ($cp & 0x3F));
		}else{
			# 1 byte
			return chr($cp);
		}
	}

	function format_string($s){
		$out = ''; 
		for ($i=0; $i<strlen($s); $i++){
			$c = ord(substr($s,$i,1));
			if ($c >= 0x20 && $c < 0x80 && !in_array($c, array(34, 39, 92))){
				$out .= chr($c);
			}else{
				$out .= sprintf('\\x%02x', $c);
			}   
		}   
		return '"'.$out.'"';
	}   

