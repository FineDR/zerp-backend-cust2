import sys

target_file = '/home/zalongwa/taskplace/zerp-backend/PDFBankingSummary.php'
replacement_file = '/home/zalongwa/taskplace/zerp-backend/tmp_banking_summary.php'

with open(target_file, 'r') as f:
    lines = f.readlines()

with open(replacement_file, 'r') as f:
    replacement_content = f.read()

# Lines 78 to 167 are 0-indexed as 77 to 167 (inclusive in 1-based is 77 to 166 in 0-based)
# But my view_file showed 78 is "$HTML = '';" and 167 is "</html>';"
new_lines = lines[:77] + [replacement_content] + lines[167:]

with open(target_file, 'w') as f:
    f.writelines(new_lines)

print("Successfully replaced lines 78-167")
