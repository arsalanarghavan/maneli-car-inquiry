<?php
/**
 * Demo Data Generator Class
 * 
 * Generates sample data for testing and demonstration purposes.
 * Includes sample customers, experts, cars, and inquiries.
 * 
 * @package ManaliCarInquiry
 * @subpackage Includes
 */

class Maneli_Demo_Data_Generator {
    
    /**
     * The plugin's database handler
     * @var Maneli_Database
     */
    private $database;
    
    /**
     * The plugin's CPT handler
     * @var Maneli_CPT_Handler
     */
    private $cpt_handler;

    /**
     * Constructor
     */
    public function __construct() {
        $this->database = new Maneli_Database();
        $this->cpt_handler = new Maneli_CPT_Handler();
    }

    /**
     * Generate all demo data
     * 
     * @return array Status and results
     */
    public function generate_demo_data() {
        $results = [
            'customers' => 0,
            'experts' => 0,
            'cars' => 0,
            'cash_inquiries' => 0,
            'installment_inquiries' => 0,
            'errors' => []
        ];

        try {
            // Generate demo customers
            $results['customers'] = $this->generate_demo_customers();
            
            // Generate demo experts
            $results['experts'] = $this->generate_demo_experts();
            
            // Generate demo cars
            $results['cars'] = $this->generate_demo_cars();
            
            // Get IDs for creating inquiries
            $customer_ids = $this->get_demo_user_ids('customer');
            $expert_ids = $this->get_demo_user_ids('expert');
            $car_ids = $this->get_demo_car_ids();
            
            // Generate demo cash inquiries
            if (!empty($customer_ids) && !empty($car_ids)) {
                $results['cash_inquiries'] = $this->generate_demo_cash_inquiries(
                    $customer_ids,
                    $expert_ids,
                    $car_ids
                );
            }
            
            // Generate demo installment inquiries
            if (!empty($customer_ids) && !empty($car_ids)) {
                $results['installment_inquiries'] = $this->generate_demo_installment_inquiries(
                    $customer_ids,
                    $expert_ids,
                    $car_ids
                );
            }

        } catch ( Exception $e ) {
            $results['errors'][] = $e->getMessage();
        }

        return $results;
    }

    /**
     * Generate 30 demo customers with realistic data
     * 
     * @return int Number of customers created
     */
    private function generate_demo_customers() {
        $count = 0;
        $existing_count = count( $this->get_demo_user_ids( 'customer' ) );
        
        // Only generate if not already created
        if ( $existing_count >= 30 ) {
            return $existing_count;
        }

        $first_names = $this->get_demo_first_names();
        $last_names = $this->get_demo_last_names();
        $cities = $this->get_demo_cities();
        $addresses = $this->get_demo_addresses();

        for ( $i = 1; $i <= 30; $i++ ) {
            $username = "demo_customer_" . $i;
            
            // Check if user already exists
            if ( username_exists( $username ) ) {
                $count++;
                continue;
            }

            $first_name = $first_names[ array_rand( $first_names ) ];
            $last_name = $last_names[ array_rand( $last_names ) ];
            $email = sanitize_email( strtolower( $first_name . '.' . $last_name . $i . '@example.com' ) );

            $user_data = [
                'user_login'    => $username,
                'user_email'    => $email,
                'user_pass'     => wp_generate_password(),
                'first_name'    => $first_name,
                'last_name'     => $last_name,
                'role'          => 'customer'
            ];

            $user_id = wp_insert_user( $user_data );

            if ( ! is_wp_error( $user_id ) ) {
                // Add comprehensive customer metadata
                $mobile = '0' . wp_rand( 9, 9 ) . wp_rand( 10000000, 99999999 );
                $national_id = wp_rand( 10000000, 99999999 );
                
                update_user_meta( $user_id, 'mobile_number', $mobile );
                update_user_meta( $user_id, 'national_id', $national_id );
                update_user_meta( $user_id, 'city', $cities[ array_rand( $cities ) ] );
                update_user_meta( $user_id, 'address', $addresses[ array_rand( $addresses ) ] );
                update_user_meta( $user_id, 'birth_year', wp_rand( 1960, 1990 ) );
                update_user_meta( $user_id, 'occupation', $this->get_demo_occupations()[ array_rand( $this->get_demo_occupations() ) ] );
                update_user_meta( $user_id, 'income_range', wp_rand( 5, 50 ) . ' میلیون' );
                update_user_meta( $user_id, 'demo_user', '1' );
                $count++;
            }
        }

        return $count;
    }

