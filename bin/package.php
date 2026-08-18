<?php
/**
 * Build a production plugin ZIP (no tests, vendor, Git, or audit docs).
 *
 * Usage (from the plugin root):
 *   php bin/package.php
 *
 * Output:
 *   dist/local-seo-by-ankit-rawat-4.0.0.zip
 *
 * @package AnkitRawat\LocalSEO
 */

if ( PHP_SAPI !== 'cli' ) {
	fwrite( STDERR, "Run from CLI.\n" );
	exit( 1 );
}

if ( ! class_exists( 'ZipArchive' ) ) {
	fwrite( STDERR, "PHP zip extension is required.\n" );
	exit( 1 );
}

$root = dirname( __DIR__ );
$slug = 'local-seo-by-ankit-rawat';

$main = file_get_contents( $root . '/local-seo-by-ankit-rawat.php' );
if ( ! is_string( $main ) || ! preg_match( '/^\s*\*\s*Version:\s*([0-9.]+)/m', $main, $match ) ) {
	fwrite( STDERR, "Could not read plugin version.\n" );
	exit( 1 );
}
$version = $match[1];

$exclude_dirs = array(
	'.git',
	'.github',
	'dist',
	'tests',
	'docs',
	'vendor',
	'tools',
	'bin',
	'node_modules',
	'.idea',
	'.vscode',
);

$exclude_files = array(
	'.gitignore',
	'.editorconfig',
	'.phpunit.result.cache',
	'.phpcs.cache',
	'composer.json',
	'composer.lock',
	'phpcs.xml.dist',
	'phpstan.neon.dist',
	'phpunit.xml.dist',
);

$dist = $root . '/dist';
if ( ! is_dir( $dist ) && ! mkdir( $dist, 0755, true ) ) {
	fwrite( STDERR, "Could not create dist/.\n" );
	exit( 1 );
}

$zip_path = $dist . '/' . $slug . '-' . $version . '.zip';
if ( is_file( $zip_path ) ) {
	unlink( $zip_path );
}

$zip = new ZipArchive();
if ( true !== $zip->open( $zip_path, ZipArchive::CREATE ) ) {
	fwrite( STDERR, "Could not create ZIP.\n" );
	exit( 1 );
}

$iterator = new RecursiveIteratorIterator(
	new RecursiveDirectoryIterator( $root, FilesystemIterator::SKIP_DOTS )
);

$added = 0;
foreach ( $iterator as $file ) {
	if ( ! $file->isFile() ) {
		continue;
	}

	$full    = $file->getPathname();
	$rel     = substr( $full, strlen( $root ) + 1 );
	$rel_unix = str_replace( '\\', '/', $rel );
	$parts   = explode( '/', $rel_unix );

	if ( in_array( $parts[0], $exclude_dirs, true ) ) {
		continue;
	}

	if ( in_array( $rel_unix, $exclude_files, true ) ) {
		continue;
	}

	if ( 0 === strpos( $parts[ count( $parts ) - 1 ], '.' ) && '.htaccess' !== $parts[ count( $parts ) - 1 ] ) {
		continue;
	}

	$zip->addFile( $full, $slug . '/' . $rel_unix );
	++$added;
}

$zip->close();

fwrite( STDOUT, $zip_path . "\n" . $added . " files\n" );
