<?php
/**
 * Builds the Beauty Galore Kenya site inside WordPress Playground (same pipeline as our RealBeef and The Daily builds).
 * Run by the blueprint after WooCommerce and the theme are installed. Product photos are read from /wordpress/bg-images.
 *
 * Real: piercing placements and prices, copied from Beauty Galore's "Piercing Price List" post on Instagram (Feb 2026).
 * Samples: every jewellery, gift and accessory price, until Beauty Galore confirms them.
 * Staff edit names, prices, photos and badges under Products in wp-admin; the home page reads them live.
 */
require_once '/wordpress/wp-load.php';
require_once ABSPATH . 'wp-admin/includes/media.php';
require_once ABSPATH . 'wp-admin/includes/file.php';
require_once ABSPATH . 'wp-admin/includes/image.php';

// Photos are already web-sized: copy each into uploads and register it, without generating thumbnails.
function bg_sideload( $src, $parent, $title ) {
	$up   = wp_upload_dir();
	$file = wp_unique_filename( $up['path'], basename( $src ) );
	$dest = trailingslashit( $up['path'] ) . $file;
	if ( ! copy( $src, $dest ) ) {
		return 0;
	}
	$type = wp_check_filetype( $file );
	$size = @getimagesize( $dest ) ?: [ 0, 0 ];
	$id   = wp_insert_attachment( [ 'post_mime_type' => $type['type'] ?: 'image/jpeg', 'post_title' => $title, 'post_status' => 'inherit', 'guid' => trailingslashit( $up['url'] ) . $file ], $dest, $parent, true, false );
	if ( is_wp_error( $id ) ) {
		return 0;
	}
	wp_update_attachment_metadata( $id, [ 'width' => $size[0], 'height' => $size[1], 'file' => _wp_relative_upload_path( $dest ), 'sizes' => [], 'image_meta' => [] ] );
	return $id;
}

wp_defer_term_counting( true );
wp_suspend_cache_invalidation( true );
$wpdb->query( 'START TRANSACTION' );

if ( defined( 'BG_FAST' ) && BG_FAST ) {
	add_filter( 'intermediate_image_sizes_advanced', '__return_empty_array' );
	add_filter( 'big_image_size_threshold', '__return_false' );
	add_filter( 'woocommerce_background_image_regeneration', '__return_false' );
	add_filter( 'woocommerce_resize_images', '__return_false' );
}

foreach ( [
	'blogname'                       => 'Beauty Galore Kenya',
	'blogdescription'                => 'The best jewellery to suit your style. Stainless jewellery, needle piercings by appointment and curated gifts at Westlands Commercial Center, Nairobi.',
	'timezone_string'                => 'Africa/Nairobi',
	'woocommerce_currency'           => 'KES',
	'woocommerce_currency_pos'       => 'left_space',
	'woocommerce_price_num_decimals' => '0',
	'woocommerce_price_thousand_sep' => ',',
	'woocommerce_default_country'    => 'KE:KE30',
	'woocommerce_store_city'         => 'Nairobi',
	'woocommerce_store_address'      => 'Westlands Commercial Center',
	'woocommerce_manage_stock'       => 'no',
	'woocommerce_enable_reviews'     => 'no',
	'woocommerce_onboarding_profile' => [ 'skipped' => true ],
	'woocommerce_task_list_hidden'   => 'yes',
	'woocommerce_coming_soon'        => 'no',
	'woocommerce_enable_coupons'     => 'no',
] as $k => $v ) {
	update_option( $k, $v );
}

foreach ( get_posts( [ 'post_type' => [ 'post', 'page' ], 'name' => 'hello-world', 'numberposts' => 1 ] ) as $p ) wp_delete_post( $p->ID, true );
$sample_page = get_page_by_path( 'sample-page' );
if ( $sample_page ) wp_delete_post( $sample_page->ID, true );

// Shop categories in tab order. "Piercing services" feeds the booking engine, not the shop grid.
$cats = [
	'rings'       => 'Stainless Rings',
	'ears'        => 'Cuffs & Ear Stacks',
	'necklaces'   => 'Necklaces & Bracelets',
	'piercing'    => 'Piercing Jewellery',
	'gifts'       => 'Gift Sets',
	'accessories' => 'Accessories',
	'services'    => 'Piercing Services',
];
$blurbs = [
	'rings'       => 'Waterproof, tarnish-free bands, signets and statement pieces.',
	'ears'        => 'Ear cuffs, huggies, studs and ready-styled ear sets.',
	'necklaces'   => 'Layered chains, pendants, tennis bracelets and wrist stacks.',
	'piercing'    => 'Implant titanium and surgical steel, safe for healing piercings.',
	'gifts'       => 'Ready-to-gift boxes with a handwritten note card.',
	'accessories' => 'Sunglasses and finishing touches.',
	'services'    => 'Piercing placements for the booking engine. Prices include stainless steel jewellery.',
];
$cat_ids = [];
$order   = 0;
foreach ( $cats as $slug => $name ) {
	$t                = wp_insert_term( $name, 'product_cat', [ 'slug' => $slug, 'description' => $blurbs[ $slug ] ] );
	$cat_ids[ $slug ] = is_wp_error( $t ) ? (int) $t->get_error_data() : $t['term_id'];
	update_term_meta( $cat_ids[ $slug ], 'order', $order++ );
}