    /**
     * Generate 30 demo experts with realistic data
     * 
     * @return int Number of experts created
     */
    private function generate_demo_experts() {
        $count = 0;
        $existing_count = count( $this->get_demo_user_ids( 'expert' ) );
        
        // Only generate if not already created
        if ( $existing_count >= 30 ) {
            return $existing_count;
        }

        $first_names = $this->get_demo_first_names();
        $last_names = $this->get_demo_last_names();
        $cities = $this->get_demo_cities();
        $specializations = $this->get_demo_specializations();

        for ( $i = 1; $i <= 30; $i++ ) {
            $username = "demo_expert_" . $i;
            
            // Check if user already exists
            if ( username_exists( $username ) ) {
                $count++;
                continue;
            }

            $first_name = $first_names[ array_rand( $first_names ) ];
            $last_name = $last_names[ array_rand( $last_names ) ];
            $email = sanitize_email( strtolower( $first_name . '.' . $last_name . '.expert' . $i . '@example.com' ) );

            $user_data = [
                'user_login'    => $username,
                'user_email'    => $email,
                'user_pass'     => wp_generate_password(),
                'first_name'    => $first_name,
                'last_name'     => $last_name,
                'role'          => 'maneli_expert'
            ];

            $user_id = wp_insert_user( $user_data );

            if ( ! is_wp_error( $user_id ) ) {
                // Add comprehensive expert metadata
                $mobile = '0' . wp_rand( 9, 9 ) . wp_rand( 10000000, 99999999 );
                $experience_years = wp_rand( 2, 15 );
                
                update_user_meta( $user_id, 'mobile_number', $mobile );
                update_user_meta( $user_id, 'city', $cities[ array_rand( $cities ) ] );
                update_user_meta( $user_id, 'specialization', $specializations[ array_rand( $specializations ) ] );
                update_user_meta( $user_id, 'experience_years', $experience_years );
                update_user_meta( $user_id, 'expert_bio', "متخصص فروش خودرو با تجربه $experience_years سال در صنعت خودرو" );
                update_user_meta( $user_id, 'certification', $this->get_demo_certifications()[ array_rand( $this->get_demo_certifications() ) ] );
                update_user_meta( $user_id, 'rating', wp_rand( 4, 5 ) . '.' . wp_rand( 0, 9 ) );
                update_user_meta( $user_id, 'total_sales', wp_rand( 50, 500 ) );
                update_user_meta( $user_id, 'expert_active', 'yes' );
                update_user_meta( $user_id, 'demo_user', '1' );
                $count++;
            }
        }

        return $count;
    }

    /**
     * Generate 30 demo cars with realistic and varied data
     * 
     * @return int Number of cars created
     */
    private function generate_demo_cars() {
        $count = 0;
        $existing_count = count( $this->get_demo_car_ids() );
        
        // Only generate if not already created
        if ( $existing_count >= 30 ) {
            return $existing_count;
        }

        // First, create categories if they don't exist
        $this->create_demo_car_categories();

        $cars_data = $this->get_demo_cars_data();
        $color_options = $this->get_demo_colors();
        $conditions = [ 'نو', 'یک کاره', 'دو کاره', 'سه کاره', 'چند کاره' ];
        $statuses = [ 'در دسترس', 'ناموجود', 'فروش ویژه' ];

        $index = 0;
        foreach ( $cars_data as $car ) {
            if ( $index >= 30 ) {
                break;
            }

            $brand = $car['brand'];
            $model = $car['model'];
            $year = 1400 + wp_rand( 0, 3 );
            $color = $color_options[ array_rand( $color_options ) ];
            $condition = $conditions[ array_rand( $conditions ) ];
            $mileage = wp_rand( 10000, 200000 );
            $engine = isset( $car['engine'] ) ? $car['engine'] : wp_rand( 1000, 2000 );
            
            $cash_price = intval( $car['base_price'] + wp_rand( -50000000, 100000000 ) );
            $installment_price = intval( $cash_price * 1.15 );
            $min_downpayment = intval( $cash_price * 0.2 ); // 20% حداقل پیش‌پرداخت
            
            $body_type = isset( $car['body_type'] ) ? $car['body_type'] : 'سدان';
            $fuel_type = isset( $car['fuel_type'] ) ? $car['fuel_type'] : 'بنزین';
            $category = isset( $car['category'] ) ? $car['category'] : 'خودروهای ایرانی';
            $status = $statuses[ wp_rand( 0, count( $statuses ) - 1 ) ];
            
            $post_data = [
                'post_type'    => 'product',
                'post_title'   => "$brand $model $year - $color ($condition)",
                'post_content' => sprintf(
                    "برند: %s\nمدل: %s\nسال: %s\nرنگ: %s\nوضعیت: %s\nمتراژ: %s کیلومتر\nموتور: %s سی سی\nنوع سوخت: %s",
                    $brand, $model, $year, $color, $condition, number_format( $mileage ), $engine, $fuel_type
                ),
                'post_status'  => 'publish',
                'tax_input'    => [
                    'product_cat' => [ $this->get_category_id_by_name( $category ) ]
                ],
                'meta_input'   => [
                    'demo_car'              => '1',
                    'car_brand'             => $brand,
                    'car_model'             => $model,
                    'car_year'              => $year,
                    'car_color'             => $color,
                    'car_condition'         => $condition,
                    'car_mileage'           => $mileage,
                    'car_engine'            => $engine,
                    'car_transmission'      => wp_rand( 0, 1 ) ? 'دستی' : 'اتوماتیک',
                    'car_body_type'         => $body_type,
                    'car_fuel_type'         => $fuel_type,
                    'cash_price'            => $cash_price,
                    'installment_price'     => $installment_price,
                    'min_downpayment'       => $min_downpayment,
                    'available_colors'      => implode( ', ', array_slice( $color_options, 0, wp_rand( 3, 5 ) ) ),
                    'sale_status'           => $status,
                    'car_features'          => implode( ', ', $this->get_demo_car_features() ),
                ]
            ];

            $post_id = wp_insert_post( $post_data );
            if ( $post_id && ! is_wp_error( $post_id ) ) {
                // Set product type
                wp_set_object_terms( $post_id, 'simple', 'product_type' );
                
                // Update WooCommerce product data (prices as integers, no decimals)
                update_post_meta( $post_id, '_regular_price', (int) $cash_price );
                update_post_meta( $post_id, '_price', (int) $cash_price );
                update_post_meta( $post_id, '_stock', 10 );
                update_post_meta( $post_id, 'min_downpayment', (int) $min_downpayment );
                
                // Set stock status based on sale status
                if ( $status === 'ناموجود' ) {
                    update_post_meta( $post_id, '_stock_status', 'outofstock' );
                } else {
                    update_post_meta( $post_id, '_stock_status', 'instock' );
                }
                
                $count++;
                $index++;
            }
        }

        return $count;
    }

