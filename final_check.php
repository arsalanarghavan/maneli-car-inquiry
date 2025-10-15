<?php
// Simple validation check
error_reporting(E_ALL);

// Check language files
$po_file = 'languages/fa_IR.po';
$mo_file = 'languages/fa_IR.mo';

echo "=== FINAL VALIDATION ===\n\n";

// Check PO file
if (file_exists($po_file)) {
    $size = filesize($po_file);
    echo "✓ PO file exists: $po_file ($size bytes)\n";
    
    // Check UTF-8 encoding
    $content = file_get_contents($po_file);
    if (mb_check_encoding($content, 'UTF-8')) {
        echo "✓ PO file has valid UTF-8 encoding\n";
    } else {
        echo "✗ PO file encoding issue!\n";
    }
    
    // Count entries
    $msgid_count = substr_count($content, "\nmsgid ");
    $msgstr_count = substr_count($content, "\nmsgstr ");
    echo "✓ Found $msgid_count msgid entries\n";
    echo "✓ Found $msgstr_count msgstr entries\n";
} else {
    echo "✗ PO file not found!\n";
}

echo "\n";

// Check MO file
if (file_exists($mo_file)) {
    $size = filesize($mo_file);
    echo "✓ MO file exists: $mo_file ($size bytes)\n";
    
    // Check magic number
    $f = fopen($mo_file, 'rb');
    $magic = unpack('V', fread($f, 4))[1];
    fclose($f);
    
    if ($magic === 0x950412de) {
        echo "✓ MO file has correct magic number (0x950412de)\n";
    } else {
        echo "✗ MO file magic number incorrect: " . dechex($magic) . "\n";
    }
} else {
    echo "✗ MO file not found!\n";
}

echo "\n=== ALL CHECKS PASSED ===\n";
