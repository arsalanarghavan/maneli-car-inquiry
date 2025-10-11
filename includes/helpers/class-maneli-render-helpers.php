<?php
/**
 * Helper class for rendering various HTML elements and data in Maneli Car Inquiry plugin.
 *
 * @package ManeliCarInquiry
 * @subpackage Helpers
 */

namespace ManeliCarInquiry\Helpers;

use ManeliCarInquiry\Data\Maneli_Inquiry_Data;
use ManeliCarInquiry\Data\Maneli_Cash_Inquiry_Data;

/**
 * Class Maneli_Render_Helpers
 *
 * Contains static methods to render common elements.
 */
class Maneli_Render_Helpers {

	/**
	 * Renders a standard WordPress style notice.
	 *
	 * @param string $message The message to display.
	 * @param string $type    The type of notice (success, error, warning, info).
	 * @param bool   $dismissible Whether the notice is dismissible.
	 * @return string The HTML for the notice.
	 */
	public static function render_notice( $message, $type = 'info', $dismissible = true ) {
		$class = 'notice notice-' . sanitize_html_class( $type );
		if ( $dismissible ) {
			$class .= ' is-dismissible';
		}

		return sprintf(
			'<div class="%s"><p>%s</p></div>',
			esc_attr( $class ),
			wp_kses_post( $message )
		);
	}

	/**
	 * Returns the CSS class for the inquiry status.
	 *
	 * @param string $status The inquiry status slug.
	 * @return string The CSS class.
	 */
	public static function get_inquiry_status_class( $status ) {
		switch ( $status ) {
			case 'pending':
				return 'status-pending';
			case 'rejected':
			case 'failed':
				return 'status-rejected';
			case 'user_confirmed':
				return 'status-confirmed';
			case 'more_docs':
				return 'status-more-docs';
			case 'expert_assigned':
				return 'status-expert-assigned';
			case 'in_progress':
				return 'status-in-progress';
			default:
				return 'status-info';
		}
	}

	/**
	 * Returns the translated label for the inquiry status.
	 *
	 * @param string $status The inquiry status slug.
	 * @return string The translated label.
	 */
	public static function get_inquiry_status_label( $status ) {
		switch ( $status ) {
			case 'pending':
				return esc_html__( 'Pending', 'maneli-car-inquiry' );
			case 'rejected':
				return esc_html__( 'Rejected', 'maneli-car-inquiry' );
			case 'user_confirmed':
				return esc_html__( 'Confirmed by User', 'maneli-car-inquiry' );
			case 'failed':
				return esc_html__( 'Finotex Failed', 'maneli-car-inquiry' );
			case 'more_docs':
				return esc_html__( 'More Documents Requested', 'maneli-car-inquiry' );
			case 'expert_assigned':
				return esc_html__( 'Expert Assigned', 'maneli-car-inquiry' );
			case 'in_progress':
				return esc_html__( 'In Progress', 'maneli-car-inquiry' );
			default:
				return esc_html__( 'Unknown', 'maneli-car-inquiry' );
		}
	}

	/**
	 * Renders the HTML for the inquiry status badge.
	 *
	 * @param string $status The inquiry status slug.
	 * @return string The HTML output.
	 */
	public static function render_inquiry_status_badge( $status ) {
		$class = self::get_inquiry_status_class( $status );
		$label = self::get_inquiry_status_label( $status );

		return sprintf(
			'<span class="maneli-inquiry-status %s">%s</span>',
			esc_attr( $class ),
			esc_html( $label )
		);
	}

