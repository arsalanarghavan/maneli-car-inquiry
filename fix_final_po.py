#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
Final fix for PO file - remove orphaned lines and fix remaining issues
"""

def fix_final():
    po_file = 'languages/maneli-car-inquiry-en_US.po'
    
    with open(po_file, 'r', encoding='utf-8') as f:
        content = f.read()
    
    lines = content.split('\n')
    new_lines = []
    i = 0
    
    while i < len(lines):
        line = lines[i]
        stripped = line.strip()
        
        # Skip orphaned continuation strings that are not part of msgid/msgstr
        if stripped.startswith('"') and not stripped.startswith('msg'):
            # Check if this is actually orphaned
            if i > 0 and i + 1 < len(lines):
                prev_line = lines[i-1].strip()
                next_line = lines[i+1].strip()
                
                # If previous line is complete msgid/msgstr and next is new msgid/msgstr, skip
                if (('msgid' in prev_line or 'msgstr' in prev_line) and 
                    prev_line.endswith('"') and 
                    (next_line.startswith('msgid') or next_line.startswith('msgstr') or next_line.startswith('#'))):
                    i += 1
                    continue
        
        # Skip lines that are just incomplete quotes
        if stripped == '"' or (stripped.startswith('"') and len(stripped) < 5 and not stripped.endswith('"')):
            if i + 1 < len(lines):
                next_line = lines[i+1].strip()
                if next_line.startswith('msgid') or next_line.startswith('msgstr'):
                    i += 1
                    continue
        
        # Skip long sequences of empty lines (keep only one)
        if stripped == '':
            if new_lines and new_lines[-1].strip() == '':
                # Already have an empty line, skip this one
                i += 1
                continue
        
        new_lines.append(line)
        i += 1
    
    # Fix remaining Persian translations
    content = '\n'.join(new_lines)
    
    # Fix specific Persian translations
    replacements = {
        'msgstr "خطا: فیلد «%s» برای خریدار الزامی است."': 'msgstr "Error: The field "%s" for the buyer is required."',
        'msgstr "کاربر جدید با نقش پیش‌فرض «مشتری» ایجاد می‌شود. نام کاربری و ایمیل به صورت خودکار بر اساس شماره موبایل ساخته خواهد شد."': 'msgstr "The new user will be created with the default "Customer" role. The username and email will be automatically generated based on the mobile number."',
        'msgstr "سیستم به صورت خودکار تمام کاربرانی که نقش <strong>\\"کارشناس مانلی\\"</strong> دارند را شناسایی می‌کند. درخواست‌ها به صورت گردشی به آن‌ها تخصیص داده میشود.<br>برای افزودن کارشناس جدید، کافیست از طریق منوی <strong>کاربران > افزودن</strong>، یک کاربر جدید با نقش «کارشناس مانلی» ایجاد کرده و شماره موبایل او را در پروفایلش وارد کنید."': 'msgstr "The system automatically identifies all users with the <strong>\\"Maneli Expert\\"</strong> role. Requests are assigned to them in a round-robin fashion.<br>To add a new expert, simply create a new user from the <strong>Users > Add New</strong> menu with the "Maneli Expert" role and enter their mobile number in their profile."',
        'msgstr "الگو: «درخواست جدید برای مدیر»"': 'msgstr "Pattern: "New Request for Admin""'
    }
    
    for old, new in replacements.items():
        content = content.replace(old, new)
    
    # Write back
    with open(po_file, 'w', encoding='utf-8') as f:
        f.write(content)
    
    print(f"Fixed PO file. Original: {len(lines)} lines, New: {len(content.split(chr(10)))} lines")

if __name__ == '__main__':
    fix_final()

