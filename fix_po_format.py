#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
Fix PO file format issues - ensure msgid and msgstr both end with \n or both don't
"""

import re

def fix_po_format():
    po_file = 'languages/maneli-car-inquiry-en_US.po'
    
    with open(po_file, 'r', encoding='utf-8') as f:
        lines = f.readlines()
    
    new_lines = []
    i = 0
    
    while i < len(lines):
        line = lines[i]
        
        # Check if this is a msgid line
        if line.strip().startswith('msgid "'):
            msgid_line = line
            msgstr_line = None
            msgid_has_newline = False
            msgstr_has_newline = False
            
            # Collect msgid
            j = i
            msgid_content = []
            while j < len(lines) and (lines[j].strip().startswith('msgid') or lines[j].strip().startswith('"')):
                if lines[j].strip().startswith('msgid'):
                    msgid_content.append(lines[j])
                elif lines[j].strip().startswith('"'):
                    msgid_content.append(lines[j])
                j += 1
                if j < len(lines) and not (lines[j].strip().startswith('msgid') or lines[j].strip().startswith('"') or lines[j].strip() == ''):
                    break
            
            # Check if msgid ends with \n
            if msgid_content:
                last_msgid = msgid_content[-1].rstrip('\n')
                if last_msgid.endswith('\\n"'):
                    msgid_has_newline = True
            
            # Find msgstr
            k = j
            while k < len(lines) and not lines[k].strip().startswith('msgstr'):
                if lines[k].strip().startswith('msgid'):
                    break
                k += 1
            
            if k < len(lines) and lines[k].strip().startswith('msgstr'):
                msgstr_content = []
                l = k
                while l < len(lines) and (lines[l].strip().startswith('msgstr') or lines[l].strip().startswith('"') or lines[l].strip() == ''):
                    if lines[l].strip().startswith('msgstr') or lines[l].strip().startswith('"'):
                        msgstr_content.append(lines[l])
                    l += 1
                    if l < len(lines) and lines[l].strip().startswith('msgid'):
                        break
                    if l < len(lines) and lines[l].strip().startswith('#') and not lines[l].strip().startswith('"'):
                        break
                
                # Check if msgstr ends with \n
                if msgstr_content:
                    last_msgstr = msgstr_content[-1].rstrip('\n')
                    if last_msgstr.endswith('\\n"'):
                        msgstr_has_newline = True
                    
                    # Fix mismatch
                    if msgid_has_newline != msgstr_has_newline:
                        # Make them match - usually we want msgstr to match msgid
                        if msgid_has_newline and not msgstr_has_newline:
                            # Add \n to msgstr
                            if msgstr_content:
                                last = msgstr_content[-1].rstrip('\n')
                                if last.endswith('"') and not last.endswith('\\n"'):
                                    msgstr_content[-1] = last[:-1] + '\\n"\n'
                        elif not msgid_has_newline and msgstr_has_newline:
                            # Remove \n from msgstr
                            if msgstr_content:
                                last = msgstr_content[-1].rstrip('\n')
                                if last.endswith('\\n"'):
                                    msgstr_content[-1] = last[:-4] + '"\n'
                
                # Write msgid and fixed msgstr
                new_lines.extend(msgid_content)
                new_lines.extend(msgstr_content)
                i = l
                continue
        
        new_lines.append(line)
        i += 1
    
    # Write back
    with open(po_file, 'w', encoding='utf-8') as f:
        f.writelines(new_lines)
    
    print("Fixed PO file format")

if __name__ == '__main__':
    fix_po_format()

