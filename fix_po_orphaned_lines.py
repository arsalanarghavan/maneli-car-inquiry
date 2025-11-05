#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
Remove orphaned lines that break PO file format
"""

def fix_orphaned_lines():
    po_file = 'languages/maneli-car-inquiry-en_US.po'
    
    with open(po_file, 'r', encoding='utf-8') as f:
        lines = f.readlines()
    
    new_lines = []
    i = 0
    
    while i < len(lines):
        line = lines[i]
        stripped = line.strip()
        
        # Remove orphaned quote lines that are not part of msgid/msgstr
        # These are lines that are just a single quote or incomplete strings
        if stripped == '"' or (stripped.startswith('"') and not stripped.endswith('"') and len(stripped) < 10):
            # Check if this is actually part of a continuation
            # Look ahead to see if next line is msgid or msgstr
            if i + 1 < len(lines):
                next_stripped = lines[i + 1].strip()
                if next_stripped.startswith('msgid') or next_stripped.startswith('msgstr') or next_stripped.startswith('#'):
                    # This is an orphaned line, skip it
                    i += 1
                    continue
        
        # Remove lines that are incomplete continuation strings
        # These are lines that look like: "Some text\n" or "Some text"
        # but are not properly connected to msgid/msgstr
        if stripped.startswith('"') and not stripped.endswith('"') and i > 0:
            prev_stripped = lines[i - 1].strip() if i > 0 else ''
            next_stripped = lines[i + 1].strip() if i + 1 < len(lines) else ''
            
            # If previous line is complete msgid/msgstr and next is new msgid/msgstr, this is orphaned
            if ('msgid' in prev_stripped or 'msgstr' in prev_stripped) and (next_stripped.startswith('msgid') or next_stripped.startswith('msgstr') or next_stripped.startswith('#')):
                # Check if previous line is complete
                if '"' in prev_stripped and (prev_stripped.count('"') >= 2 or prev_stripped.endswith('"')):
                    # This is orphaned, skip
                    i += 1
                    continue
        
        new_lines.append(line)
        i += 1
    
    # Write back
    with open(po_file, 'w', encoding='utf-8') as f:
        f.writelines(new_lines)
    
    print(f"Removed orphaned lines. Original: {len(lines)} lines, New: {len(new_lines)} lines")

if __name__ == '__main__':
    fix_orphaned_lines()