    /**
     * Generate 30 demo cash inquiries with realistic and varied data
     * 
     * @param array $customer_ids
     * @param array $expert_ids
     * @param array $car_ids
     * @return int Number of inquiries created
     */
    private function generate_demo_cash_inquiries( $customer_ids, $expert_ids, $car_ids ) {
        $count = 0;
        $statuses = array_keys( $this->cpt_handler->get_all_cash_inquiry_statuses() );
        $payment_methods = [ 'نقد', 'چک', 'حواله', 'کارت اعتباری' ];
        
        // Create a balanced distribution of 30 inquiries across statuses
        // Total 30 inquiries, distributed evenly: ~3-4 per status
        $status_distribution = [];
        foreach ( $statuses as $status ) {
            $status_distribution[ $status ] = 0;
        }
        
        // Distribute 30 inquiries evenly
        for ( $i = 0; $i < 30; $i++ ) {
            $status = $statuses[ $i % count( $statuses ) ];
            $status_distribution[ $status ]++;
        }

        foreach ( $statuses as $status ) {
            $inquiries_for_status = $status_distribution[ $status ];
            
            for ( $i = 0; $i < $inquiries_for_status; $i++ ) {
                $customer_id = $customer_ids[ array_rand( $customer_ids ) ];
                $car_id = $car_ids[ array_rand( $car_ids ) ];
                $expert_id = ! empty( $expert_ids ) ? $expert_ids[ array_rand( $expert_ids ) ] : null;
                
                $customer = get_user_by( 'id', $customer_id );
                $car_post = get_post( $car_id );
                $car_title = isset( $car_post ) ? $car_post->post_title : "خودروی نمونه";
                
                // Create varied inquiry dates
                $days_ago = wp_rand( 1, 60 );
                $inquiry_date = date( 'Y-m-d H:i:s', strtotime( "-$days_ago days" ) );
                $customer_phone = get_user_meta( $customer_id, 'mobile_number', true );
                $expert_name = $expert_id ? get_user_by( 'id', $expert_id )->display_name : '';
                
                // Split customer name for cash inquiry
                $name_parts = explode( ' ', $customer->display_name );
                $first_name = isset( $name_parts[0] ) ? $name_parts[0] : '';
                $last_name = isset( $name_parts[1] ) ? $name_parts[1] : '';

                $payment_method = $payment_methods[ array_rand( $payment_methods ) ];

                $post_data = [
                    'post_type'    => 'cash_inquiry',
                    'post_title'   => "#" . (5000 + $count) . " - " . $car_title,
                    'post_content' => sprintf(
                        "مشتری: %s\nموبایل: %s\nخودرو: %s\nنوع پرداخت: %s\nتاریخ: %s",
                        $customer->display_name,
                        $customer_phone,
                        $car_title,
                        $payment_method,
                        $inquiry_date
                    ),
                    'post_status'  => 'publish',
                    'post_author'  => 1,
                    'post_date'    => $inquiry_date,
                    'meta_input'   => [
                        'demo_inquiry'      => '1',
                        'customer_id'       => $customer_id,
                        'product_id'        => $car_id,
                        'cash_inquiry_status'  => $status,
                        'assigned_expert'   => $expert_id,
                        'assigned_expert_id' => $expert_id,
                        'assigned_expert_name' => $expert_name,
                        'inquiry_date'      => $inquiry_date,
                        'cash_first_name'   => $first_name,
                        'cash_last_name'    => $last_name,
                        'mobile_number'     => $customer_phone,
                        'customer_name'     => $customer->display_name,
                        'customer_email'    => $customer->user_email,
                        'expert_name'       => $expert_name,
                        'expert_note'       => $this->get_demo_inquiry_notes()[ array_rand( $this->get_demo_inquiry_notes() ) ],
                        'expert_notes'      => [],
                        'expert_decision'   => $status === 'completed' ? 'approved' : ($status === 'rejected' ? 'rejected' : ''),
                        'meeting_date'      => $status === 'completed' ? date( 'Y-m-d', strtotime( "-" . wp_rand( 1, 30 ) . " days" ) ) : '',
                        'meeting_time'      => $status === 'completed' ? wp_rand( 9, 17 ) . ':' . wp_rand( 0, 59 ) : '',
                        'payment_method'    => $payment_method,
                        'offered_price'     => wp_rand( 500000000, 2000000000 ),
                        'cash_car_color'    => get_post_meta( $car_id, 'car_color', true ),
                        'original_product_price' => get_post_meta( $car_id, 'cash_price', true ),
                        'cash_down_payment' => intval( get_post_meta( $car_id, 'cash_price', true ) * 0.2 ),
                        'referred_at'       => $status !== 'new' ? date( 'Y-m-d H:i:s', strtotime( $inquiry_date . " +1 day" ) ) : '',
                        'in_progress_at'    => in_array( $status, [ 'in_progress', 'completed', 'rejected' ] ) ? date( 'Y-m-d H:i:s', strtotime( $inquiry_date . " +2 days" ) ) : '',
                        'downpayment_requested_at' => $status === 'awaiting_payment' || $status === 'completed' ? date( 'Y-m-d H:i:s', strtotime( $inquiry_date . " +3 days" ) ) : '',
                        'downpayment_received_at' => $status === 'completed' ? date( 'Y-m-d H:i:s', strtotime( $inquiry_date . " +4 days" ) ) : '',
                        'meeting_scheduled_at' => $status === 'completed' ? date( 'Y-m-d H:i:s', strtotime( $inquiry_date . " +5 days" ) ) : '',
                        'completed_at'      => $status === 'completed' ? date( 'Y-m-d H:i:s', strtotime( $inquiry_date . " +10 days" ) ) : '',
                        'rejected_at'       => $status === 'rejected' ? date( 'Y-m-d H:i:s', strtotime( $inquiry_date . " +5 days" ) ) : '',
                        'notes'             => $this->get_demo_inquiry_notes()[ array_rand( $this->get_demo_inquiry_notes() ) ],
                        'visit_date'        => $status === 'completed' ? date( 'Y-m-d', strtotime( "-" . wp_rand( 1, 30 ) . " days" ) ) : '',
                        'sms_history'       => [],
                    ]
                ];

                $post_id = wp_insert_post( $post_data );
                if ( $post_id && ! is_wp_error( $post_id ) ) {
                    $count++;
                }
            }
        }

        return $count;
    }

