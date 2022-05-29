<?php
$hreflang  = get_post_meta( $post->ID, '_hrflng_page_hreflang', true );
?>

<div class="section-hrflng-page-hreflang">
	<div class="section__body">
		<p class="meta-options">
			<label for="hrflng-page-hreflang"><?php _e( 'Hreflang', 'hreflang-tags-to-a' ); ?></label>
			<input id="hrflng-page-hreflang" type="text" name="hrflng-page-hreflang" value="<?php echo $hreflang ?>">
		</p>
	</div>
</div>
