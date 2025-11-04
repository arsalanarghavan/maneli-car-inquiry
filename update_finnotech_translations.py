#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
Script to add all new Finnotech-related translation strings to .pot and .po files
"""

import re
import os

# New translation strings for Finnotech features
new_translations = {
    # Settings - Credentials
    "Finnotech API Credentials": "اعتبارنامه‌های API فینوتک",
    "These credentials are used for all Finnotech API services including Credit Risk, Credit Score, Collaterals, and Cheque Color inquiries.": "این اعتبارنامه‌ها برای تمام سرویس‌های API فینوتک از جمله استعلام ریسک اعتباری، امتیاز اعتباری، وثیقه‌ها و وضعیت چک استفاده می‌شوند.",
    "Finnotech Client ID": "شناسه کلاینت فینوتک",
    "Client ID provided by Finnotech. Stored securely encrypted.": "شناسه کلاینت ارائه شده توسط فینوتک. به صورت امن رمزنگاری شده ذخیره می‌شود.",
    "Finnotech API Key": "کلید API فینوتک",
    "API Key (Access Token) provided by Finnotech. Stored securely encrypted.": "کلید API (Access Token) ارائه شده توسط فینوتک. به صورت امن رمزنگاری شده ذخیره می‌شود.",
    "Enable Legacy Finotex API": "فعال‌سازی API قدیمی فینوتک",
    "Enable the legacy Finotex cheque color inquiry API (deprecated, use Finnotech API Services instead).": "فعال‌سازی API قدیمی استعلام وضعیت چک فینوتک (منسوخ شده، به جای آن از سرویس‌های API فینوتک استفاده کنید).",
    
    # Settings - Services
    "Finnotech API Services": "سرویس‌های API فینوتک",
    "Enable or disable individual Finnotech API services. When disabled, the data will be hidden from reports but preserved in the database.": "فعال یا غیرفعال کردن سرویس‌های API فینوتک. در صورت غیرفعال بودن، داده‌ها از گزارش‌ها مخفی می‌شوند اما در دیتابیس حفظ می‌شوند.",
    "Enable Credit Risk Inquiry": "فعال‌سازی استعلام ریسک اعتباری",
    "Enable banking risk inquiry for individuals (ریسک بانکی شخص).": "فعال‌سازی استعلام ریسک بانکی برای اشخاص (ریسک بانکی شخص).",
    "Enable Credit Score Report": "فعال‌سازی گزارش امتیاز اعتباری",
    "Enable credit score decrease reasons inquiry (دلایل کاهش امتیاز اعتباری).": "فعال‌سازی استعلام دلایل کاهش امتیاز اعتباری (دلایل کاهش امتیاز اعتباری).",
    "Enable Collaterals Inquiry": "فعال‌سازی استعلام وثیقه‌ها",
    "Enable contracts summary inquiry (وام‌ها/تسهیلات).": "فعال‌سازی استعلام خلاصه قراردادها (وام‌ها/تسهیلات).",
    "Enable Cheque Color Inquiry": "فعال‌سازی استعلام وضعیت چک",
    "Enable Sadad cheque status inquiry (وضعیت چک‌های صیادی).": "فعال‌سازی استعلام وضعیت چک‌های صیادی (وضعیت چک‌های صیادی).",
    
    # Credit Risk Report
    "Banking Risk Assessment": "ارزیابی ریسک بانکی",
    "Credit Risk Level:": "سطح ریسک اعتباری:",
    "Risk Score:": "امتیاز ریسک:",
    "Prohibited Transaction Status:": "وضعیت ممنوعیت تراکنش:",
    "Yes": "بله",
    "No": "خیر",
    "Financial Judgments:": "رای‌های مالی:",
    "Rials": "ریال",
    
    # Credit Score Report
    "Credit Score Decrease Reasons": "دلایل کاهش امتیاز اعتباری",
    "Current Credit Score:": "امتیاز اعتباری فعلی:",
    "Negative Factors:": "عوامل منفی:",
    "Score History:": "تاریخچه امتیاز:",
    "Date": "تاریخ",
    "Previous Score": "امتیاز قبلی",
    "New Score": "امتیاز جدید",
    "Reason": "علت",
    
    # Collaterals Report
    "Contracts Summary (Loans/Facilities)": "خلاصه قراردادها (وام‌ها/تسهیلات)",
    "Total Contracts:": "مجموع قراردادها:",
    "Total Loans:": "مجموع وام‌ها:",
    "Total Facilities:": "مجموع تسهیلات:",
    "Bank": "بانک",
    "Contract Number": "شماره قرارداد",
    "Type": "نوع",
    "Amount": "مبلغ",
    "Status": "وضعیت",
    
    # Cheque Color Report
    "Sadad Cheque Status Inquiry": "استعلام وضعیت چک‌های صیادی",
    "Cheque Status Details": "جزئیات وضعیت چک",
    "Bounced Cheques:": "چک‌های برگشتی:",
    "Cleared Cheques:": "چک‌های رفع سوءاثر شده:",
    
    # List Indicators
    "Credit Information Available": "اطلاعات اعتباری موجود",
    "Credit Risk Available": "ریسک اعتباری موجود",
    "Credit Score Available": "امتیاز اعتباری موجود",
    "Contracts Available": "قراردادها موجود",
    "Cheque Status Available": "وضعیت چک موجود",
}

def extract_msgids_from_pot(pot_file):
    """Extract all msgid values from a .pot file"""
    msgids = set()
    with open(pot_file, 'r', encoding='utf-8') as f:
        content = f.read()
    
    # Find all msgid entries
    pattern = r'msgid\s+"([^"]+)"'
    matches = re.findall(pattern, content)
    for match in matches:
        if match:  # Skip empty strings
            msgids.add(match)
    
    return msgids

def add_to_pot(pot_file, translations):
    """Add new translation strings to .pot file"""
    existing_msgids = extract_msgids_from_pot(pot_file)
    
    with open(pot_file, 'r', encoding='utf-8') as f:
        content = f.read()
    
    # Find the end of the file (before the last empty line)
    lines = content.split('\n')
    
    # Add new entries
    new_entries = []
    for msgid, _ in translations.items():
        if msgid not in existing_msgids:
            new_entries.append('')
            new_entries.append(f'msgid "{msgid}"')
            new_entries.append('msgstr ""')
    
    if new_entries:
        # Append to file
        with open(pot_file, 'a', encoding='utf-8') as f:
            f.write('\n'.join(new_entries))
        print(f"Added {len(new_entries) // 3} new entries to {pot_file}")
    else:
        print(f"No new entries to add to {pot_file}")

def update_po_file(po_file, translations):
    """Update .po file with Persian translations"""
    with open(po_file, 'r', encoding='utf-8') as f:
        lines = f.readlines()
    
    result_lines = []
    i = 0
    updated_count = 0
    
    while i < len(lines):
        line = lines[i]
        result_lines.append(line)
        
        # Check if this is a msgid line
        if line.strip().startswith('msgid "'):
            msgid_match = re.match(r'msgid "(.+)"', line.strip())
            if msgid_match:
                msgid_content = msgid_match.group(1)
                
                # Look ahead for msgstr
                j = i + 1
                while j < len(lines) and not lines[j].strip().startswith('msgstr'):
                    if lines[j].strip():
                        result_lines.append(lines[j])
                    j += 1
                
                # Check if we have a translation for this msgid
                if j < len(lines) and msgid_content in translations:
                    msgstr_line = lines[j]
                    if msgstr_line.strip() == 'msgstr ""' or 'msgstr ""' in msgstr_line:
                        # Replace with translation
                        result_lines.append(f'msgstr "{translations[msgid_content]}"\n')
                        updated_count += 1
                        i = j + 1
                        continue
                    else:
                        # Keep existing translation
                        result_lines.append(lines[j])
                        i = j + 1
                        continue
        
        i += 1
    
    # Write back
    with open(po_file, 'w', encoding='utf-8') as f:
        f.writelines(result_lines)
    
    print(f"Updated {updated_count} translations in {po_file}")
    
    # Also add any missing entries
    existing_msgids = set()
    for line in lines:
        msgid_match = re.match(r'msgid "(.+)"', line.strip())
        if msgid_match and msgid_match.group(1):
            existing_msgids.add(msgid_match.group(1))
    
    missing_entries = []
    for msgid, translation in translations.items():
        if msgid not in existing_msgids:
            missing_entries.append('')
            missing_entries.append(f'msgid "{msgid}"')
            missing_entries.append(f'msgstr "{translation}"')
    
    if missing_entries:
        with open(po_file, 'a', encoding='utf-8') as f:
            f.write('\n')
            f.write('\n'.join(missing_entries))
        print(f"Added {len(missing_entries) // 3} missing entries to {po_file}")

def compile_mo(po_file):
    """Compile .po file to .mo using msgfmt"""
    mo_file = po_file.replace('.po', '.mo')
    os.system(f'msgfmt "{po_file}" -o "{mo_file}"')
    print(f"Compiled {po_file} to {mo_file}")

if __name__ == '__main__':
    pot_file = 'languages/maneli-car-inquiry.pot'
    fa_po_file = 'languages/maneli-car-inquiry-fa_IR.po'
    en_po_file = 'languages/maneli-car-inquiry-en_US.po'
    
    print("Adding new translations to .pot file...")
    add_to_pot(pot_file, new_translations)
    
    print("\nUpdating Persian translations...")
    update_po_file(fa_po_file, new_translations)
    
    print("\nUpdating English translations...")
    # For English, just use the English strings as translations
    en_translations = {k: k for k in new_translations.keys()}
    update_po_file(en_po_file, en_translations)
    
    print("\nCompiling .mo files...")
    compile_mo(fa_po_file)
    compile_mo(en_po_file)
    
    print("\nDone! All translations updated and compiled.")

