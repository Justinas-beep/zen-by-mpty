<?php
/**
 * Build and validate the Zen by MPTY WordPress.org production artifact.
 *
 * Usage:
 * php scripts/build-production.php build --source=PATH --output=PATH
 * php scripts/build-production.php validate --source=PATH --zip=PATH
 *
 * @package MPTYZen
 */

declare(strict_types=1);

const MPTY_ZEN_BUILD_SLUG = 'zen-by-mpty';

/**
 * Stop the build with a clear error.
 *
 * @param string $message Failure reason.
 * @return never
 */
function mpty_zen_build_fail( $message ) {
	fwrite( STDERR, 'Zen build failed: ' . $message . PHP_EOL );
	exit( 1 );
}

/**
 * Read long CLI options in --name=value form.
 *
 * @param string[] $arguments CLI arguments.
 * @return array<string,string>
 */
function mpty_zen_build_options( $arguments ) {
	$options = array();
	foreach ( $arguments as $argument ) {
		if ( 0 !== strpos( $argument, '--' ) || false === strpos( $argument, '=' ) ) {
			continue;
		}
		list( $name, $value ) = explode( '=', substr( $argument, 2 ), 2 );
		if ( '' === $name || '' === $value ) {
			mpty_zen_build_fail( 'Options must use --name=value with a non-empty value.' );
		}
		$options[ $name ] = $value;
	}
	return $options;
}

/**
 * Return the reviewed positive production allowlist.
 *
 * @param string $source Source repository root.
 * @return string[]
 */
function mpty_zen_build_manifest( $source ) {
	$manifest_file = $source . DIRECTORY_SEPARATOR . 'scripts' . DIRECTORY_SEPARATOR . 'production-manifest.php';
	if ( ! is_file( $manifest_file ) ) {
		mpty_zen_build_fail( 'The authoritative production manifest is missing.' );
	}
	$manifest = require $manifest_file;
	if ( ! is_array( $manifest ) || array() === $manifest ) {
		mpty_zen_build_fail( 'The production manifest must be a non-empty array.' );
	}
	$normalized = array();
	foreach ( $manifest as $path ) {
		if ( ! is_string( $path ) || ! preg_match( '#^[a-zA-Z0-9][a-zA-Z0-9._/-]*$#', $path ) || false !== strpos( $path, '..' ) || '\\' === substr( $path, -1 ) ) {
			mpty_zen_build_fail( 'The production manifest contains an unsafe path.' );
		}
		$normalized[] = str_replace( '/', DIRECTORY_SEPARATOR, $path );
	}
	if ( count( $normalized ) !== count( array_unique( $normalized ) ) ) {
		mpty_zen_build_fail( 'The production manifest contains duplicate paths.' );
	}
	sort( $normalized, SORT_STRING );
	return $normalized;
}

/**
 * Normalize and verify an existing source root.
 *
 * @param string $source Source path.
 * @return string
 */
function mpty_zen_build_source( $source ) {
	$resolved = realpath( $source );
	if ( false === $resolved || ! is_dir( $resolved ) || ! is_file( $resolved . DIRECTORY_SEPARATOR . 'zen-by-mpty.php' ) ) {
		mpty_zen_build_fail( 'Source must be the Zen repository root.' );
	}
	return rtrim( $resolved, '/\\' );
}

/**
 * Ensure an output directory is a dedicated child of the source root.
 *
 * @param string $source Source repository root.
 * @param string $output Requested output root.
 * @return string
 */
function mpty_zen_build_output( $source, $output ) {
	$output = str_replace( '/', DIRECTORY_SEPARATOR, $output );
	if ( ! preg_match( '#^(?:[A-Za-z]:[\\\\/]|/)#', $output ) ) {
		$output = $source . DIRECTORY_SEPARATOR . $output;
	}
	$output = rtrim( $output, '/\\' );
	$parent = dirname( $output );
	if ( ! is_dir( $parent ) && ! mkdir( $parent, 0777, true ) && ! is_dir( $parent ) ) {
		mpty_zen_build_fail( 'Could not create the output parent directory.' );
	}
	$parent_real = realpath( $parent );
	if ( false === $parent_real ) {
		mpty_zen_build_fail( 'Could not resolve the output parent directory.' );
	}
	$output = rtrim( $parent_real, '/\\' ) . DIRECTORY_SEPARATOR . basename( $output );
	$source_prefix = strtolower( $source . DIRECTORY_SEPARATOR );
	if ( 0 !== strpos( strtolower( $output . DIRECTORY_SEPARATOR ), $source_prefix ) || $output === $source ) {
		mpty_zen_build_fail( 'Output must be a dedicated directory inside the source repository.' );
	}
	return $output;
}

