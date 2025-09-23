<?php
// Redirect 404 errors to homepage
wp_redirect( home_url( '/' ), 302 );
exit;
?>
