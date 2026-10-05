<?php
/** Run with: wp eval-file scripts/test-reader-txt.php (Reader active). No posts created. */
if ( ! defined( 'ABSPATH' ) || ! function_exists( 'kilka_reader_import_txt' ) ) {
	throw new RuntimeException( 'Load WordPress with Kilka Reader active.' );
}
$assert = function ( $ok, $message ) {
	if ( ! $ok ) { throw new RuntimeException( $message ); }
};
$fixture = function ( $text ) {
	$file = tempnam( sys_get_temp_dir(), 'kilka-txt-test-' );
	try { file_put_contents( $file, $text ); return kilka_reader_import_txt( $file ); }
	finally { unlink( $file ); }
};
$baseline = null;
foreach ( array( "\n", "\r\n", "\r" ) as $ending ) {
	foreach ( array( '', "\xEF\xBB\xBF" ) as $bom ) {
		$text = $bom . implode( $ending, array( '', 'Первая строка', 'Вторая строка', '', " \t", '', '  Ёжик — Straße & <script>alert(1)</script> **bold**', '', '* * *', '', 'Глава 1', '' ) );
		$r = $fixture( $text );
		$assert( ! is_wp_error( $r ), 'Valid UTF-8 rejected.' );
		if ( null === $baseline ) { $baseline = $r['content']; }
		$assert( $baseline === $r['content'], 'BOM/line-ending variants differ.' );
		$blocks = array_values( array_filter( parse_blocks( $r['content'] ), function ( $b ) { return ! empty( $b['blockName'] ); } ) );
		$assert( 4 === count( $blocks ), 'Blank-line paragraph rule failed.' );
		foreach ( $blocks as $block ) { $assert( 'core/paragraph' === $block['blockName'], 'TXT must not infer headings.' ); }
		$assert( false !== strpos( $r['content'], 'Первая строка<br>Вторая строка' ), 'Single break lost.' );
		$assert( false !== strpos( $r['content'], '<p>  Ёжик — Straße &amp; &lt;script&gt;alert(1)&lt;/script&gt; **bold**</p>' ), 'Text/escaping/leading spaces changed.' );
		$assert( 1 === count( $r['warnings'] ), 'TXT limitations notice missing.' );
	}
}
foreach ( array( '', "\xEF\xBB\xBF", " \t\r\n\xC2\xA0", "\xCF\xF0\xE8\xE2\xE5\xF2", "\xFF\xFEt\0", "text\0binary", "text\x1Bcontrol", str_repeat( 'a', kilka_reader_import_limit() + 1 ) ) as $bad ) {
	$assert( is_wp_error( $fixture( $bad ) ), 'Invalid, empty or oversized input accepted.' );
}
$r = $fixture( 'Literal &amp; &#169; &copy; <b>text</b>' );
$assert( ! is_wp_error( $r ) && false !== strpos( $r['content'], 'Literal &amp;amp; &amp;#169; &amp;copy; &lt;b&gt;text&lt;/b&gt;' ), 'Literal HTML entities changed.' );
$r = $fixture( 'Кириллица без завершающего переноса.' );
$assert( ! is_wp_error( $r ) && false !== strpos( $r['content'], 'Кириллица без завершающего переноса.</p>' ), 'Final paragraph lost.' );
echo "PASS TXT UTF-8/BOM, LF/CRLF/CR, blank lines, single breaks, literal markup, invalid encodings, controls and size limit.\n";
