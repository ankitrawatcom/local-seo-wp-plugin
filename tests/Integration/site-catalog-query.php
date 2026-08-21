<?php
foreach ( array( 12, 13 ) as $id ) {
	$p = wc_get_product( $id );
	echo "id={$id} stock_status=" . $p->get_stock_status() . " purchasable=" . ( $p->is_purchasable() ? '1' : '0' ) . " in_stock=" . ( $p->is_in_stock() ? '1' : '0' ) . " date=" . $p->get_date_created() . "\n";
}
echo "hide_out_of_stock=" . get_option( 'woocommerce_hide_out_of_stock_items' ) . "\n";
$q = new WP_Query(
	array(
		'post_type'      => 'product',
		'post_status'    => 'publish',
		'posts_per_page' => 10,
		'fields'         => 'ids',
	)
);
echo "wp_query_ids=" . implode( ',', $q->posts ) . "\n";
$ids = wc_get_products(
	array(
		'status'  => 'publish',
		'limit'   => -1,
		'return'  => 'ids',
		'orderby' => 'date',
		'order'   => 'DESC',
	)
);
echo "wc_all_ids=" . implode( ',', $ids ) . "\n";
$ids2 = wc_get_products(
	array(
		'status' => 'publish',
		'limit'  => -1,
		'return' => 'ids',
		'type'   => array( 'simple', 'variable' ),
	)
);
echo "wc_typed_ids=" . implode( ',', $ids2 ) . "\n";
