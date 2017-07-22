<?php
/**
 * Output regular expression alternatives globals, statics or defines (default globals)
 * for general category codepoints (default "Mn" nonspacing combining marks)
 * from the UCD unicode data file "UnicodeData.txt".
 *
 * See http://www.unicode.org/Public/10.0.0/ucd/UnicodeData.txt
 */

$basename = basename( __FILE__ );
$dirname = dirname( __FILE__ );
$dirdirname = dirname( $dirname );
$subdirname = basename( $dirname );

require $dirname . '/functions.php';

// Arguments.

$opts = getopt( 'p:g:cv:u:o:' );
$prefix = isset( $opts['p'] ) ? $opts['p'] : 'global'; // 'global' or 'static' or blank for defines.
$general_cats = isset( $opts['g'] ) ? explode( ',', $opts['g'] ) : array( 'Mn' ); // Comma-separated list of categories to output. Defaults to 'Mn'.
$concat = isset( $opts['c'] ); // If set will concatenation output to STDIN, with no '<?php' prefix, otherwise will output '<?php' prefix and no concatenation.
$version = isset( $opts['v'] ) ? $opts['v'] : '10.0.0'; // Unicode version number.
$unicode = isset( $opts['u'] ) ? $opts['u'] : ''; // Blank for UTF-8 regex, 'unicode' for Unicode regex, or 'both' for both.
$output = isset( $opts['o'] ) ? $opts['o'] : 'regex_alts'; // 'regex_alts' for regex alternatives sub-expression, 'regex' for full regex expression, or 'both' for both.

if ( ! function_exists( '__' ) ) {
	function __( $str, $td ) { return $str; }
}

// Open the unicode file.

$filename = '/tests/UCD-' . $version . '/UnicodeData.txt';
$file = $dirdirname . $filename;
error_log( "$basename: reading file=$file" );

// Callback for Unicode data file parser.
function parse_unicode_data_cb( &$codepoints, $cp, $name, $parts, $in_interval, $first_cp, $last_cp ) {
	global $general_cats;

	$general_cat = $parts[UNFC_UCD_GENERAL_CATEGORY];
	$general_cat_super = strlen( $general_cat ) > 1 ? $general_cat[0] : null;
	if ( $general_cats && ! in_array( $general_cat, $general_cats ) && ( ! $general_cat_super || ! in_array( $general_cat_super, $general_cats ) ) ) {
		return;
	}
	if ( ! isset( $codepoints[ $general_cat ] ) ) {
		$codepoints[ $general_cat ] = array();
	}
	if ( $general_cat_super && ! isset( $codepoints[ $general_cat_super ] ) ) {
		$codepoints[ $general_cat_super ] = array();
	}
	$codepoints[ $general_cat ][] = $cp;
	if ( $general_cat_super ) {
		$codepoints[ $general_cat_super ][] = $cp;
	}
}

// Read the file.

$codepoints = unfc_parse_unicode_data( $file, 'parse_unicode_data_cb' );
if ( false === $codepoints ) {
	/* translators: %s: file name */
	$error = sprintf( __( 'Could not read unicode data file "%s"', 'unfc' ), $file );
	error_log( $error );
	return $error;
}

ksort( $codepoints );

// Output.

$indent = 'static' === $prefix ? "\t\t\t" : '';
$out = array();
$out[] =  $concat ? '' : '<?php';
$out[] = $indent . '// Generated by "' . $basename . '" from "http://www.unicode.org/Public/' . $version . '/ucd/UnicodeData.txt".';

if ( 'static' === $prefix || 'global' === $prefix ) {
	$preface = $indent . ( 'static' === $prefix ? 'static ' : '' ) . '$';
} else {
	$preface = "define( '{$prefix}";
}

foreach ( $codepoints as $general_cat => $general_cat_cps ) {
	if ( $general_cats && ! in_array( $general_cat, $general_cats ) ) {
		continue;
	}
	sort( $general_cat_cps );

	$num_cps_comment = " // " . count( $general_cat_cps ) . " code points.";
	$both_num_cps_comment = 'both' === $output ? '' : $num_cps_comment;

	$lc_general_cat = strtolower( $general_cat );
	$uc_general_cat = strtoupper( $general_cat );

	if ( '' === $unicode || 'both' === $unicode ) {
		// Calculate the UTF-8 byte sequence ranges from the unicode codepoints.
		$ranges = unfc_utf8_ranges_from_codepoints( $general_cat_cps );

		// Generate the regular expression alternatives.
		$regex_alts = unfc_utf8_regex_alts( $ranges );

		if ( 'static' === $prefix || 'global' === $prefix ) {
			if ( 'regex_alts' === $output || 'both' === $output ) {
				$out[] = "{$preface}{$lc_general_cat}_regex_alts = '{$regex_alts}';{$num_cps_comment}";
			}
			if ( 'regex' === $output || 'both' === $output ) {
				$out[] = "{$preface}{$lc_general_cat}_regex = '/" . ( 'both' === $output ? "' . \$regex_alts . '" : $regex_alts ) . "/';{$both_num_cps_comment}";
			}
		} else {
			if ( 'regex_alts' === $output || 'both' === $output ) {
				$out[] = "{$preface}{$uc_general_cat}_REGEX_ALTS', '{$regex_alts}' );{$num_cps_comment}";
			}
			if ( 'regex' === $output || 'both' === $output ) {
				$out[] = "{$preface}{$uc_general_cat}_REGEX', '/" . ( 'both' === $output ? "' . {$prefix}{$uc_general_cat}_REGEX_ALTS . " : $regex_alts ) . "/' );{$both_num_cps_comment}";
			}
		}
	}
	if ( 'unicode' === $unicode || 'both' === $unicode ) {
		// Generate the regular expression alternatives.
		$regex_alts = unfc_unicode_regex_chars_from_codepoints( $general_cat_cps );

		if ( 'static' === $prefix || 'global' === $prefix ) {
			if ( 'regex_alts' === $output || 'both' === $output ) {
				$out[] = "{$preface}{$lc_general_cat}_regex_alts_u = '{$regex_alts}';{$num_cps_comment}";
			}
			if ( 'regex' === $output || 'both' === $output ) {
				$out[] = "{$preface}{$lc_general_cat}_regex_u = '/[" . ( 'both' === $output ? "' . \$regex_alts_u . '" : $regex_alts ) . "]/u';{$both_num_cps_comment}";
			}
		} else {
			if ( 'regex_alts' === $output || 'both' === $output ) {
				$out[] = "{$preface}{$uc_general_cat}_REGEX_ALTS_U', '{$regex_alts}' );{$num_cps_comment}";
			}
			if ( 'regex' === $output || 'both' === $output ) {
				$out[] = "{$preface}{$uc_general_cat}_REGEX_U', '/[" . ( 'both' === $output ? "' . {$prefix}{$uc_general_cat}_REGEX_ALTS_U . " : $regex_alts ) . "]/u' );{$both_num_cps_comment}";
			}
		}
	}
}

$out = implode( "\n", $out ) . "\n";

if ( $concat ) echo stream_get_contents( STDIN );
echo $out;
