							<!-- End of Email Content -->
						</td>
					</tr>
				</table>

			</td>
		</tr>

		<!-- Footer Section -->
		<tr>
			<td align="center" style="padding: 60px 20px 40px 20px;">
				<table width="100%" cellpadding="0" cellspacing="0" border="0" style="max-width: 600px; margin: 0 auto; background-color: #ffffff;">
					<tr>
						<td style="padding: 40px 0 0 0; border-top: 1px solid #e5e5e5;">

							<!-- Footer Content -->
							<table width="100%" cellpadding="0" cellspacing="0" border="0">
								<tr>
									<td align="center" style="padding: 0 0 30px 0;">
										<p style="font-family: 'Helvetica Neue', Helvetica, Roboto, Arial, sans-serif; font-size: 14px; color: #666666; margin: 0; line-height: 1.5;">
											<?php
											/* translators: %s: Site name */
											printf( esc_html__( 'Thank you for choosing %s', 'woocommerce' ), esc_html( $site_name ) );
											?>
										</p>
									</td>
								</tr>
								<tr>
									<td align="center" style="padding: 0 0 20px 0;">
										<p style="font-family: 'Helvetica Neue', Helvetica, Roboto, Arial, sans-serif; font-size: 12px; color: #999999; margin: 0; line-height: 1.4;">
											<?php esc_html_e( 'This email was sent to you because you are a valued customer.', 'woocommerce' ); ?>
										</p>
									</td>
								</tr>
								<tr>
									<td align="center">
										<a href="<?php echo esc_url( $site_url ); ?>" style="font-family: 'Helvetica Neue', Helvetica, Roboto, Arial, sans-serif; font-size: 12px; color: #666666; text-decoration: none; display: inline-block; padding: 10px 20px; border: 1px solid #e5e5e5; border-radius: 4px; margin: 10px 5px;">
											<?php esc_html_e( 'Visit Our Store', 'woocommerce' ); ?>
										</a>
										<?php
										// Social media links or additional footer content can be added here
										?>
									</td>
								</tr>
							</table>

						</td>
					</tr>
				</table>
			</td>
		</tr>
	</table>

</body>
</html>

<?php
/**
 * Email Footer
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/emails/email-footer.php.
 *
 * @package WooCommerce/Templates/Emails
 * @version 10.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

do_action( 'woocommerce_email_footer' );
?>
