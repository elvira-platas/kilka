<?php
/** Conservative, server-local document import into a new reading draft. */
if ( ! defined( 'ABSPATH' ) ) { exit; }

function kilka_reader_import_limit() {
	return min( 2 * MB_IN_BYTES, wp_max_upload_size() );
}
function kilka_reader_import_error( $message ) {
	return new WP_Error( 'kilka_reader_import', $message );
}
function kilka_reader_docx_xml( $xml ) {
	if ( ! is_string( $xml ) || '' === $xml ) {
		return kilka_reader_import_error( __( 'The DOCX is missing a required XML part.', 'kilka-reader' ) );
	}
	$previous = libxml_use_internal_errors( true );
	$doc = new DOMDocument();
	$doc->resolveExternals = false;
	$doc->substituteEntities = false;
	$ok = $doc->loadXML( $xml, LIBXML_NONET );
	libxml_clear_errors();
	libxml_use_internal_errors( $previous );
	if ( ! $ok || $doc->doctype ) {
		return kilka_reader_import_error( __( 'The DOCX contains invalid or unsupported XML.', 'kilka-reader' ) );
	}
	return $doc;
}
function kilka_reader_docx_xpath( $doc ) {
	$path = new DOMXPath( $doc );
	$path->registerNamespace( 'w', 'http://schemas.openxmlformats.org/wordprocessingml/2006/main' );
	return $path;
}
function kilka_reader_docx_value( $node, $name = 'val' ) {
	return $node ? $node->getAttributeNS( 'http://schemas.openxmlformats.org/wordprocessingml/2006/main', $name ) : '';
}
function kilka_reader_docx_flags( $path, $properties, $flags, $toggle = false ) {
	if ( ! $properties ) { return $flags; }
	foreach ( array( 'b', 'i' ) as $key ) {
		$node = $path->query( 'w:' . $key, $properties )->item( 0 );
		if ( $node ) {
			$enabled = ! in_array( strtolower( kilka_reader_docx_value( $node ) ), array( '0', 'false', 'off' ), true );
			$flags[ $key ] = $toggle ? ( $enabled ? ! $flags[ $key ] : $flags[ $key ] ) : $enabled;
		}
	}
	return $flags;
}
function kilka_reader_docx_supported_properties( $path, $properties ) {
	if ( ! $properties ) { return true; }
	// Preserve the promised emphasis only; do not silently discard semantic styling.
	foreach ( array( 'b', 'i' ) as $key ) {
		$complex = $path->query( 'w:' . $key . 'Cs', $properties )->item( 0 );
		$regular = $path->query( 'w:' . $key, $properties )->item( 0 );
		if ( $complex && ( ! $regular || kilka_reader_docx_value( $complex ) !== kilka_reader_docx_value( $regular ) ) ) {
			return kilka_reader_import_error( __( 'Separate complex-script emphasis is not supported yet. No draft was created.', 'kilka-reader' ) );
		}
	}
	$allowed = array( 'rStyle', 'rFonts', 'b', 'i', 'bCs', 'iCs', 'color', 'sz', 'szCs', 'lang', 'kern', 'spacing', 'w', 'hint', 'noProof' );
	foreach ( $properties->childNodes as $property ) {
		if ( $property instanceof DOMElement && ! in_array( $property->localName, $allowed, true ) ) {
			return kilka_reader_import_error( __( 'This document uses text formatting beyond bold and italic (for example underline, strikethrough or superscript). No draft was created.', 'kilka-reader' ) );
		}
	}
	return true;
}
function kilka_reader_docx_style( $id, $styles, $path, $seen = array() ) {
	$result = array( 'chain' => array(), 'level' => null, 'list' => false );
	if ( '' === $id ) { return $result; }
	if ( ! isset( $styles[ $id ] ) || isset( $seen[ $id ] ) || count( $seen ) > 32 ) {
		return kilka_reader_import_error( __( 'The DOCX has an unknown or circular text style. Please save a standard DOCX copy.', 'kilka-reader' ) );
	}
	$seen[ $id ] = true;
	$node = $styles[ $id ];
	$parent = kilka_reader_docx_value( $path->query( 'w:basedOn', $node )->item( 0 ) );
	$result = kilka_reader_docx_style( $parent, $styles, $path, $seen );
	if ( is_wp_error( $result ) ) { return $result; }
	$check = kilka_reader_docx_supported_properties( $path, $path->query( 'w:rPr', $node )->item( 0 ) );
	if ( is_wp_error( $check ) ) { return $check; }
	$result['chain'][] = $node;
	$outline = $path->query( 'w:pPr/w:outlineLvl', $node )->item( 0 );
	$name = strtolower( kilka_reader_docx_value( $path->query( 'w:name', $node )->item( 0 ) ) );
	if ( $outline ) { $result['level'] = (int) kilka_reader_docx_value( $outline ); }
	elseif ( preg_match( '/^heading\s*([1-9])$/i', $id, $match ) || preg_match( '/^heading\s*([1-9])$/i', $name, $match ) ) { $result['level'] = (int) $match[1] - 1; }
	if ( $path->query( 'w:pPr/w:numPr', $node )->length ) { $result['list'] = true; }
	return $result;
}