/*
 * Jewellery: name, sample price, category, short description (material), photo file ('' = line drawing),
 * drawing (ring, band, signet, hoop, cuff, stud, chain, bracelet, labret, septum, gift), metal tone, badge.
 */
$jewellery = [
	[ 'Swirl Statement Ring', 1200, 'rings', '18K gold-tone stainless steel', 'swirl-ring.jpg', 'ring', 'gold', 'Bestseller' ],
	[ 'Stackable Band Trio', 950, 'rings', 'Stainless steel, set of 3', '', 'band', 'gold', '' ],
	[ 'Oval Signet Ring', 1100, 'rings', 'Gold-tone stainless steel', '', 'signet', 'gold', '' ],
	[ 'Pavé Sparkle Band', 850, 'rings', 'Stainless steel with cubic zirconia', '', 'ring', 'silver', '' ],

	[ 'Emerald-Cut Crystal Studs', 650, 'ears', 'Silver-tone steel, cubic zirconia', 'emerald-studs.jpg', 'stud', 'silver', 'New' ],
	[ 'Chunky Huggie Hoops', 750, 'ears', 'Gold-tone stainless steel', '', 'hoop', 'gold', '' ],
	[ 'No-Piercing Ear Cuff', 550, 'ears', 'Stainless steel, adjustable', '', 'cuff', 'silver', '' ],
	[ 'Curated Ear Stack Set', 1500, 'ears', '3 pairs: studs, huggies and a cuff', '', 'stud', 'gold', 'Styled set' ],

	[ 'Layered Tree of Life Set', 1200, 'necklaces', 'Gold-tone, three layered chains', 'layered-tree.jpg', 'chain', 'gold', '' ],
	[ 'Dainty Paperclip Chain', 800, 'necklaces', 'Waterproof stainless steel', '', 'chain', 'gold', '' ],
	[ 'Classic Tennis Bracelet', 1300, 'necklaces', 'Silver-tone with cubic zirconia', '', 'bracelet', 'silver', '' ],
	[ 'Waterproof Wrist Stack', 1600, 'necklaces', 'Three stainless bangles', '', 'bracelet', 'gold', '' ],

	[ 'Titanium Flat-Back Labret', 800, 'piercing', 'Implant-grade titanium, internally threaded', '', 'labret', 'silver', '' ],
	[ 'Septum Clicker Ring', 900, 'piercing', 'Surgical steel, hinged', '', 'septum', 'silver', '' ],
	[ 'Seamless Helix Hoop', 700, 'piercing', 'Gold-tone surgical steel', '', 'hoop', 'gold', '' ],
	[ 'Crystal Starter Stud', 600, 'piercing', 'Implant-grade titanium', '', 'stud', 'silver', '' ],

	[ 'Festive Stud Card (6 pairs)', 1000, 'gifts', 'Holiday studs on a gift card', 'festive-set.jpg', 'gift', 'rose', 'Seasonal' ],
	[ 'Birthday Glow Box', 2500, 'gifts', 'Necklace, studs and a note card', '', 'gift', 'rose', '' ],
	[ 'Bridesmaid Box', 3200, 'gifts', 'Matching pendant and huggies', '', 'gift', 'gold', '' ],
	[ 'Anniversary Box', 3800, 'gifts', 'Tennis bracelet and signet ring', '', 'gift', 'silver', '' ],

	[ 'Oversized Square Sunglasses', 1200, 'accessories', 'Clear frame, amber lens', 'square-shades.jpg', 'gift', 'gold', '' ],
	[ 'Slim Cream Sunglasses', 1000, 'accessories', 'Retro rectangle frame', 'slim-shades.jpg', 'gift', 'gold', '' ],
];