/**
 * Remove a dedicated build directory without following links.
 *
 * @param string $path Build path.
 * @return void
 */
function mpty_zen_build_remove_tree( $path ) {
	if ( is_link( $path ) || is_file( $path ) ) {
		if ( ! unlink( $path ) ) {
			mpty_zen_build_fail( 'Could not remove existing build output.' );
		}
		return;
	}
	if ( ! is_dir( $path ) ) {
		return;
	}
	$iterator = new FilesystemIterator( $path, FilesystemIterator::SKIP_DOTS );
	foreach ( $iterator as $item ) {
		mpty_zen_build_remove_tree( $item->getPathname() );
	}
	if ( ! rmdir( $path ) ) {
		mpty_zen_build_fail( 'Could not remove existing build directory.' );
	}
}

/**
 * List files below a directory using manifest-style separators.
 *
 * @param string $root Directory to inspect.
 * @return string[]
 */
function mpty_zen_build_file_list( $root ) {
	$files = array();
	$iterator = new RecursiveIteratorIterator(
		new RecursiveDirectoryIterator( $root, FilesystemIterator::SKIP_DOTS )
	);
	foreach ( $iterator as $item ) {
		if ( $item->isFile() && ! $item->isLink() ) {
			$files[] = str_replace( DIRECTORY_SEPARATOR, '/', substr( $item->getPathname(), strlen( $root ) + 1 ) );
		}
	}
	sort( $files, SORT_STRING );
	return $files;
}

/**
 * Extract and compare artifact version declarations.
 *
 * @param string $plugin_file Main plugin contents.
 * @param string $readme      Readme contents.
 * @return string
 */
function mpty_zen_build_version( $plugin_file, $readme ) {
	if ( ! preg_match( '/^\s*\*\s*Version:\s*([^\s]+)\s*$/mi', $plugin_file, $header ) ) {
		mpty_zen_build_fail( 'Plugin header version is missing.' );
	}
	if ( ! preg_match( "/define\(\s*'MPTY_ZEN_VERSION'\s*,\s*'([^']+)'\s*\)/", $plugin_file, $constant ) ) {
		mpty_zen_build_fail( 'Runtime version constant is missing.' );
	}
	if ( ! preg_match( '/^Stable tag:\s*([^\s]+)\s*$/mi', $readme, $stable ) ) {
		mpty_zen_build_fail( 'Readme Stable Tag is missing.' );
	}
	$versions = array( $header[1], $constant[1], $stable[1] );
	if ( 1 !== count( array_unique( $versions ) ) || ! preg_match( '/^\d+\.\d+\.\d+(?:-[0-9A-Za-z.-]+)?$/', $versions[0] ) ) {
		mpty_zen_build_fail( 'Plugin header, runtime constant, and Stable Tag must contain one valid matching version.' );
	}
	return $versions[0];
}

/**
 * Validate PHP parse syntax without executing shipped code.
 *
 * @param string $contents PHP source.
 * @param string $path     Relative artifact path.
 * @return void
 */
function mpty_zen_build_validate_php( $contents, $path ) {
	try {
		if ( array() === token_get_all( $contents, TOKEN_PARSE ) ) {
			mpty_zen_build_fail( 'PHP source is unexpectedly empty: ' . $path );
		}
	} catch ( ParseError $error ) {
		mpty_zen_build_fail( 'PHP syntax error in ' . $path . ': ' . $error->getMessage() );
	}
}

/**
 * Reject direct-distribution or remote behavior from the WordPress.org runtime.
 *
 * @param string $contents Runtime source.
 * @param string $path     Relative path.
 * @return void
 */
function mpty_zen_build_validate_distribution_boundary( $contents, $path ) {
	$patterns = array(
		'/\bUpdate URI\s*:/i',
		'/pre_set_site_transient_update_plugins/i',
		'/upgrader_pre_download/i',
		'/\bMPTY_Zen_Updater\b/i',
		'/\blicen[cs](?:e|ing)[_-]?(?:key|server|check)/i',
		'/\bwp_remote_(?:get|post|request)\s*\(/i',
		'/\b(?:fetch|sendBeacon)\s*\(/i',
		'/\bXMLHttpRequest\b/i',
	);
	foreach ( $patterns as $pattern ) {
		if ( preg_match( $pattern, $contents ) ) {
			mpty_zen_build_fail( 'Forbidden updater, licensing, or remote behavior found in ' . $path . '.' );
		}
	}
}

/**
 * Validate an extracted production directory.
 *
 * @param string   $root     Plugin artifact root.
 * @param string[] $manifest Manifest paths using platform separators.
 * @return string Validated version.
 */
