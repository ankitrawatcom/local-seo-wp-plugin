<?php
/**
 * Autoloader path-traversal tests.
 *
 * @package AnkitRawat\LocalSEO
 */

declare(strict_types=1);

use AnkitRawat\LocalSEO\Plugin;
use AnkitRawat\LocalSEO\Support\Sanitize;
use PHPUnit\Framework\TestCase;

final class AutoloaderTest extends TestCase {

	protected function setUp(): void {
		$GLOBALS['lsar_autoload_pwned'] = false;
	}

	public function test_legitimate_classes_load() {
		$this->assertTrue( class_exists( Plugin::class ) );
		$this->assertTrue( class_exists( Sanitize::class ) );
		local_seo_by_ankit_rawat_autoload( 'AnkitRawat\\LocalSEO\\Schema\\JsonLd' );
		$this->assertTrue( class_exists( 'AnkitRawat\\LocalSEO\\Schema\\JsonLd' ) );
	}

	/**
	 * @dataProvider traversalClassNames
	 * @param string $class_name Hostile class name.
	 */
	public function test_traversal_class_names_do_not_include_files( $class_name ) {
		$bait = dirname( LOCAL_SEO_BY_ANKIT_RAWAT_FILE ) . '/tests/fixtures/traversal-bait.php';
		$this->assertFileExists( $bait );

		local_seo_by_ankit_rawat_autoload( $class_name );

		$this->assertFalse( $GLOBALS['lsar_autoload_pwned'] );
		$this->assertFalse( class_exists( $class_name, false ) );
	}

	/**
	 * @return array<int, array<int, string>>
	 */
	public function traversalClassNames() {
		return array(
			array( 'AnkitRawat\\LocalSEO\\..\\..\\tests\\fixtures\\traversal-bait' ),
			array( 'AnkitRawat\\LocalSEO\\../..\\tests/fixtures/traversal-bait' ),
			array( 'AnkitRawat\\LocalSEO\\..\\Plugin' ),
			array( 'AnkitRawat\\LocalSEO\\Admin\\..\\..\\Plugin' ),
			array( 'AnkitRawat\\LocalSEO\\..\\..\\..\\..\\windows\\system32' ),
			array( 'AnkitRawat\\LocalSEO\\foo/../../tests/fixtures/traversal-bait' ),
			array( "AnkitRawat\\LocalSEO\\..\\..\\tests\\fixtures\\traversal-bait\0Plugin" ),
		);
	}
}