$count = 0;
foreach ( $jewellery as [ $name, $price, $cat, $desc, $photo, $art, $tone, $badge ] ) {
	$p = new WC_Product_Simple();
	$p->set_name( $name );
	$p->set_status( 'publish' );
	$p->set_menu_order( $count++ );
	$p->set_regular_price( (string) $price );
	$p->set_short_description( $desc );
	$p->set_category_ids( [ $cat_ids[ $cat ] ] );
	$p->update_meta_data( '_bg_art', $art );
	$p->update_meta_data( '_bg_tone', $tone );
	if ( $badge ) $p->update_meta_data( '_bg_badge', $badge );
	$p->update_meta_data( '_bg_sample', 'yes' );
	$pid = $p->save();
	if ( $photo && file_exists( "/wordpress/bg-images/$photo" ) ) {
		$img = bg_sideload( "/wordpress/bg-images/$photo", $pid, $name );
		if ( $img ) set_post_thumbnail( $pid, $img );
	}
}

/*
 * Piercing services, exactly as on their price list: name, KSh price, group, healing guide, 18+ only.
 * Healing times are general guidance, not from the studio.
 */
$piercings = [
	[ 'Lobe (one)', 1500, 'Ear & cartilage', '6–8 weeks', false ],
	[ 'Lobe (pair)', 2000, 'Ear & cartilage', '6–8 weeks', false ],
	[ 'Helix', 2500, 'Ear & cartilage', '6–12 months', false ],
	[ 'Forward helix', 2500, 'Ear & cartilage', '6–12 months', false ],
	[ 'Tragus', 2500, 'Ear & cartilage', '6–12 months', false ],
	[ 'Conch', 2500, 'Ear & cartilage', '6–12 months', false ],
	[ 'Rook', 2500, 'Ear & cartilage', '6–12 months', false ],
	[ 'Daith', 2500, 'Ear & cartilage', '6–12 months', false ],
	[ 'Industrial', 3500, 'Ear & cartilage', '9–12 months', false ],
	[ 'Nose (nostril)', 2500, 'Facial', '4–6 months', false ],
	[ 'Nose (double)', 5000, 'Facial', '4–6 months', false ],
	[ 'Septum', 3000, 'Facial', '2–3 months', false ],
	[ 'Eyebrow', 3000, 'Facial', '2–3 months', false ],
	[ 'Lip', 2500, 'Facial', '2–3 months', false ],
	[ 'Medusa / philtrum', 2500, 'Facial', '2–3 months', false ],
	[ 'Smiley', 3000, 'Facial', '4–12 weeks', false ],
	[ 'Tongue', 3500, 'Facial', '4–6 weeks', false ],
	[ 'Bridge', 3000, 'Facial', '2–3 months', false ],
	[ 'Ashley', 3000, 'Facial', '2–3 months', false ],
	[ 'Belly / navel', 3000, 'Torso', '6–12 months', false ],
	[ 'Nipple (one)', 2500, 'Torso', '6–12 months', true ],
	[ 'Nipple (pair)', 5000, 'Torso', '6–12 months', true ],
	[ 'Surface', 3000, 'Other', '6–12 months', false ],
	[ 'Microdermal', 5000, 'Other', '1–3 months', false ],
	[ 'Dermal removal', 3000, 'Other', '2–4 weeks', false ],
	[ 'Professional cleaning', 1000, 'Other', '', false ],
	[ 'Jewellery change', 500, 'Other', '', false ],
];
foreach ( $piercings as [ $name, $price, $group, $heal, $adult ] ) {
	$p = new WC_Product_Simple();
	$p->set_name( $name );
	$p->set_slug( 'piercing-' . sanitize_title( $name ) );
	$p->set_status( 'publish' );
	$p->set_catalog_visibility( 'hidden' );
	$p->set_menu_order( $count++ );
	$p->set_regular_price( (string) $price );
	$p->set_category_ids( [ $cat_ids['services'] ] );
	$p->update_meta_data( '_bg_group', $group );
	if ( $heal ) $p->update_meta_data( '_bg_heal', $heal );
	if ( $adult ) $p->update_meta_data( '_bg_adult', 'yes' );
	$p->save();
}

// One-page site: the WooCommerce shop page and product pages redirect to the home page (functions.php).
foreach ( [ 'cart', 'checkout', 'myaccount' ] as $wc_page ) {
	$id = wc_get_page_id( $wc_page );
	if ( $id > 0 ) wp_delete_post( $id, true );
}

update_option( 'permalink_structure', '/%postname%/' );
update_option( 'woocommerce_task_list_hidden_lists', [ 'setup', 'extended' ] );
update_option( 'woocommerce_task_list_complete', 'yes' );
update_option( 'woocommerce_show_marketplace_suggestions', 'no' );
update_option( 'woocommerce_admin_install_timestamp', time() - WEEK_IN_SECONDS );

$wpdb->query( 'COMMIT' );
wp_suspend_cache_invalidation( false );
wp_defer_term_counting( false );
delete_option( 'product_cat_children' );
clean_taxonomy_cache( 'product_cat' );
_get_term_hierarchy( 'product_cat' );
flush_rewrite_rules();
echo "Beauty Galore: $count products\n";