function kilka_reader_import_docx( $file ) {
	if ( ! class_exists( 'ZipArchive' ) || ! class_exists( 'DOMDocument' ) ) {
		return kilka_reader_import_error( __( 'DOCX import requires the PHP ZIP and XML extensions on this server.', 'kilka-reader' ) );
	}
	if ( ! is_file( $file ) || filesize( $file ) > kilka_reader_import_limit() ) {
		return kilka_reader_import_error( __( 'The file exceeds the DOCX upload limit.', 'kilka-reader' ) );
	}
	$zip = new ZipArchive();
	if ( true !== $zip->open( $file, ZipArchive::RDONLY ) ) {
		return kilka_reader_import_error( __( 'This is not a readable DOCX file. Password-protected documents are not supported.', 'kilka-reader' ) );
	}
	try {
		$total = 0;
		$names = array();
		if ( $zip->numFiles > 512 ) { return kilka_reader_import_error( __( 'The DOCX package is too complex.', 'kilka-reader' ) ); }
		for ( $i = 0; $i < $zip->numFiles; $i++ ) {
			$stat = $zip->statIndex( $i );
			if ( ! $stat ) { return kilka_reader_import_error( __( 'The DOCX package is damaged.', 'kilka-reader' ) ); }
			$name = $stat['name'];
			$total += $stat['size'];
			if ( $total > 20 * MB_IN_BYTES || $stat['size'] > 2 * MB_IN_BYTES || isset( $names[ $name ] ) || preg_match( '~(^/|\\\\|(^|/)\.\.(/|$)|\.bin$)~i', $name ) || ! empty( $stat['encryption_method'] ) ) {
				return kilka_reader_import_error( __( 'The DOCX package is too large or uses unsupported features.', 'kilka-reader' ) );
			}
			$names[ $name ] = true;
		}
		$types = kilka_reader_docx_xml( $zip->getFromName( '[Content_Types].xml' ) );
		if ( is_wp_error( $types ) ) { return $types; }
		$valid = false;
		foreach ( $types->getElementsByTagName( 'Override' ) as $part ) {
			if ( '/word/document.xml' === $part->getAttribute( 'PartName' ) && 'application/vnd.openxmlformats-officedocument.wordprocessingml.document.main+xml' === $part->getAttribute( 'ContentType' ) ) { $valid = true; }
		}
		if ( ! $valid ) { return kilka_reader_import_error( __( 'Please use a standard DOCX document without macros.', 'kilka-reader' ) ); }
		$doc = kilka_reader_docx_xml( $zip->getFromName( 'word/document.xml' ) );
		if ( is_wp_error( $doc ) ) { return $doc; }
		$style_doc = isset( $names['word/styles.xml'] ) ? kilka_reader_docx_xml( $zip->getFromName( 'word/styles.xml' ) ) : kilka_reader_docx_xml( '<w:styles xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main"/>' );
		if ( is_wp_error( $style_doc ) ) { return $style_doc; }
	} finally {
		$zip->close();
	}
	$path = kilka_reader_docx_xpath( $doc );
	$sp = kilka_reader_docx_xpath( $style_doc );
	$body = $path->query( '/w:document/w:body' )->item( 0 );
	if ( ! $body ) { return kilka_reader_import_error( __( 'This DOCX XML format is not supported.', 'kilka-reader' ) ); }
	// Reject semantic content rather than silently returning a shortened work.
	$unsupported = array(
		'tbl' => __( 'tables', 'kilka-reader' ), 'drawing' => __( 'images', 'kilka-reader' ), 'pict' => __( 'images', 'kilka-reader' ),
		'footnoteReference' => __( 'footnotes', 'kilka-reader' ), 'endnoteReference' => __( 'endnotes', 'kilka-reader' ),
		'hyperlink' => __( 'links', 'kilka-reader' ), 'numPr' => __( 'lists', 'kilka-reader' ),
		'pPrChange' => __( 'tracked changes', 'kilka-reader' ), 'rPrChange' => __( 'tracked changes', 'kilka-reader' ), 'sectPrChange' => __( 'tracked changes', 'kilka-reader' ),
		'ins' => __( 'tracked changes', 'kilka-reader' ), 'del' => __( 'tracked changes', 'kilka-reader' ),
		'moveFrom' => __( 'tracked changes', 'kilka-reader' ), 'moveTo' => __( 'tracked changes', 'kilka-reader' ),
		'commentRangeStart' => __( 'comments', 'kilka-reader' ), 'commentReference' => __( 'comments', 'kilka-reader' ),
		'fldChar' => __( 'fields', 'kilka-reader' ), 'fldSimple' => __( 'fields', 'kilka-reader' ),
		'sdt' => __( 'content controls', 'kilka-reader' ), 'altChunk' => __( 'embedded content', 'kilka-reader' ),
	);
	$issues = array();
	foreach ( $unsupported as $element => $label ) {
		if ( $path->query( './/w:' . $element, $body )->length ) { $issues[] = $label; }
	}
	if ( $issues ) {
		return kilka_reader_import_error( sprintf( __( 'Import stopped. This document contains unsupported content: %s. No draft was created.', 'kilka-reader' ), implode( ', ', array_unique( $issues ) ) ) );
	}
	$styles = array();
	$default_style = '';
	foreach ( $sp->query( '/w:styles/w:style' ) as $style ) {
		$id = $style->getAttributeNS( 'http://schemas.openxmlformats.org/wordprocessingml/2006/main', 'styleId' );
		$styles[ $id ] = $style;
		if ( 'paragraph' === $style->getAttributeNS( 'http://schemas.openxmlformats.org/wordprocessingml/2006/main', 'type' ) && '1' === $style->getAttributeNS( 'http://schemas.openxmlformats.org/wordprocessingml/2006/main', 'default' ) ) { $default_style = $id; }
	}
	$check = kilka_reader_docx_supported_properties( $sp, $sp->query( '/w:styles/w:docDefaults/w:rPrDefault/w:rPr' )->item( 0 ) );
	if ( is_wp_error( $check ) ) { return $check; }
	$defaults = kilka_reader_docx_flags( $sp, $sp->query( '/w:styles/w:docDefaults/w:rPrDefault/w:rPr' )->item( 0 ), array( 'b' => false, 'i' => false ) );
	$blocks = array();
	$warnings = array();
	if ( $path->query( './/w:headerReference | .//w:footerReference', $body )->length ) { $warnings[] = __( 'Headers, footers and their page numbers were not imported.', 'kilka-reader' ); }
	if ( $path->query( './/w:p/w:pPr/w:sectPr | .//w:pageBreakBefore | .//w:br[@w:type="page" or @w:type="column"]', $body )->length ) { $warnings[] = __( 'Page and section breaks were omitted; the reader creates pages for each screen.', 'kilka-reader' ); }
	foreach ( $body->childNodes as $paragraph ) {
		if ( ! $paragraph instanceof DOMElement ) { continue; }
		if ( 'sectPr' === $paragraph->localName ) { continue; }
		if ( 'p' !== $paragraph->localName || $paragraph->namespaceURI !== $body->namespaceURI ) { return kilka_reader_import_error( __( 'The document contains unsupported structure. No draft was created.', 'kilka-reader' ) ); }
		$id = kilka_reader_docx_value( $path->query( 'w:pPr/w:pStyle', $paragraph )->item( 0 ) );
		$style = kilka_reader_docx_style( $id ?: $default_style, $styles, $sp );
		if ( is_wp_error( $style ) ) { return $style; }
		$outline = $path->query( 'w:pPr/w:outlineLvl', $paragraph )->item( 0 );
		$level = $outline ? (int) kilka_reader_docx_value( $outline ) : $style['level'];
		if ( $style['list'] || ( null !== $level && ! in_array( $level, array( 0, 1, 9 ), true ) ) ) { return kilka_reader_import_error( __( 'Lists and heading levels deeper than Heading 2 are not supported yet. No draft was created.', 'kilka-reader' ) ); }
		$flags = $defaults;
		foreach ( $style['chain'] as $node ) { $flags = kilka_reader_docx_flags( $sp, $sp->query( 'w:rPr', $node )->item( 0 ), $flags, true ); }
		$html = '';
		foreach ( $paragraph->childNodes as $run ) {
			if ( ! $run instanceof DOMElement ) { continue; }
			if ( in_array( $run->localName, array( 'pPr', 'bookmarkStart', 'bookmarkEnd', 'proofErr' ), true ) ) { continue; }
			if ( 'r' !== $run->localName || $run->namespaceURI !== $body->namespaceURI ) { return kilka_reader_import_error( __( 'The document contains unsupported inline content. No draft was created.', 'kilka-reader' ) ); }
			$run_flags = $flags;
			$character = kilka_reader_docx_value( $path->query( 'w:rPr/w:rStyle', $run )->item( 0 ) );
			if ( $character ) {
				$cs = kilka_reader_docx_style( $character, $styles, $sp );
				if ( is_wp_error( $cs ) ) { return $cs; }
				foreach ( $cs['chain'] as $node ) { $run_flags = kilka_reader_docx_flags( $sp, $sp->query( 'w:rPr', $node )->item( 0 ), $run_flags, true ); }
			}
			$check = kilka_reader_docx_supported_properties( $path, $path->query( 'w:rPr', $run )->item( 0 ) );
			if ( is_wp_error( $check ) ) { return $check; }
			$run_flags = kilka_reader_docx_flags( $path, $path->query( 'w:rPr', $run )->item( 0 ), $run_flags );
			$fragment = '';
			foreach ( $run->childNodes as $part ) {
				if ( ! $part instanceof DOMElement ) { continue; }
				if ( $part->namespaceURI !== $body->namespaceURI ) { return kilka_reader_import_error( __( 'The document contains unsupported inline content. No draft was created.', 'kilka-reader' ) ); }
				if ( 't' === $part->localName ) { $fragment .= esc_html( $part->textContent ); }
				elseif ( 'br' === $part->localName ) {
					$type = kilka_reader_docx_value( $part, 'type' );
					if ( '' === $type || 'textWrapping' === $type ) { $fragment .= '<br>'; }
					elseif ( ! in_array( $type, array( 'page', 'column' ), true ) ) { return kilka_reader_import_error( __( 'An unsupported line break was found.', 'kilka-reader' ) ); }
				} elseif ( 'cr' === $part->localName ) { $fragment .= '<br>'; }
				elseif ( 'noBreakHyphen' === $part->localName ) { $fragment .= '&#8209;'; }
				elseif ( 'softHyphen' === $part->localName ) { $fragment .= '&#173;'; }
				elseif ( ! in_array( $part->localName, array( 'rPr', 'lastRenderedPageBreak' ), true ) ) { return kilka_reader_import_error( __( 'The document contains unsupported characters or inline elements. No draft was created.', 'kilka-reader' ) ); }
			}
			if ( $run_flags['i'] && '' !== $fragment ) { $fragment = '<em>' . $fragment . '</em>'; }
			if ( $run_flags['b'] && '' !== $fragment ) { $fragment = '<strong>' . $fragment . '</strong>'; }
			$html .= $fragment;
		}
		if ( '' === trim( wp_strip_all_tags( $html ) ) ) {
			if ( ! $html ) { $blocks[] = "<!-- wp:paragraph -->\n<p></p>\n<!-- /wp:paragraph -->"; }
			else { $blocks[] = "<!-- wp:paragraph -->\n<p>" . $html . "</p>\n<!-- /wp:paragraph -->"; }
		} elseif ( 0 === $level || 1 === $level ) {
			$heading = $level + 2;
			$attrs = 2 === $heading ? '' : ' {"level":3}';
			$blocks[] = '<!-- wp:heading' . $attrs . " -->\n<h" . $heading . ' class="wp-block-heading">' . $html . '</h' . $heading . ">\n<!-- /wp:heading -->";
		} else { $blocks[] = "<!-- wp:paragraph -->\n<p>" . $html . "</p>\n<!-- /wp:paragraph -->"; }
	}
	$content = implode( "\n\n", $blocks );
	if ( '' === trim( wp_strip_all_tags( $content ) ) ) { return kilka_reader_import_error( __( 'The document has no readable text.', 'kilka-reader' ) ); }
	return array( 'content' => $content, 'warnings' => array_unique( $warnings ) );
}