    /**
     * Generate 30 demo installment inquiries with realistic and varied data
     * 
     * @param array $customer_ids
     * @param array $expert_ids
     * @param array $car_ids
     * @return int Number of inquiries created
     */
    private function generate_demo_installment_inquiries( $customer_ids, $expert_ids, $car_ids ) {
        $count = 0;
        $statuses = array_keys( $this->cpt_handler->get_all_statuses() );
        $tracking_statuses = array_keys( $this->cpt_handler->get_tracking_statuses() );
        
        // Create a balanced distribution of 30 inquiries across statuses
        // Total 30 inquiries, distributed evenly: ~3-4 per status
        $status_distribution = [];
        foreach ( $statuses as $status ) {
            $status_distribution[ $status ] = 0;
        }
        
        // Distribute 30 inquiries evenly
        for ( $i = 0; $i < 30; $i++ ) {
            $status = $statuses[ $i % count( $statuses ) ];
            $status_distribution[ $status ]++;
        }

        foreach ( $statuses as $status ) {
            $inquiries_for_status = $status_distribution[ $status ];
            
            for ( $i = 0; $i < $inquiries_for_status; $i++ ) {
                $customer_id = $customer_ids[ array_rand( $customer_ids ) ];
                $car_id = $car_ids[ array_rand( $car_ids ) ];
                $expert_id = ! empty( $expert_ids ) ? $expert_ids[ array_rand( $expert_ids ) ] : null;
                $tracking_status = $tracking_statuses[ array_rand( $tracking_statuses ) ];
                
                $customer = get_user_by( 'id', $customer_id );
                $car_post = get_post( $car_id );
                $car_title = isset( $car_post ) ? $car_post->post_title : "خودروی نمونه";
                
                // Create varied inquiry dates
                $days_ago = wp_rand( 1, 60 );
                $inquiry_date = date( 'Y-m-d H:i:s', strtotime( "-$days_ago days" ) );
                $customer_phone = get_user_meta( $customer_id, 'mobile_number', true );
                $expert_name = $expert_id ? get_user_by( 'id', $expert_id )->display_name : '';
                
                // Split customer name for installment inquiry
                $name_parts = explode( ' ', $customer->display_name );
                $first_name = isset( $name_parts[0] ) ? $name_parts[0] : '';
                $last_name = isset( $name_parts[1] ) ? $name_parts[1] : '';
                
                $monthly_income = wp_rand( 10000000, 100000000 );
                $requested_amount = wp_rand( 500000000, 2000000000 );
                $requested_months = wp_rand( 12, 60 );
                $monthly_payment = intval( $requested_amount / $requested_months );

                $post_data = [
                    'post_type'    => 'inquiry',
                    'post_title'   => "#" . (6000 + $count) . " - " . $car_title,
                    'post_content' => sprintf(
                        "مشتری: %s\nموبایل: %s\nخودرو: %s\nمقدار درخواستی: %s تومان\nمدت‌زمان: %s ماه\nقسط ماهانه: %s تومان",
                        $customer->display_name,
                        $customer_phone,
                        $car_title,
                        number_format( $requested_amount ),
                        $requested_months,
                        number_format( $monthly_payment )
                    ),
                    'post_status'  => 'publish',
                    'post_author'  => $customer_id,
                    'post_date'    => $inquiry_date,
                    'meta_input'   => [
                        'demo_inquiry'         => '1',
                        'customer_id'          => $customer_id,
                        'product_id'           => $car_id,
                        'inquiry_status'       => $status,
                        'tracking_status'      => $tracking_status,
                        'assigned_expert'      => $expert_id,
                        'assigned_expert_id'   => $expert_id,
                        'assigned_expert_name' => $expert_name,
                        'inquiry_date'         => $inquiry_date,
                        'first_name'           => $first_name,
                        'last_name'            => $last_name,
                        'father_name'          => $this->get_demo_first_names()[ array_rand( $this->get_demo_first_names() ) ],
                        'national_code'        => wp_rand( 1000000000, 9999999999 ),
                        'birth_date'           => date( 'Y-m-d', strtotime( "-" . wp_rand( 20, 60 ) . " years" ) ),
                        'mobile_number'        => $customer_phone,
                        'phone_number'         => $customer_phone,
                        'customer_name'        => $customer->display_name,
                        'email'                => $customer->user_email,
                        'job_type'             => 'employee',
                        'occupation'           => $this->get_demo_occupations()[ array_rand( $this->get_demo_occupations() ) ],
                        'income_level'         => wp_rand( 10000000, 100000000 ),
                        'residency_status'     => 'owner',
                        'workplace_status'     => 'permanent',
                        'address'              => $this->get_demo_addresses()[ array_rand( $this->get_demo_addresses() ) ] . ' - ' . $this->get_demo_cities()[ array_rand( $this->get_demo_cities() ) ],
                        'expert_name'          => $expert_name,
                        'monthly_income'       => $monthly_income,
                        'requested_amount'     => $requested_amount,
                        'requested_months'     => $requested_months,
                        'monthly_payment'      => $monthly_payment,
                        'employment_type'      => $this->get_demo_employment_types()[ array_rand( $this->get_demo_employment_types() ) ],
                        'employment_duration'  => wp_rand( 1, 20 ),
                        'guarantor_name'       => $this->get_demo_first_names()[ array_rand( $this->get_demo_first_names() ) ] . ' ' . $this->get_demo_last_names()[ array_rand( $this->get_demo_last_names() ) ],
                        'guarantor_phone'      => '0' . wp_rand( 9, 9 ) . wp_rand( 10000000, 99999999 ),
                        'bank_name'            => 'بانک ملت',
                        'account_number'       => wp_rand( 1000000000, 9999999999 ),
                        'branch_code'          => wp_rand( 100, 999 ),
                        'branch_name'          => $this->get_demo_cities()[ array_rand( $this->get_demo_cities() ) ],
                        'approval_status'      => $status === 'completed' ? 'تایید شده' : 'در انتظار بررسی',
                        'notes'                => $this->get_demo_inquiry_notes()[ array_rand( $this->get_demo_inquiry_notes() ) ],
                        'rejection_reason'     => $status === 'rejected' ? 'متأسفانه درخواست شما تأیید نشد.' : '',
                        'follow_up_date'       => $tracking_status === 'follow_up_scheduled' ? date( 'Y-m-d', strtotime( $inquiry_date . " +7 days" ) ) : '',
                        'meeting_date'         => $tracking_status === 'meeting_scheduled' || $status === 'completed' ? date( 'Y-m-d', strtotime( $inquiry_date . " +10 days" ) ) : '',
                        'meeting_time'         => $tracking_status === 'meeting_scheduled' || $status === 'completed' ? wp_rand( 9, 17 ) . ':' . wp_rand( 0, 59 ) : '',
                        'followup_date'        => $tracking_status === 'follow_up_scheduled' ? date( 'Y-m-d', strtotime( $inquiry_date . " +7 days" ) ) : '',
                        'cancel_reason'        => $tracking_status === 'cancelled' ? 'درخواست توسط مشتری لغو شد.' : '',
                        'followup_history'     => [],
                        'followup_note'        => $this->get_demo_inquiry_notes()[ array_rand( $this->get_demo_inquiry_notes() ) ],
                        'expert_notes'         => [],
                        'referred_at'          => $tracking_status !== 'new' ? date( 'Y-m-d H:i:s', strtotime( $inquiry_date . " +1 day" ) ) : '',
                        'in_progress_at'       => in_array( $tracking_status, [ 'in_progress', 'completed', 'rejected', 'cancelled' ] ) ? date( 'Y-m-d H:i:s', strtotime( $inquiry_date . " +2 days" ) ) : '',
                        'followup_scheduled_at' => $tracking_status === 'follow_up_scheduled' ? date( 'Y-m-d H:i:s', strtotime( $inquiry_date . " +3 days" ) ) : '',
                        'meeting_scheduled_at' => $tracking_status === 'meeting_scheduled' || $status === 'completed' ? date( 'Y-m-d H:i:s', strtotime( $inquiry_date . " +5 days" ) ) : '',
                        'completed_at'         => $status === 'completed' ? date( 'Y-m-d H:i:s', strtotime( $inquiry_date . " +10 days" ) ) : '',
                        'rejected_at'          => $status === 'rejected' ? date( 'Y-m-d H:i:s', strtotime( $inquiry_date . " +5 days" ) ) : '',
                        'cancelled_at'         => $tracking_status === 'cancelled' ? date( 'Y-m-d H:i:s', strtotime( $inquiry_date . " +7 days" ) ) : '',
                        'requested_documents'  => [],
                        'uploaded_documents'   => [],
                        'sms_history'          => [],
                        // Finnotech API data
                        '_finotex_response_data' => [],
                        '_finnotech_credit_risk_data' => [
                            'result' => [
                                'riskScore' => wp_rand( 200, 900 ),
                                'transactionProhibitionStatus' => 'none',
                            ],
                            'status' => 'success'
                        ],
                        '_finnotech_credit_score_data' => [
                            'result' => [
                                'creditScore' => wp_rand( 500, 950 ),
                                'negativeFactors' => [],
                                'scoreHistory' => [
                                    [
                                        'date' => date( 'Y-m-d', strtotime( $inquiry_date . ' -90 days' ) ),
                                        'previousScore' => null,
                                        'newScore' => wp_rand( 400, 750 ),
                                        'changeReason' => 'اول ثبت نام',
                                    ],
                                    [
                                        'date' => date( 'Y-m-d', strtotime( $inquiry_date . ' -60 days' ) ),
                                        'previousScore' => wp_rand( 400, 750 ),
                                        'newScore' => wp_rand( 500, 850 ),
                                        'changeReason' => 'بهبود سابقه اعتباری',
                                    ],
                                    [
                                        'date' => date( 'Y-m-d', strtotime( $inquiry_date . ' -30 days' ) ),
                                        'previousScore' => wp_rand( 500, 850 ),
                                        'newScore' => wp_rand( 500, 950 ),
                                        'changeReason' => 'پرداخت‌های منظم',
                                    ],
                                ]
                            ],
                            'status' => 'success'
                        ],
                        '_finnotech_collaterals_data' => [
                            'result' => [
                                'totalContracts' => wp_rand( 1, 3 ),
                                'totalLoanAmount' => 0,
                                'totalFacilityAmount' => 0,
                                'contracts' => [
                                    [
                                        'contractNumber' => 'CNT-' . wp_rand( 100000, 999999 ),
                                        'type' => 'وام شخصی',
                                        'amount' => 0,
                                        'status' => 'فعال',
                                    ],
                                ]
                            ],
                            'status' => 'success'
                        ],
                        '_finnotech_cheque_color_data' => [
                            'result' => [
                                'chequeColor' => 2, // 2 = yellow (درهم خورده)
                                'chequeStatus' => 'یک چک برگشتی یا حداکثر ۵۰ میلیون ریال تعهدات برگشتی.',
                            ],
                            'status' => 'success'
                        ],
                        // Special meta keys for installment inquiry display (Maneli Wizard format)
                        'maneli_inquiry_total_price' => $requested_amount,
                        'maneli_inquiry_down_payment' => intval( $requested_amount * 0.2 ),
                        'maneli_inquiry_term_months'  => $requested_months,
                        'maneli_inquiry_installment'  => $monthly_payment,
                        'issuer_type'          => 'self',
                    ]
                ];

                $post_id = wp_insert_post( $post_data );
                if ( $post_id && ! is_wp_error( $post_id ) ) {
                    $count++;
                }
            }
        }

        return $count;
    }

