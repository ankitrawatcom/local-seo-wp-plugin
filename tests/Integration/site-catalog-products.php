<?php
foreach ( array( 12, 13, 14, 15 ) as $id ) {
	$p = wc_get_product( $id );
	if ( ! $p ) {
		echo "id={$id} missing\n";
		continue;
	}
	echo "id={$id} type=" . $p->get_type() . " status=" . $p->get_status() . " name=" . $p->get_name() . " parent=" . $p->get_parent_id() . "\n";
}
$all = wc_get_products(
	array(
		'status' => array( 'publish', 'draft', 'private' ),
		'limit'  => 20,
		'type'   => array( 'simple', 'variable', 'variation' ),
		'return' => 'ids',
	)
);
echo "all_ids=" . implode( ',', $all ) . "\n";
