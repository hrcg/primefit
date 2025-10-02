<?php
/**
 * Email Header
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/emails/email-header.php.
 *
 * @package WooCommerce/Templates/Emails
 * @version 10.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Get site information
$site_name = get_bloginfo( 'name' );
$site_url = home_url();
$logo_url = get_template_directory_uri() . '/assets/images/logo-black.webp';

// Email heading (fallback if not provided)
$email_heading = isset( $email_heading ) ? $email_heading : '';

?>

<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=<?php bloginfo( 'charset' ); ?>" />
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title><?php echo esc_html( $site_name ); ?> - <?php echo esc_html( $email_heading ); ?></title>
	<?php do_action( 'woocommerce_email_header' ); ?>
</head>
<body <?php echo is_rtl() ? 'rightmargin' : 'leftmargin'; ?>="0" marginwidth="0" topmargin="0" marginheight="0" offset="0" style="background-color: #ffffff; font-family: 'Helvetica Neue', Helvetica, Roboto, Arial, sans-serif; margin: 0; padding: 0;">

	<table width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color: #ffffff; margin: 0; padding: 0;">
		<tr>
			<td align="center" style="padding: 40px 20px 20px 20px;">

				<!-- Header Section -->
				<table width="100%" cellpadding="0" cellspacing="0" border="0" style="max-width: 600px; margin: 0 auto; background-color: #ffffff;">
					<tr>
						<td align="center" style="padding: 0 0 30px 0; border-bottom: 1px solid #e5e5e5;">

							<!-- Logo and Site Name -->
							<table width="100%" cellpadding="0" cellspacing="0" border="0">
								<tr>
									<td align="center" style="padding: 0 0 20px 0;">
										<a href="<?php echo esc_url( $site_url ); ?>" style="text-decoration: none; color: #333333;">
											<img src="<?php echo esc_url( $logo_url ); ?>" alt="<?php echo esc_attr( $site_name ); ?>" width="120" height="auto" style="display: block; margin: 0 auto;" />
										</a>
									</td>
								</tr>
								<tr>
									<td align="center" style="padding: 0 0 10px 0;">
										<h1 style="font-family: 'Helvetica Neue', Helvetica, Roboto, Arial, sans-serif; font-size: 24px; font-weight: 700; color: #333333; margin: 0; letter-spacing: -0.5px;"><?php echo esc_html( $site_name ); ?></h1>
									</td>
								</tr>
							</table>

						</td>
					</tr>
				</table>

				<!-- Email Content Container -->
				<table width="100%" cellpadding="0" cellspacing="0" border="0" style="max-width: 600px; margin: 0 auto; background-color: #ffffff;">
					<tr>
						<td style="padding: 40px 0 0 0;">

							<!-- Email Heading -->
							<?php if ( ! empty( $email_heading ) ) : ?>
								<table width="100%" cellpadding="0" cellspacing="0" border="0">
									<tr>
										<td align="center" style="padding: 0 0 30px 0;">
											<h2 style="font-family: 'Helvetica Neue', Helvetica, Roboto, Arial, sans-serif; font-size: 28px; font-weight: 700; color: #333333; margin: 0; letter-spacing: -0.5px;"><?php echo esc_html( $email_heading ); ?></h2>
										</td>
									</tr>
								</table>
							<?php endif; ?>