    /**
     * Get demo user IDs by role
     * 
     * @param string $role User role
     * @return array Array of user IDs
     */
    private function get_demo_user_ids( $role = 'customer' ) {
        $args = [
            'meta_query' => [
                [
                    'key'     => 'demo_user',
                    'value'   => '1',
                    'compare' => '='
                ]
            ],
            'role' => $role === 'customer' ? 'customer' : 'maneli_expert',
            'fields' => 'ID'
        ];

        return get_users( $args );
    }

    /**
     * Get demo car IDs
     * 
     * @return array Array of car post IDs
     */
    private function get_demo_car_ids() {
        $args = [
            'post_type'  => 'product',
            'posts_per_page' => -1,
            'meta_query' => [
                [
                    'key'     => 'demo_car',
                    'value'   => '1',
                    'compare' => '='
                ]
            ],
            'fields' => 'ids'
        ];

        return get_posts( $args );
    }

    /**
     * Delete all demo data
     * 
     * @return array Status of deletion
     */
    public function delete_demo_data() {
        $results = [
            'customers_deleted' => 0,
            'experts_deleted' => 0,
            'cars_deleted' => 0,
            'inquiries_deleted' => 0,
            'errors' => []
        ];

        try {
            // Delete demo customers
            $customer_ids = $this->get_demo_user_ids( 'customer' );
            foreach ( $customer_ids as $user_id ) {
                if ( wp_delete_user( $user_id ) ) {
                    $results['customers_deleted']++;
                }
            }

            // Delete demo experts
            $expert_ids = $this->get_demo_user_ids( 'expert' );
            foreach ( $expert_ids as $user_id ) {
                if ( wp_delete_user( $user_id ) ) {
                    $results['experts_deleted']++;
                }
            }

            // Delete demo cars
            $car_ids = $this->get_demo_car_ids();
            foreach ( $car_ids as $post_id ) {
                if ( wp_delete_post( $post_id, true ) ) {
                    $results['cars_deleted']++;
                }
            }

            // Delete demo inquiries
            $cash_inquiries = get_posts( [
                'post_type'  => 'cash_inquiry',
                'posts_per_page' => -1,
                'meta_query' => [
                    [
                        'key'     => 'demo_inquiry',
                        'value'   => '1',
                        'compare' => '='
                    ]
                ],
                'fields' => 'ids'
            ] );

            $installment_inquiries = get_posts( [
                'post_type'  => 'inquiry',
                'posts_per_page' => -1,
                'meta_query' => [
                    [
                        'key'     => 'demo_inquiry',
                        'value'   => '1',
                        'compare' => '='
                    ]
                ],
                'fields' => 'ids'
            ] );

            foreach ( array_merge( $cash_inquiries, $installment_inquiries ) as $post_id ) {
                if ( wp_delete_post( $post_id, true ) ) {
                    $results['inquiries_deleted']++;
                }
            }

        } catch ( Exception $e ) {
            $results['errors'][] = $e->getMessage();
        }

        return $results;
    }

