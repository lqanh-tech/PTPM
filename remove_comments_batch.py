#!/usr/bin/env python3
import os
import re
import sys
from pathlib import Path

def remove_php_comments(content):
    """Remove all PHP comments from content including PHPDoc"""
    lines = content.split('\n')
    result = []
    in_multiline_comment = False
    
    for line in lines:
        new_line = line
        
        # Check for multiline comment start (including /** for PHPDoc)
        if ('/*' in line or '/**' in line) and not in_multiline_comment:
            in_multiline_comment = True
            # Find the position of /* or /**
            pos = line.find('/**') if '/**' in line else line.find('/*')
            new_line = line[:pos]
        
        # Check for multiline comment end
        if '*/' in line and in_multiline_comment:
            in_multiline_comment = False
            # Remove everything before and including */
            idx = line.index('*/')
            new_line = line[idx+2:]
            
        # If in multiline comment, skip line
        if in_multiline_comment:
            continue
            
        # Remove single line comments
        # But preserve URLs with //
        if '//' in new_line:
            # Check if it's not in a string
            in_string = False
            quote_char = None
            for i, char in enumerate(new_line):
                if char in ['"', "'"] and (i == 0 or new_line[i-1] != '\\'):
                    if not in_string:
                        in_string = True
                        quote_char = char
                    elif char == quote_char:
                        in_string = False
                        quote_char = None
                
                if not in_string and i < len(new_line) - 1:
                    if new_line[i:i+2] == '//':
                        new_line = new_line[:i].rstrip()
                        break
        
        # Only add non-empty lines or lines with code
        if new_line.strip() or (not new_line.strip() and result and result[-1].strip()):
            result.append(new_line)
    
    # Remove excessive blank lines
    final_result = []
    prev_blank = False
    for line in result:
        is_blank = not line.strip()
        if is_blank and prev_blank:
            continue
        final_result.append(line)
        prev_blank = is_blank
    
    return '\n'.join(final_result)

def process_file(filepath):
    """Process a single PHP file"""
    try:
        with open(filepath, 'r', encoding='utf-8') as f:
            content = f.read()
        
        # Count comments before
        comment_count = content.count('//') + content.count('/*') + content.count('*/')
        
        if comment_count == 0:
            return 0, 0
        
        # Remove comments
        new_content = remove_php_comments(content)
        
        # Write back
        with open(filepath, 'w', encoding='utf-8') as f:
            f.write(new_content)
        
        saved = len(content) - len(new_content)
        return 1, saved
        
    except Exception as e:
        print(f"Error processing {filepath}: {e}")
        return 0, 0

def main():
    directories = [
        '.',
    ]
    
    exclude_patterns = [
        'vendor',
        'node_modules',
        'logs',
        'uploads',
        '.git',
        '.backup',
    ]
    
    total_files = 0
    total_saved = 0
    processed_files = []
    
    for directory in directories:
        if not os.path.exists(directory):
            continue
            
        for root, dirs, files in os.walk(directory):
            # Skip excluded directories
            dirs[:] = [d for d in dirs if not any(ex in d for ex in exclude_patterns)]
            
            for file in files:
                if file.endswith('.php'):
                    filepath = os.path.join(root, file)
                    
                    # Skip if in excluded path
                    if any(ex in filepath for ex in exclude_patterns):
                        continue
                    
                    count, saved = process_file(filepath)
                    if count > 0:
                        total_files += count
                        total_saved += saved
                        processed_files.append((filepath, saved))
                        print(f"✓ {filepath} (-{saved} bytes)")
    
    print(f"\n{'='*60}")
    print(f"Summary:")
    print(f"Files processed: {total_files}")
    print(f"Total saved: {total_saved:,} bytes ({total_saved/1024:.2f} KB)")
    print(f"{'='*60}")

if __name__ == '__main__':
    main()
