<?php
/** Run with: wp eval-file scripts/test-reader-docx.php (Reader active). No posts created. */
if ( ! defined( 'ABSPATH' ) || ! function_exists( 'kilka_reader_import_docx' ) ) {
	throw new RuntimeException( 'Load WordPress with Kilka Reader active.' );
}
$assert = function ( $ok, $message ) {
	if ( ! $ok ) { throw new RuntimeException( $message ); }
};
$fixture = function ( $body, $styles = '', $prefix = '', $extra = array() ) {
	$file = tempnam( sys_get_temp_dir(), 'kilka-docx-test-' );
	$zip = new ZipArchive();
	$zip->open( $file, ZipArchive::OVERWRITE );
	$zip->addFromString( '[Content_Types].xml', '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Override PartName="/word/document.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.document.main+xml"/></Types>' );
	$zip->addFromString( 'word/document.xml', $prefix . '<w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main"><w:body>' . $body . '</w:body></w:document>' );
	if ( $styles ) { $zip->addFromString( 'word/styles.xml', '<w:styles xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main">' . $styles . '</w:styles>' ); }
	foreach ( $extra as $name => $content ) { $zip->addFromString( $name, $content ); }
	$zip->close();
	try { return kilka_reader_import_docx( $file ); }
	finally { unlink( $file ); }
};
$styles = '<w:style w:type="paragraph" w:styleId="Base"><w:name w:val="Локальный заголовок"/><w:pPr><w:outlineLvl w:val="0"/></w:pPr></w:style><w:style w:type="paragraph" w:styleId="Chapter"><w:basedOn w:val="Base"/></w:style><w:style w:type="character" w:styleId="Emphasis"><w:rPr><w:i/></w:rPr></w:style>';
$r = $fixture( '<w:p><w:pPr><w:pStyle w:val="Chapter"/></w:pPr><w:r><w:t>Глава</w:t></w:r></w:p><w:p><w:r><w:rPr><w:rStyle w:val="Emphasis"/><w:b/></w:rPr><w:t>Курсив и жирный</w:t><w:br/><w:t>&lt;script&gt; &amp; ё Straße</w:t></w:r></w:p>', $styles );
$assert( ! is_wp_error( $r ), 'Supported DOCX failed.' );
$assert( false !== strpos( $r['content'], '<h2 class="wp-block-heading">Глава</h2>' ), 'Inherited outline failed.' );
$assert( false !== strpos( $r['content'], '<strong><em>Курсив и жирный<br>&lt;script&gt; &amp; ё Straße</em></strong>' ), 'Emphasis, break or escaping failed.' );
$blocks = array_values( array_filter( parse_blocks( $r['content'] ), function ( $b ) { return ! empty( $b['blockName'] ); } ) );
$assert( 2 === count( $blocks ), 'Wrong block count.' );
$r = $fixture( '<w:p><w:r><w:rPr><w:b w:val="0"/></w:rPr><w:t>Plain</w:t></w:r></w:p>' );
$assert( ! is_wp_error( $r ) && false === strpos( $r['content'], '<strong>' ), 'Explicit false emphasis failed.' );
$r = $fixture( '<w:p><w:pPr><w:pStyle w:val="Heading1"/></w:pPr><w:r><w:rPr><w:b w:val="0"/></w:rPr><w:t>Plain heading</w:t></w:r></w:p>', '<w:style w:styleId="Heading1"><w:rPr><w:b/></w:rPr></w:style>' );
$assert( ! is_wp_error( $r ) && false === strpos( $r['content'], '<strong>' ), 'Direct formatting must override style.' );
$r = $fixture( '<w:p><w:r><w:t>Before</w:t><w:br w:type="page"/><w:t>After</w:t></w:r></w:p><w:sectPr><w:headerReference/></w:sectPr>' );
$assert( ! is_wp_error( $r ) && 2 === count( $r['warnings'] ), 'Layout omissions require warnings.' );
$bad = array(
	'table' => '<w:tbl/>',
	'link' => '<w:p><w:hyperlink><w:r><w:t>Keep me</w:t></w:r></w:hyperlink></w:p>',
	'footnote' => '<w:p><w:r><w:footnoteReference w:id="1"/></w:r></w:p>',
	'revision' => '<w:p><w:ins><w:r><w:t>Keep me</w:t></w:r></w:ins></w:p>',
	'formatting revision' => '<w:p><w:r><w:rPr><w:rPrChange/></w:rPr><w:t>Keep me</w:t></w:r></w:p>',
	'equation' => '<m:oMath xmlns:m="http://schemas.openxmlformats.org/officeDocument/2006/math"/>',
	'list' => '<w:p><w:pPr><w:numPr/></w:pPr><w:r><w:t>Item</w:t></w:r></w:p>',
	'deep heading' => '<w:p><w:pPr><w:outlineLvl w:val="2"/></w:pPr><w:r><w:t>Heading</w:t></w:r></w:p>',
	'tab' => '<w:p><w:r><w:tab/></w:r></w:p>',
	'strike' => '<w:p><w:r><w:rPr><w:strike/></w:rPr><w:t>Keep me</w:t></w:r></w:p>',
	'empty' => '<w:p/>',
);
foreach ( $bad as $name => $body ) {
	$assert( is_wp_error( $fixture( $body ) ), 'Did not reject ' . $name );
}
$paragraph = '<w:p><w:r><w:t>Text</w:t></w:r></w:p>';
$assert( is_wp_error( $fixture( $paragraph, '', '<!DOCTYPE x [<!ENTITY e SYSTEM "file:///etc/passwd">]>' ) ), 'DTD accepted.' );
$assert( is_wp_error( $fixture( $paragraph, '', '', array( 'word/vbaProject.bin' => 'macro' ) ) ), 'Binary package part accepted.' );
$assert( is_wp_error( $fixture( $paragraph, '', '', array( '../outside' => 'text' ) ) ), 'Traversal package path accepted.' );
$assert( is_wp_error( $fixture( $paragraph, '', '', array( 'word/oversize.xml' => str_repeat( 'a', 2 * MB_IN_BYTES + 1 ) ) ) ), 'Expanded part limit ignored.' );
$assert( is_wp_error( $fixture( '<w:p><w:pPr><w:pStyle w:val="Cycle"/></w:pPr><w:r><w:t>Text</w:t></w:r></w:p>', '<w:style w:styleId="Cycle"><w:basedOn w:val="Cycle"/></w:style>' ) ), 'Circular style accepted.' );
echo "PASS DOCX text, style inheritance, emphasis, escaping, breaks, warnings, unsupported content and package limits.\n";
