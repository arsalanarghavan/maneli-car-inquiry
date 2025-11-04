#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
Script to fix Finotex/Finnotech naming in translation files
- Replace "فنوتک" with "فینوتک"
- Replace "فینوتکس" with "فینوتک"
"""

import re
import os

def fix_finnotex_names(file_path):
    """Fix Finotex/Finnotech naming in translation files"""
    with open(file_path, 'r', encoding='utf-8') as f:
        content = f.read()
    
    original_content = content
    
    # Replace "فنوتک" with "فینوتک"
    content = content.replace('فنوتک', 'فینوتک')
    
    # Replace "فینوتکس" with "فینوتک"
    content = content.replace('فینوتکس', 'فینوتک')
    
    if content != original_content:
        with open(file_path, 'w', encoding='utf-8') as f:
            f.write(content)
        print(f"Fixed: {file_path}")
        return True
    return False

if __name__ == '__main__':
    translation_files = [
        'languages/maneli-car-inquiry-fa_IR.po',
        'languages/maneli-car-inquiry-en_US.po',
        'languages/maneli-car-inquiry.pot',
    ]
    
    fixed_count = 0
    for file_path in translation_files:
        if os.path.exists(file_path):
            if fix_finnotex_names(file_path):
                fixed_count += 1
        else:
            print(f"File not found: {file_path}")
    
    print(f"\nFixed {fixed_count} file(s)")
    
    # Also compile .mo files
    if fixed_count > 0:
        print("\nCompiling .mo files...")
        os.system('msgfmt languages/maneli-car-inquiry-fa_IR.po -o languages/maneli-car-inquiry-fa_IR.mo')
        os.system('msgfmt languages/maneli-car-inquiry-en_US.po -o languages/maneli-car-inquiry-en_US.mo')
        print("Done!")

