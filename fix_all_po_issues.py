#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
Fix all PO file issues - remove orphaned continuation lines
"""

def fix_all_issues():
    po_file = 'languages/maneli-car-inquiry-en_US.po'
    
    with open(po_file, 'r', encoding='utf-8') as f:
        lines = f.readlines()
    
    new_lines = []
    i = 0
    
    while i < len(lines):
        line = lines[i]
        stripped = line.strip()
        
        # Skip orphaned continuation lines
        # These are lines that start with " but are not part of msgid/msgstr
        if stripped.startswith('"') and not stripped.startswith('msg'):
            # Check if previous line is a complete msgid or msgstr
            if i > 0:
                prev_stripped = lines[i-1].strip()
                
                # If previous is a complete msgid/msgstr (ends with " and has msgid/msgstr)
                if ('msgid' in prev_stripped or 'msgstr' in prev_stripped) and '"' in prev_stripped:
                    # Check if next line is a new msgid/msgstr or comment
                    if i + 1 < len(lines):
                        next_stripped = lines[i+1].strip()
                        if next_stripped.startswith('msgid') or next_stripped.startswith('msgstr') or next_stripped.startswith('#'):
                            # This is an orphaned continuation, skip it
                            i += 1
                            continue
        
        # Skip lines that are just incomplete quotes
        if stripped == '"' or (stripped.startswith('"') and not stripped.endswith('"') and len(stripped) < 5):
            if i + 1 < len(lines):
                next_stripped = lines[i+1].strip()
                if next_stripped.startswith('msgid') or next_stripped.startswith('msgstr'):
                    i += 1
                    continue
        
        new_lines.append(line)
        i += 1
    
    # Write back
    with open(po_file, 'w', encoding='utf-8') as f:
        f.writelines(new_lines)
    
    print(f"Fixed PO file. Original: {len(lines)} lines, New: {len(new_lines)} lines")

if __name__ == '__main__':
    fix_all_issues()