    /**
     * Get demo occupations
     * 
     * @return array
     */
    private function get_demo_occupations() {
        return [
            'کارمند', 'معلم', 'مهندس', 'پزشک', 'وکیل', 'معماری',
            'تاجر', 'کشاورز', 'صنعتگر', 'متخصص IT', 'حسابدار',
            'بازاریاب', 'خریدار', 'شهردار', 'فروشنده', 'دستگاه‌دار'
        ];
    }

    /**
     * Get demo cities
     * 
     * @return array
     */
    private function get_demo_cities() {
        return [
            'تهران', 'کرج', 'قزوین', 'رشت', 'اردبیل', 'تبریز',
            'مشهد', 'سمنان', 'گرگان', 'قم', 'اصفهان', 'یزد',
            'کرمان', 'بندرعباس', 'اهواز', 'شیراز', 'کاشان', 'سنندج'
        ];
    }

    /**
     * Get demo addresses
     * 
     * @return array
     */
    private function get_demo_addresses() {
        return [
            'خیابان رسالت، بلوار شهید مطهری', 'خیابان ولیعصر، پلاک 42',
            'خیابان انقلاب، نزدیک میدان تجریش', 'درکه، خیابان پیروزی',
            'چالوس، نزدیک باغ تالار', 'ستارخان، پلاک 105',
            'پیروزی، بین پاسداران و فردوسی', 'شریعتی، نزدیک هلال احمر',
            'سعادت آباد، خیابان طالب', 'نیاوران، خیابان گلبرگ',
            'داراک، خیابان آزادی', 'کوی نور، پلاک 24'
        ];
    }