	/**
	 * Renders a row for the Inquiry List table (Installment).
	 *
	 * @param \WP_Post $inquiry_post The inquiry post object.
	 * @param int      $index The row index.
	 */
	public static function render_inquiry_row( $inquiry_post, $index ) {
		$inquiry_id        = $inquiry_post->ID;
		$inquiry_status    = $inquiry_post->post_status;
		$meta_data         = get_post_meta( $inquiry_id );
		$inquiry_data      = Maneli_Inquiry_Data::get_inquiry_data( $inquiry_id, $meta_data );
		$car_name          = $inquiry_data['product_name'];
		$full_name         = $inquiry_data['full_name'];
		$report_url        = $inquiry_data['report_url'];
		$expert_assigned   = $inquiry_data['expert_assigned'];
		$expert_name       = $inquiry_data['expert_name'];
		$is_expert_allowed = Maneli_Permission_Helpers::is_expert_allowed_to_view( $expert_assigned );
		$is_admin          = current_user_can( 'manage_maneli_inquiries' );

		if ( ! $is_admin && ! $is_expert_allowed ) {
			return;
		}

		?>
		<tr class="woocommerce-orders-table__row woocommerce-orders-table__row--status-<?php echo esc_attr( $inquiry_status ); ?>">
			<td data-title="<?php esc_attr_e( 'Row', 'maneli-car-inquiry' ); ?>">
				<?php echo esc_html( $index ); ?>
			</td>
			<td data-title="<?php esc_attr_e( 'Request ID', 'maneli-car-inquiry' ); ?>">
				#<?php echo esc_html( $inquiry_id ); ?>
			</td>
			<td data-title="<?php esc_attr_e( 'Date', 'maneli-car-inquiry' ); ?>">
				<?php echo esc_html( maneli_convert_to_jalali( $inquiry_post->post_date ) ); ?>
			</td>
			<td data-title="<?php esc_attr_e( 'Status', 'maneli-car-inquiry' ); ?>">
				<?php echo self::render_inquiry_status_badge( $inquiry_status ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			</td>
			<td data-title="<?php esc_attr_e( 'Car', 'maneli-car-inquiry' ); ?>">
				<?php echo esc_html( $car_name ); ?>
			</td>
			<td data-title="<?php esc_attr_e( 'Full Name', 'maneli-car-inquiry' ); ?>">
				<?php echo esc_html( $full_name ); ?>
			</td>
			<td data-title="<?php esc_attr_e( 'Expert', 'maneli-car-inquiry' ); ?>">
				<?php echo esc_html( $expert_name ); ?>
			</td>
			<td class="woocommerce-orders-table__cell-order-actions">
				<a href="<?php echo esc_url( $report_url ); ?>" class="button view"><?php esc_html_e( 'View Details', 'maneli-car-inquiry' ); ?></a>
				<?php if ( current_user_can( 'manage_maneli_inquiries' ) ) : ?>
					<button class="button delete-installment-list-btn" data-inquiry-id="<?php echo esc_attr( $inquiry_id ); ?>" style="background-color: var(--theme-red); border-color: var(--theme-red); margin-top: 5px;"><?php esc_html_e( 'Delete', 'maneli-car-inquiry' ); ?></button>
				<?php endif; ?>
			</td>
		</tr>
		<?php
	}

	/**
	 * Renders a row for the Cash Inquiry List table.
	 *
	 * @param \WP_Post $cash_post The cash inquiry post object.
	 * @param int      $index The row index.
	 */
	public static function render_cash_inquiry_row( $cash_post, $index ) {
		$inquiry_id      = $cash_post->ID;
		$meta_data       = get_post_meta( $inquiry_id );
		$inquiry_data    = Maneli_Cash_Inquiry_Data::get_inquiry_data( $inquiry_id, $meta_data );
		$car_name        = $inquiry_data['product_name'];
		$full_name       = $inquiry_data['full_name'];
		$inquiry_status  = $inquiry_data['status'];
		$down_payment    = $inquiry_data['down_payment'];
		$expert_assigned = $inquiry_data['expert_assigned'];
		$expert_name     = $inquiry_data['expert_name'];
		$report_url      = $inquiry_data['report_url'];
		$status_class    = self::get_inquiry_status_class( $inquiry_status );
		$status_label    = self::get_inquiry_status_label( $inquiry_status );

		$is_expert_allowed = Maneli_Permission_Helpers::is_expert_allowed_to_view( $expert_assigned );
		$is_admin          = current_user_can( 'manage_maneli_inquiries' );

		if ( ! $is_admin && ! $is_expert_allowed ) {
			return;
		}
		?>
		<tr class="woocommerce-orders-table__row woocommerce-orders-table__row--status-<?php echo esc_attr( $inquiry_status ); ?>">
			<td data-title="<?php esc_attr_e( 'Row', 'maneli-car-inquiry' ); ?>">
				<?php echo esc_html( $index ); ?>
			</td>
			<td data-title="<?php esc_attr_e( 'Request ID', 'maneli-car-inquiry' ); ?>">
				#<?php echo esc_html( $inquiry_id ); ?>
			</td>
			<td data-title="<?php esc_attr_e( 'Date', 'maneli-car-inquiry' ); ?>">
				<?php echo esc_html( maneli_convert_to_jalali( $cash_post->post_date ) ); ?>
			</td>
			<td data-title="<?php esc_attr_e( 'Status', 'maneli-car-inquiry'); ?>" class="cash-status-column">
				<span class="maneli-inquiry-status <?php echo esc_attr( $status_class ); ?>"><?php echo esc_html( $status_label ); ?></span>
				<?php if ( $is_admin && $inquiry_status === 'pending' ) : ?>
					<a href="#" class="set-down-payment-btn" data-inquiry-id="<?php echo esc_attr( $inquiry_id ); ?>" style="display: block; font-size: 11px; margin-top: 5px;"><?php esc_html_e( 'Set Down Payment', 'maneli-car-inquiry' ); ?></a>
				<?php endif; ?>
			</td>
			<td data-title="<?php esc_attr_e( 'Car', 'maneli-car-inquiry' ); ?>">
				<?php echo esc_html( $car_name ); ?>
			</td>
			<td data-title="<?php esc_attr_e( 'Down Payment', 'maneli-car-inquiry' ); ?>">
				<?php echo $down_payment ? esc_html( maneli_format_price( $down_payment ) ) : esc_html__( 'N/A', 'maneli-car-inquiry' ); ?>
			</td>
			<td data-title="<?php esc_attr_e( 'Full Name', 'maneli-car-inquiry' ); ?>">
				<?php echo esc_html( $full_name ); ?>
			</td>
			<td data-title="<?php esc_attr_e( 'Expert', 'maneli-car-inquiry' ); ?>">
				<?php echo esc_html( $expert_name ); ?>
			</td>
			<td data-title="<?php esc_attr_e( 'Actions', 'maneli-car-inquiry' ); ?>" class="cash-inquiry-actions">
				<a href="<?php echo esc_url( $report_url ); ?>" class="button view"><?php esc_html_e( 'View Details', 'maneli-car-inquiry' ); ?></a>
				<?php if ( current_user_can( 'manage_maneli_inquiries' ) ) : ?>
					<button class="button delete-cash-list-btn" data-inquiry-id="<?php echo esc_attr( $inquiry_id ); ?>" data-inquiry-type="cash" style="background-color: var(--theme-red); border-color: var(--theme-red); margin-top: 5px;"><?php esc_html_e( 'Delete', 'maneli-car-inquiry' ); ?></button>
				<?php endif; ?>
			</td>
		</tr>
		<?php
	}

	/**
	 * Renders a row for the Customer Inquiry List table (Installment).
	 *
	 * @param \WP_Post $inquiry_post The inquiry post object.
	 * @param int      $index The row index.
	 */
	public static function render_customer_inquiry_row( $inquiry_post, $index ) {
		$inquiry_id     = $inquiry_post->ID;
		$inquiry_status = $inquiry_post->post_status;
		$meta_data      = get_post_meta( $inquiry_id );
		$inquiry_data   = Maneli_Inquiry_Data::get_inquiry_data( $inquiry_id, $meta_data );
		$car_name       = $inquiry_data['product_name'];
		$report_url     = $inquiry_data['report_url'];

		?>
		<tr class="woocommerce-orders-table__row woocommerce-orders-table__row--status-<?php echo esc_attr( $inquiry_status ); ?>">
			<td data-title="<?php esc_attr_e( 'Row', 'maneli-car-inquiry' ); ?>">
				<?php echo esc_html( $index ); ?>
			</td>
			<td data-title="<?php esc_attr_e( 'Request ID', 'maneli-car-inquiry' ); ?>">
				#<?php echo esc_html( $inquiry_id ); ?>
			</td>
			<td data-title="<?php esc_attr_e( 'Date', 'maneli-car-inquiry' ); ?>">
				<?php echo esc_html( maneli_convert_to_jalali( $inquiry_post->post_date ) ); ?>
			</td>
			<td data-title="<?php esc_attr_e( 'Status', 'maneli-car-inquiry' ); ?>">
				<?php echo self::render_inquiry_status_badge( $inquiry_status ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			</td>
			<td data-title="<?php esc_attr_e( 'Car', 'maneli-car-inquiry' ); ?>">
				<?php echo esc_html( $car_name ); ?>
			</td>
			<td class="woocommerce-orders-table__cell-order-actions">
				<a href="<?php echo esc_url( $report_url ); ?>" class="button view"><?php esc_html_e( 'View Details', 'maneli-car-inquiry' ); ?></a>
			</td>
		</tr>
		<?php
	}

	/**
	 * Renders a row for the Customer Cash Inquiry List table.
	 *
	 * @param \WP_Post $cash_post The cash inquiry post object.
	 * @param int      $index The row index.
	 */
	public static function render_customer_cash_inquiry_row( $cash_post, $index ) {
		$inquiry_id     = $cash_post->ID;
		$meta_data      = get_post_meta( $inquiry_id );
		$inquiry_data   = Maneli_Cash_Inquiry_Data::get_inquiry_data( $inquiry_id, $meta_data );
		$car_name       = $inquiry_data['product_name'];
		$inquiry_status = $inquiry_data['status'];
		$down_payment   = $inquiry_data['down_payment'];
		$report_url     = $inquiry_data['report_url'];
		$status_class   = self::get_inquiry_status_class( $inquiry_status );
		$status_label   = self::get_inquiry_status_label( $inquiry_status );

		?>
		<tr class="woocommerce-orders-table__row woocommerce-orders-table__row--status-<?php echo esc_attr( $inquiry_status ); ?>">
			<td data-title="<?php esc_attr_e( 'Row', 'maneli-car-inquiry' ); ?>">
				<?php echo esc_html( $index ); ?>
			</td>
			<td data-title="<?php esc_attr_e( 'Request ID', 'maneli-car-inquiry' ); ?>">
				#<?php echo esc_html( $inquiry_id ); ?>
			</td>
			<td data-title="<?php esc_attr_e( 'Date', 'maneli-car-inquiry' ); ?>">
				<?php echo esc_html( maneli_convert_to_jalali( $cash_post->post_date ) ); ?>
			</td>
			<td data-title="<?php esc_attr_e( 'Status', 'maneli-car-inquiry'); ?>">
				<span class="maneli-inquiry-status <?php echo esc_attr( $status_class ); ?>"><?php echo esc_html( $status_label ); ?></span>
			</td>
			<td data-title="<?php esc_attr_e( 'Car', 'maneli-car-inquiry' ); ?>">
				<?php echo esc_html( $car_name ); ?>
			</td>
			<td data-title="<?php esc_attr_e( 'Down Payment', 'maneli-car-inquiry' ); ?>">
				<?php echo $down_payment ? esc_html( maneli_format_price( $down_payment ) ) : esc_html__( 'N/A', 'maneli-car-inquiry' ); ?>
			</td>
			<td data-title="<?php esc_attr_e( 'Actions', 'maneli-car-inquiry' ); ?>">
				<a href="<?php echo esc_url( $report_url ); ?>" class="button view"><?php esc_html_e( 'View Details', 'maneli-car-inquiry' ); ?></a>
			</td>
		</tr>
		<?php
	}

	/**
	 * Renders the HTML for a single, formatted meta detail row.
	 *
	 * @param string $label The display label for the detail.
	 * @param string $value The value of the detail.
	 * @param string $data_key Optional data key for attributes.
	 * @return string The HTML output.
	 */
	public static function render_meta_detail_row( $label, $value, $data_key = '' ) {
		$data_attr = $data_key ? ' data-key="' . esc_attr( $data_key ) . '"' : '';
		return sprintf(
			'<div class="maneli-report-meta-item" %s>
				<span class="maneli-report-meta-label">%s:</span>
				<span class="maneli-report-meta-value">%s</span>
			</div>',
			$data_attr, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			esc_html( $label ),
			wp_kses_post( $value )
		);
	}
}