/** Plain UTF-8 text: blank lines separate paragraphs; single breaks stay inside. */
function kilka_reader_import_txt( $file ) {
	if ( ! is_file( $file ) || filesize( $file ) > kilka_reader_import_limit() ) {
		return kilka_reader_import_error( __( 'The file exceeds the upload limit.', 'kilka-reader' ) );
	}
	$text = file_get_contents( $file );
	if ( false === $text ) {
		return kilka_reader_import_error( __( 'The text file could not be read.', 'kilka-reader' ) );
	}
	if ( 1 !== preg_match( '//u', $text ) ) {
		return kilka_reader_import_error( __( 'This TXT file is not valid UTF-8. Save a UTF-8 copy in your text editor and try again. No draft was created.', 'kilka-reader' ) );
	}
	if ( 0 === strpos( $text, "\xEF\xBB\xBF" ) ) { $text = substr( $text, 3 ); }
	if ( preg_match( '/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', $text ) ) {
		return kilka_reader_import_error( __( 'This file contains unsupported control characters. Please use a plain UTF-8 TXT file. No draft was created.', 'kilka-reader' ) );
	}
	if ( ! preg_match( '/[^\s\p{Z}]/u', $text ) ) {
		return kilka_reader_import_error( __( 'The document has no readable text.', 'kilka-reader' ) );
	}
	$text = str_replace( array( "\r\n", "\r" ), "\n", $text );
	// Remove only empty boundary lines, not spaces belonging to the text itself.
	$text = preg_replace( '/\A(?:[ \t]*\n)+|(?:\n[ \t]*)+\z/', '', $text );
	$paragraphs = preg_split( '/\n(?:[ \t]*\n)+/', $text );
	$blocks = array();
	foreach ( $paragraphs as $paragraph ) {
		$html = str_replace( "\n", '<br>', htmlspecialchars( $paragraph, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8' ) );
		$blocks[] = "<!-- wp:paragraph -->\n<p>" . $html . "</p>\n<!-- /wp:paragraph -->";
	}
	return array(
		'content' => implode( "\n\n", $blocks ),
		'warnings' => array( __( 'TXT has no formatting or chapter styles. Single line breaks were preserved. Review the text and mark chapter headings in the editor if needed.', 'kilka-reader' ) ),
	);
}

function kilka_reader_import_form() {
	$limit = size_format( kilka_reader_import_limit() );
	echo '<details><summary>' . esc_html__( 'Import DOCX or TXT', 'kilka-reader' ) . '</summary>';
	echo '<p>' . esc_html__( 'Upload a story as a new draft. DOCX preserves paragraphs, bold, italic and Heading 1/2. Fonts and page layout are not imported. Review the text before publishing.', 'kilka-reader' ) . '</p>';
	echo '<p>' . esc_html__( 'DOCX: this first version does not accept images, tables, lists, links, notes or tracked changes. Use a text-only DOCX copy.', 'kilka-reader' ) . '</p>';
	echo '<p>' . esc_html__( 'TXT must use UTF-8. Blank lines separate paragraphs; single line breaks are preserved. Formatting and chapter headings can be added in the editor. Line breaks copied from a PDF will not be joined automatically.', 'kilka-reader' ) . '</p>';
	echo '<form method="post" enctype="multipart/form-data" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '"><input type="hidden" name="action" value="kilka_reader_import_docx">';
	wp_nonce_field( 'kilka_reader_import_docx' );
	echo '<p><label for="kilka-import-title">' . esc_html__( 'Document title (optional)', 'kilka-reader' ) . '</label><br><input class="regular-text" id="kilka-import-title" name="document_title" type="text" maxlength="200"></p>';
	echo '<p><label for="kilka-import-file">' . esc_html( sprintf( __( 'DOCX or UTF-8 TXT file — maximum %s', 'kilka-reader' ), $limit ) ) . '</label><br><input id="kilka-import-file" type="file" name="document_file" accept=".docx,.txt,application/vnd.openxmlformats-officedocument.wordprocessingml.document,text/plain" required></p>';
	submit_button( __( 'Import as draft', 'kilka-reader' ), 'secondary', 'submit', false );
	echo '</form></details>';
}
add_action( 'admin_post_kilka_reader_import_docx', function () {
	if ( ! current_user_can( 'edit_pages' ) ) { wp_die( esc_html__( 'You cannot create reading documents.', 'kilka-reader' ), '', array( 'response' => 403 ) ); }
	check_admin_referer( 'kilka_reader_import_docx' );
	$file = isset( $_FILES['document_file'] ) ? $_FILES['document_file'] : array();
	if ( ! isset( $file['error'], $file['name'], $file['tmp_name'] ) || ! is_scalar( $file['error'] ) || UPLOAD_ERR_OK !== (int) $file['error'] || ! is_string( $file['name'] ) || ! is_string( $file['tmp_name'] ) || ! is_uploaded_file( $file['tmp_name'] ) ) {
		wp_die( esc_html__( 'No file was received. Check the upload limit and choose a DOCX or UTF-8 TXT file again.', 'kilka-reader' ), '', array( 'back_link' => true ) );
	}
	try {
		$extension = strtolower( pathinfo( $file['name'], PATHINFO_EXTENSION ) );
		if ( 'docx' === $extension ) { $result = kilka_reader_import_docx( $file['tmp_name'] ); }
		elseif ( 'txt' === $extension ) { $result = kilka_reader_import_txt( $file['tmp_name'] ); }
		else { $result = kilka_reader_import_error( __( 'Choose a DOCX or UTF-8 TXT file. Other formats are not supported yet.', 'kilka-reader' ) ); }
	} finally {
		// PHP upload storage only: never copy the original into public uploads.
		unlink( $file['tmp_name'] );
	}
	if ( is_wp_error( $result ) ) { wp_die( esc_html( $result->get_error_message() ), '', array( 'back_link' => true ) ); }
	$title = isset( $_POST['document_title'] ) && is_string( $_POST['document_title'] ) ? sanitize_text_field( wp_unslash( $_POST['document_title'] ) ) : '';
	$id = wp_insert_post( wp_slash( array( 'post_type' => 'page', 'post_status' => 'draft', 'post_title' => $title ?: __( 'Imported reading document', 'kilka-reader' ), 'post_content' => $result['content'], 'comment_status' => 'closed', 'ping_status' => 'closed', 'meta_input' => array( '_wp_page_template' => KILKA_READER_TEMPLATE ) ) ), true );
	if ( is_wp_error( $id ) ) { wp_die( esc_html( $id->get_error_message() ), '', array( 'back_link' => true ) ); }
	set_transient( 'kilka_reader_import_' . get_current_user_id() . '_' . $id, $result['warnings'], HOUR_IN_SECONDS );
	wp_safe_redirect( add_query_arg( 'kilka_imported', 1, get_edit_post_link( $id, 'raw' ) ) );
	exit;
} );
add_action( 'admin_notices', function () {
	if ( get_current_screen() && get_current_screen()->is_block_editor() ) { return; }
	if ( empty( $_GET['kilka_imported'] ) || empty( $_GET['post'] ) || ! is_scalar( $_GET['post'] ) ) { return; }
	$id = absint( $_GET['post'] );
	if ( ! current_user_can( 'edit_post', $id ) ) { return; }
	$key = 'kilka_reader_import_' . get_current_user_id() . '_' . $id;
	$warnings = get_transient( $key );
	if ( false === $warnings ) { return; }
	delete_transient( $key );
	echo '<div class="notice notice-success"><p>' . esc_html__( 'Document imported as a draft. Review the text and headings before publishing. Set the opening image separately in Reader settings.', 'kilka-reader' ) . ' <a href="' . esc_url( kilka_reader_admin_url( $id ) ) . '">' . esc_html__( 'Reader settings', 'kilka-reader' ) . '</a></p></div>';
	foreach ( $warnings as $warning ) { echo '<div class="notice notice-warning"><p>' . esc_html( $warning ) . '</p></div>'; }
} );

// Gutenberg has its own notice area; classic admin_notices are not reliably visible there.
add_action( 'enqueue_block_editor_assets', function () {
	if ( empty( $_GET['kilka_imported'] ) || empty( $_GET['post'] ) || ! is_scalar( $_GET['post'] ) ) { return; }
	$id = absint( $_GET['post'] );
	if ( ! current_user_can( 'edit_post', $id ) ) { return; }
	$key = 'kilka_reader_import_' . get_current_user_id() . '_' . $id;
	$warnings = get_transient( $key );
	if ( false === $warnings ) { return; }
	delete_transient( $key );
	$messages = array( array( 'status' => 'success', 'text' => __( 'Document imported as a draft. Review the text and headings before publishing. Set the opening image separately in Reader settings.', 'kilka-reader' ) ) );
	foreach ( $warnings as $warning ) { $messages[] = array( 'status' => 'warning', 'text' => $warning ); }
	wp_add_inline_script( 'wp-edit-post', 'wp.domReady(function(){' . wp_json_encode( $messages ) . '.forEach(function(message,index){wp.data.dispatch("core/notices").createNotice(message.status,message.text,{id:"kilka-document-import-"+index,isDismissible:true});});});' );
} );