    /**
     * Get demo specializations
     * 
     * @return array
     */
    private function get_demo_specializations() {
        return [
            'فروش خودروهای سبک', 'فروش خودروهای سنگین',
            'تخصص وام و تسهیلات', 'تخصص معامله نقدی',
            'تخصص خودروهای لوکس', 'تخصص خودروهای ارزان‌قیمت',
            'مشاور مالی', 'مشاور قانونی',
            'تخصص واردات', 'تخصص ساخت داخل'
        ];
    }

    /**
     * Get demo certifications
     * 
     * @return array
     */
    private function get_demo_certifications() {
        return [
            'مدرک فروش ماشین از اتاق بازرگانی',
            'گواهی دوره تخصصی فروش خودرو',
            'شهادت نامه دوره مشاور مالی',
            'گواهی دوره آنلاین فروش',
            'مدرک تخصصی بازاریابی',
            'شهادت نامه دوره مذاکره و فروش'
        ];
    }

    /**
     * Get demo cars data with realistic specifications and categories
     * 
     * @return array
     */
    private function get_demo_cars_data() {
        return [
            // خودروهای ایرانی - موتور کوچک
            [ 'brand' => 'پژو', 'model' => 'پارس', 'engine' => 1600, 'body_type' => 'سدان', 'fuel_type' => 'بنزین', 'base_price' => 350000000, 'category' => 'خودروهای ایرانی' ],
            [ 'brand' => 'پژو', 'model' => '206', 'engine' => 1400, 'body_type' => 'هاچ‌بک', 'fuel_type' => 'بنزین', 'base_price' => 300000000, 'category' => 'خودروهای ایرانی' ],
            [ 'brand' => 'ایران‌خودرو', 'model' => 'تیبا', 'engine' => 1400, 'body_type' => 'سدان', 'fuel_type' => 'بنزین', 'base_price' => 280000000, 'category' => 'خودروهای ایرانی' ],
            [ 'brand' => 'سایپا', 'model' => 'پریدو', 'engine' => 1600, 'body_type' => 'سدان', 'fuel_type' => 'بنزین', 'base_price' => 260000000, 'category' => 'خودروهای ایرانی' ],
            [ 'brand' => 'رانا', 'model' => 'پلاس', 'engine' => 1400, 'body_type' => 'هاچ‌بک', 'fuel_type' => 'بنزین', 'base_price' => 280000000, 'category' => 'خودروهای ایرانی' ],
            
            // خودروهای ایرانی - SUV و بالاتر
            [ 'brand' => 'ایران‌خودرو', 'model' => 'دنا', 'engine' => 1600, 'body_type' => 'SUV', 'fuel_type' => 'بنزین', 'base_price' => 420000000, 'category' => 'خودروهای ایرانی SUV' ],
            [ 'brand' => 'کیا', 'model' => 'سراتو', 'engine' => 2000, 'body_type' => 'سدان', 'fuel_type' => 'بنزین', 'base_price' => 450000000, 'category' => 'خودروهای ایرانی' ],
            [ 'brand' => 'بیسل', 'model' => 'دیما', 'engine' => 1500, 'body_type' => 'سدان', 'fuel_type' => 'بنزین', 'base_price' => 380000000, 'category' => 'خودروهای ایرانی' ],
            [ 'brand' => 'جک', 'model' => 'S3', 'engine' => 1600, 'body_type' => 'SUV', 'fuel_type' => 'بنزین', 'base_price' => 380000000, 'category' => 'خودروهای ایرانی SUV' ],
            [ 'brand' => 'چری', 'model' => 'تیگو 5', 'engine' => 1500, 'body_type' => 'SUV', 'fuel_type' => 'بنزین', 'base_price' => 440000000, 'category' => 'خودروهای ایرانی SUV' ],
            
            // خودروهای خارجی - سدان معمولی
            [ 'brand' => 'هیوندای', 'model' => 'اکسنت', 'engine' => 1600, 'body_type' => 'سدان', 'fuel_type' => 'بنزین', 'base_price' => 550000000, 'category' => 'خودروهای خارجی' ],
            [ 'brand' => 'تویوتا', 'model' => 'کرولا', 'engine' => 1800, 'body_type' => 'سدان', 'fuel_type' => 'بنزین', 'base_price' => 800000000, 'category' => 'خودروهای خارجی' ],
            [ 'brand' => 'کیا', 'model' => 'سپتیا', 'engine' => 1600, 'body_type' => 'سدان', 'fuel_type' => 'بنزین', 'base_price' => 500000000, 'category' => 'خودروهای خارجی' ],
            
            // خودروهای خارجی - SUV
            [ 'brand' => 'هیوندای', 'model' => 'سانتافه', 'engine' => 2000, 'body_type' => 'SUV', 'fuel_type' => 'بنزین', 'base_price' => 1200000000, 'category' => 'خودروهای خارجی SUV' ],
            [ 'brand' => 'بیّج', 'model' => 'BJ40', 'engine' => 2400, 'body_type' => 'جیپ', 'fuel_type' => 'بنزین', 'base_price' => 520000000, 'category' => 'خودروهای خارجی SUV' ],
            
            // خودروهای لوکس - آلمانی
            [ 'brand' => 'بی‌ام‌و', 'model' => '320i', 'engine' => 2000, 'body_type' => 'سدان', 'fuel_type' => 'بنزین', 'base_price' => 2300000000, 'category' => 'خودروهای لوکس' ],
            [ 'brand' => 'آئودی', 'model' => 'A4', 'engine' => 2000, 'body_type' => 'سدان', 'fuel_type' => 'بنزین', 'base_price' => 2400000000, 'category' => 'خودروهای لوکس' ],
            [ 'brand' => 'فولکس‌واگن', 'model' => 'پاسات', 'engine' => 1800, 'body_type' => 'سدان', 'fuel_type' => 'بنزین', 'base_price' => 1800000000, 'category' => 'خودروهای لوکس' ],
            [ 'brand' => 'مرسدس', 'model' => 'C300', 'engine' => 2000, 'body_type' => 'سدان', 'fuel_type' => 'بنزین', 'base_price' => 2500000000, 'category' => 'خودروهای لوکس' ],
            [ 'brand' => 'لکسوس', 'model' => 'RX450h', 'engine' => 3500, 'body_type' => 'SUV', 'fuel_type' => 'هایبریدی', 'base_price' => 3500000000, 'category' => 'خودروهای لوکس' ],
            
            // خودروهای اسپرت
            [ 'brand' => 'فراری', 'model' => '458 Italia', 'engine' => 4527, 'body_type' => 'کوپه', 'fuel_type' => 'بنزین', 'base_price' => 8000000000, 'category' => 'خودروهای اسپرت' ],
            [ 'brand' => 'پورشه', 'model' => '911', 'engine' => 3800, 'body_type' => 'کوپه', 'fuel_type' => 'بنزین', 'base_price' => 5500000000, 'category' => 'خودروهای اسپرت' ],
        ];
    }