function mpty_zen_build_validate_directory( $root, $manifest ) {
	if ( ! is_dir( $root ) ) {
		mpty_zen_build_fail( 'Production plugin directory is missing.' );
	}
	$expected = array_map(
		static function ( $path ) {
			return str_replace( DIRECTORY_SEPARATOR, '/', $path );
		},
		$manifest
	);
	if ( mpty_zen_build_file_list( $root ) !== $expected ) {
		mpty_zen_build_fail( 'Production directory does not exactly match the authoritative manifest.' );
	}
	foreach ( $manifest as $path ) {
		$absolute = $root . DIRECTORY_SEPARATOR . $path;
		if ( ! is_file( $absolute ) || is_link( $absolute ) ) {
			mpty_zen_build_fail( 'Required production file is missing or unsafe: ' . $path );
		}
		$contents = file_get_contents( $absolute );
		if ( false === $contents ) {
			mpty_zen_build_fail( 'Could not read production file: ' . $path );
		}
		$display_path = str_replace( DIRECTORY_SEPARATOR, '/', $path );
		if ( '.php' === substr( $path, -4 ) ) {
			mpty_zen_build_validate_php( $contents, $display_path );
		}
		if ( '.php' === substr( $path, -4 ) || '.js' === substr( $path, -3 ) ) {
			mpty_zen_build_validate_distribution_boundary( $contents, $display_path );
		}
	}
	foreach ( array( 'assets/js/admin.js', 'assets/js/classifier.js', 'uninstall.php' ) as $required ) {
		if ( ! in_array( str_replace( '/', DIRECTORY_SEPARATOR, $required ), $manifest, true ) ) {
			mpty_zen_build_fail( 'Manifest is missing required runtime file: ' . $required );
		}
	}
	$plugin = file_get_contents( $root . DIRECTORY_SEPARATOR . 'zen-by-mpty.php' );
	$readme = file_get_contents( $root . DIRECTORY_SEPARATOR . 'readme.txt' );
	if ( false === $plugin || false === $readme ) {
		mpty_zen_build_fail( 'Could not read version metadata.' );
	}
	return mpty_zen_build_version( $plugin, $readme );
}

/**
 * Validate the final ZIP entries, contents, layout, name, and checksum.
 *
 * @param string   $zip_path ZIP path.
 * @param string[] $manifest Manifest paths using platform separators.
 * @param string   $version  Expected plugin version.
 * @param string   $root     Extracted plugin directory.
 * @return string SHA-256 checksum.
 */
function mpty_zen_build_validate_zip( $zip_path, $manifest, $version, $root ) {
	if ( ! class_exists( 'ZipArchive' ) ) {
		mpty_zen_build_fail( 'The PHP ZIP extension is required.' );
	}
	if ( basename( $zip_path ) !== MPTY_ZEN_BUILD_SLUG . '-' . $version . '.zip' ) {
		mpty_zen_build_fail( 'ZIP filename does not match the validated plugin version.' );
	}
	$zip = new ZipArchive();
	if ( true !== $zip->open( $zip_path ) ) {
		mpty_zen_build_fail( 'Could not open the production ZIP.' );
	}
	$actual = array();
	// phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- ZipArchive defines numFiles.
	for ( $index = 0; $index < $zip->numFiles; $index++ ) {
		$name = $zip->getNameIndex( $index );
		if ( false === $name ) {
			$zip->close();
			mpty_zen_build_fail( 'Could not read a ZIP entry name.' );
		}
		$actual[] = $name;
	}
	$expected = array_map(
		static function ( $path ) {
			return MPTY_ZEN_BUILD_SLUG . '/' . str_replace( DIRECTORY_SEPARATOR, '/', $path );
		},
		$manifest
	);
	sort( $actual, SORT_STRING );
	sort( $expected, SORT_STRING );
	if ( $actual !== $expected ) {
		$zip->close();
		mpty_zen_build_fail( 'ZIP must contain exactly one zen-by-mpty top-level directory and the manifest files.' );
	}
	foreach ( $manifest as $path ) {
		$entry = MPTY_ZEN_BUILD_SLUG . '/' . str_replace( DIRECTORY_SEPARATOR, '/', $path );
		$zipped = $zip->getFromName( $entry );
		$built  = file_get_contents( $root . DIRECTORY_SEPARATOR . $path );
		if ( false === $zipped || false === $built || ! hash_equals( hash( 'sha256', $built ), hash( 'sha256', $zipped ) ) ) {
			$zip->close();
			mpty_zen_build_fail( 'ZIP contents do not match the validated build directory: ' . $entry );
		}
	}
	$zip->close();
	$checksum = hash_file( 'sha256', $zip_path );
	if ( false === $checksum || 64 !== strlen( $checksum ) ) {
		mpty_zen_build_fail( 'Could not generate a SHA-256 checksum.' );
	}
	return $checksum;
}

