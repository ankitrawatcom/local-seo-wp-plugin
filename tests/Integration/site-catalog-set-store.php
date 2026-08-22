<?php
foreach ( array( 12, 13 ) as $id ) {
	$p = wc_get_product( $id );
	echo "id={$id} catalog_visibility=" . $p->get_catalog_visibility() . " featured=" . ( $p->get_featured() ? '1' : '0' ) . "\n";
}
$listed = wc_get_products(
	array(
		'status'  => 'publish',
		'limit'   => 5,
		'orderby' => 'date',
		'order'   => 'DESC',
	)
);
foreach ( $listed as $p ) {
	echo "listed id=" . $p->get_id() . " type=" . $p->get_type() . " name=" . $p->get_name() . "\n";
}

$opts = get_option( 'local_seo_by_ankit_rawat_options', array() );
$opts['business_type'] = 'Store';
update_option( 'local_seo_by_ankit_rawat_options', $opts );
$opts = get_option( 'local_seo_by_ankit_rawat_options', array() );
echo "updated_business_type=" . $opts['business_type'] . "\n";
echo "schema_enabled=" . ( ! empty( $opts['schema_enabled'] ) ? '1' : '0' ) . "\n";
echo "woocommerce_product_schema=" . ( ! empty( $opts['woocommerce_product_schema'] ) ? '1' : '0' ) . "\n";
