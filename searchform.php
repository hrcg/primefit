<?php
$query = get_search_query();
?>
<form role="search" method="get" class="search-form" action="<?php echo esc_url( home_url( '/' ) ); ?>">
	<label>
		<input type="search" class="search-field" placeholder="Search products" value="<?php echo esc_attr( $query ); ?>" name="s" />
		<input type="hidden" name="post_type" value="product" />
	</label>
	<button type="submit" class="search-submit" aria-label="Search">Search</button>
</form>