/**
 * Build the exact artifact and validate it before returning.
 *
 * @param string   $source   Source repository root.
 * @param string   $output   Dedicated build root.
 * @param string[] $manifest Production manifest.
 * @return void
 */
function mpty_zen_build_artifact( $source, $output, $manifest ) {
	mpty_zen_build_remove_tree( $output );
	$plugin_root = $output . DIRECTORY_SEPARATOR . MPTY_ZEN_BUILD_SLUG;
	if ( ! mkdir( $plugin_root, 0777, true ) && ! is_dir( $plugin_root ) ) {
		mpty_zen_build_fail( 'Could not create the production plugin directory.' );
	}
	foreach ( $manifest as $path ) {
		$from = $source . DIRECTORY_SEPARATOR . $path;
		$to   = $plugin_root . DIRECTORY_SEPARATOR . $path;
		if ( ! is_file( $from ) || is_link( $from ) ) {
			mpty_zen_build_fail( 'Required source file is missing or unsafe: ' . $path );
		}
		if ( ! is_dir( dirname( $to ) ) && ! mkdir( dirname( $to ), 0777, true ) && ! is_dir( dirname( $to ) ) ) {
			mpty_zen_build_fail( 'Could not create a production subdirectory.' );
		}
		if ( ! copy( $from, $to ) ) {
			mpty_zen_build_fail( 'Could not copy production file: ' . $path );
		}
	}
	$version  = mpty_zen_build_validate_directory( $plugin_root, $manifest );
	$zip_path = $output . DIRECTORY_SEPARATOR . MPTY_ZEN_BUILD_SLUG . '-' . $version . '.zip';
	$zip      = new ZipArchive();
	if ( true !== $zip->open( $zip_path, ZipArchive::CREATE | ZipArchive::OVERWRITE ) ) {
		mpty_zen_build_fail( 'Could not create the production ZIP.' );
	}
	foreach ( $manifest as $path ) {
		$entry    = MPTY_ZEN_BUILD_SLUG . '/' . str_replace( DIRECTORY_SEPARATOR, '/', $path );
		$contents = file_get_contents( $plugin_root . DIRECTORY_SEPARATOR . $path );
		if ( false === $contents || ! $zip->addFromString( $entry, $contents ) ) {
			$zip->close();
			mpty_zen_build_fail( 'Could not add a production file to the ZIP.' );
		}
		if ( ! $zip->setMtimeName( $entry, 946684800 ) ) {
			$zip->close();
			mpty_zen_build_fail( 'Could not normalize a ZIP entry timestamp.' );
		}
	}
	if ( ! $zip->close() ) {
		mpty_zen_build_fail( 'Could not finalize the production ZIP.' );
	}
	$checksum      = mpty_zen_build_validate_zip( $zip_path, $manifest, $version, $plugin_root );
	$checksum_path = $zip_path . '.sha256';
	if ( false === file_put_contents( $checksum_path, $checksum . '  ' . basename( $zip_path ) . PHP_EOL ) ) {
		mpty_zen_build_fail( 'Could not write the SHA-256 checksum file.' );
	}
	fwrite( STDOUT, 'Built and validated Zen by MPTY ' . $version . PHP_EOL );
	fwrite( STDOUT, 'ZIP: ' . $zip_path . PHP_EOL );
	fwrite( STDOUT, 'SHA-256: ' . $checksum . PHP_EOL );
}

$mpty_zen_build_mode = isset( $argv[1] ) ? $argv[1] : '';
$options             = mpty_zen_build_options( array_slice( $argv, 2 ) );
$source              = mpty_zen_build_source( isset( $options['source'] ) ? $options['source'] : dirname( __DIR__ ) );
$manifest            = mpty_zen_build_manifest( $source );

if ( 'build' === $mpty_zen_build_mode ) {
	$output = mpty_zen_build_output( $source, isset( $options['output'] ) ? $options['output'] : 'build' );
	mpty_zen_build_artifact( $source, $output, $manifest );
} elseif ( 'validate' === $mpty_zen_build_mode ) {
	if ( empty( $options['zip'] ) ) {
		mpty_zen_build_fail( 'Validation requires --zip=PATH.' );
	}
	$zip_path   = $options['zip'];
	$build_root = dirname( $zip_path ) . DIRECTORY_SEPARATOR . MPTY_ZEN_BUILD_SLUG;
	$version    = mpty_zen_build_validate_directory( $build_root, $manifest );
	$checksum   = mpty_zen_build_validate_zip( $zip_path, $manifest, $version, $build_root );
	fwrite( STDOUT, 'Validated Zen by MPTY ' . $version . ' (' . $checksum . ')' . PHP_EOL );
} else {
	mpty_zen_build_fail( 'Mode must be build or validate.' );
}
