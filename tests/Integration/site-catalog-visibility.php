<?php
$p = wc_get_product( 12 );
$terms = wp_get_post_terms( 12, 'product_visibility', array( 'fields' => 'names' ) );
echo "p12_visibility_terms=" . implode( ',', is_array( $terms ) ? $terms : array() ) . "\n";
$terms13 = wp_get_post_terms( 13, 'product_visibility', array( 'fields' => 'names' ) );
echo "p13_visibility_terms=" . implode( ',', is_array( $terms13 ) ? $terms13 : array() ) . "\n";
echo "p12_catalog=" . $p->get_catalog_visibility() . "\n";
$data = $p->get_data();
echo "p12_keys=" . implode( ',', array_keys( $data ) ) . "\n";