    /**
     * Get demo car features
     * 
     * @return array
     */
    private function get_demo_car_features() {
        $features = [
            'ABS', 'سیستم ایمنی', 'کیسه هوا', 'تاچومتر دیجیتال',
            'رادیو CD', 'بلوتوث', 'GPS', 'سنسور پارک',
            'دوربین عقب', 'کنترل سرعت', 'سقف شیشه‌ای'
        ];
        
        $selected = array_slice( $features, 0, wp_rand( 4, 7 ) );
        return $selected;
    }

    /**
     * Get demo inquiry notes/comments
     * 
     * @return array
     */
    private function get_demo_inquiry_notes() {
        return [
            'مشتری علاقه‌مند به دیدن خودروی دیگری است',
            'نیاز به بررسی مدارک بانکی مشتری',
            'قیمت پیشنهادی توسط مشتری قبول نشد',
            'مشتری موافق با شرایط اقساط است',
            'انجام بازدید از محل کار مشتری لازم است',
            'مستندات لازم در دست بررسی می‌باشند',
            'مشتری برای تصمیم‌گیری نیاز به مشاوره دارد',
            'قرارداد تنظیم شده و منتظر امضا است',
            'درخواست تضمین اضافی از مشتری دریافت شد',
            'مشتری درخواست تمدید زمان را کرده است'
        ];
    }

    /**
     * Get demo employment types
     * 
     * @return array
     */
    private function get_demo_employment_types() {
        return [
            'کارمند دولتی', 'کارمند خصوصی', 'خودروساز',
            'کارفرما', 'فریلنسر', 'معلم', 'پزشک',
            'وکیل', 'بازنشسته', 'دانشجو'
        ];
    }

    /**
     * Get demo first names
     * 
     * @return array
     */
    private function get_demo_first_names() {
        return [
            'محمد', 'علی', 'حسن', 'حسین', 'احمد', 'علیرضا', 'مهدی',
            'رضا', 'ایمان', 'بهزاد', 'عباس', 'سعید', 'امین', 'مصطفی',
            'فرانک', 'شاهرخ', 'نیما', 'سیاوش', 'دریا', 'آرش'
        ];
    }

    /**
     * Get demo last names
     * 
     * @return array
     */
    private function get_demo_last_names() {
        return [
            'محمدی', 'علوی', 'حسنی', 'احمدی', 'صفایی', 'کریمی', 'فرهادی',
            'رضایی', 'شریفی', 'کاظمی', 'ابراهیمی', 'عطایی', 'صالحی', 'نوری',
            'پورحسین', 'قلی‌پور', 'صفری', 'منصوری', 'حجازی', 'گلستانی'
        ];
    }

    /**
     * Get demo colors
     * 
     * @return array
     */
    private function get_demo_colors() {
        return [
            'سفید', 'سیاه', 'نقره‌ای', 'خاکستری', 'قرمز', 'آبی',
            'سبز', 'طلایی', 'برنزه‌ای', 'سرمه‌ای', 'بنفش', 'بژ'
        ];
    }

    /**
     * Create demo car categories if they don't exist
     */
    private function create_demo_car_categories() {
        $categories = [
            'خودروهای ایرانی',
            'خودروهای ایرانی SUV',
            'خودروهای خارجی',
            'خودروهای خارجی SUV',
            'خودروهای لوکس',
            'خودروهای اسپرت'
        ];

        foreach ( $categories as $cat_name ) {
            // Check if category already exists
            $existing = get_term_by( 'name', $cat_name, 'product_cat' );
            if ( ! $existing ) {
                wp_insert_term( $cat_name, 'product_cat', [
                    'description' => "دسته‌بندی $cat_name برای نمایش محصولات دمو"
                ] );
            }
        }
    }

    /**
     * Get category ID by name
     * 
     * @param string $cat_name
     * @return int Category ID or 0 if not found
     */
    private function get_category_id_by_name( $cat_name ) {
        $term = get_term_by( 'name', $cat_name, 'product_cat' );
        return $term ? $term->term_id : 0;
    }
}
