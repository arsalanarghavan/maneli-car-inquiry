<?php
/**
 * Test Demo Data Generation
 * 
 * Usage: wp eval-file test-demo-data.php
 * Or: php test-demo-data.php (from plugin root, assuming WordPress is loaded)
 */

// Check if WordPress is loaded
if ( ! function_exists( 'get_option' ) ) {
    // Try to load WordPress
    require_once( '../../../../wp-load.php' );
}

// Check required classes exist
echo "Checking if demo data classes exist...\n";
echo "- class_exists('Autopuzzle_Database'): " . ( class_exists( 'Autopuzzle_Database' ) ? 'YES' : 'NO' ) . "\n";
echo "- class_exists('Autopuzzle_CPT_Handler'): " . ( class_exists( 'Autopuzzle_CPT_Handler' ) ? 'YES' : 'NO' ) . "\n";
echo "- class_exists('Autopuzzle_Demo_Data_Generator'): " . ( class_exists( 'Autopuzzle_Demo_Data_Generator' ) ? 'YES' : 'NO' ) . "\n";

// Load the classes if they don't exist
if ( ! class_exists( 'Autopuzzle_Demo_Data_Generator' ) ) {
    require_once( 'includes/class-demo-data-generator.php' );
}

echo "\nAfter loading:\n";
echo "- class_exists('Autopuzzle_Demo_Data_Generator'): " . ( class_exists( 'Autopuzzle_Demo_Data_Generator' ) ? 'YES' : 'NO' ) . "\n";

if ( class_exists( 'Autopuzzle_Demo_Data_Generator' ) ) {
    try {
        echo "\nCreating generator instance...\n";
        $generator = new Autopuzzle_Demo_Data_Generator();
        echo "Generator created successfully!\n";
        
        echo "\nTesting generate_demo_data()...\n";
        $results = $generator->generate_demo_data();
        
        echo "\nResults:\n";
        echo json_encode( $results, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE ) . "\n";
        
    } catch ( Exception $e ) {
        echo "ERROR: " . $e->getMessage() . "\n";
        echo "Stack trace:\n";
        echo $e->getTraceAsString() . "\n";
    }
} else {
    echo "ERROR: Autopuzzle_Demo_Data_Generator class not found!\n";
}
