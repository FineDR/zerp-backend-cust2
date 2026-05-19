
import sys

with open('/home/zalongwa/taskplace/zerp-backend/PrintCustTrans.php', 'r') as f:
    lines = f.readlines()

# Find start and end indices (0-based)
start_idx = -1
end_idx = -1

for i, line in enumerate(lines):
    if "} elseif (isset($_GET['View']) && $_GET['View'] == 'Yes') {" in line and "PART 1" in lines[i+2]:
        start_idx = i
        break

if start_idx == -1:
    print("Could not find start index")
    sys.exit(1)

for i in range(start_idx, len(lines)):
    if "</html>';" in line and i > start_idx + 100: # safety
        # Search for the closing brace of the else block which should be a few lines down
        for j in range(i, i+10):
            if lines[j].strip() == "}":
                end_idx = j
                break
        if end_idx != -1:
            break
    line = lines[i]

if end_idx == -1:
    print("Could not find end index")
    sys.exit(1)

print(f"Replacing from line {start_idx+1} to {end_idx+1}")

replacement = """			} else {
				// ====================================================================
				// PART 1 & 2: REUSABLE INVOICE TEMPLATE (Dashboard & PDF)
				// ====================================================================
				
				// Prepare Data Arrays
				$invoice = array(
					'logo' => $_SESSION['LogoFile'],
					'company_name' => $_SESSION['CompanyRecord']['coyname'],
					'company_address' => $_SESSION['CompanyRecord']['regoffice1'] . ', ' . $_SESSION['CompanyRecord']['regoffice2'],
					'company_contact' => $_SESSION['CompanyRecord']['telephone'] . ' | ' . $_SESSION['CompanyRecord']['email'],
					'title' => ($InvOrCredit == "Invoice" ? __("TAX INVOICE") : __("TAX CREDIT NOTE")),
					'number' => $FromTransNo,
					'status' => (isset($IsPaid) && $IsPaid ? 'PAID' : 'PENDING'),
					'date' => ConvertSQLDate($MyRow['trandate']),
					'due_date' => $DisplayDueDate,
					'terms' => $MyRow['terms'],
					'notes' => $MyRow['invtext']
				);

				$customer = array(
					'name' => $MyRow['name'],
					'address' => $CustomerAddress,
					'ship_to' => $MyRow['deliverto'] . '<br/>' . $DeliveryAddress
				);

				$items = array();
				if (DB_num_rows($ResultLines) > 0) {
					DB_data_seek($ResultLines, 0);
					while ($line = DB_fetch_array($ResultLines)) {
						$items[] = array(
							'code' => $line['stockid'],
							'description' => $line['description'],
							'narrative' => $line['narrative'],
							'qty' => locale_number_format($line['quantity'], $line['decimalplaces']) . ' ' . $line['units'],
							'price' => locale_number_format($line['fxprice'], $MyRow['decimalplaces']),
							'total' => locale_number_format($line['fxnet'], $MyRow['decimalplaces'])
						);
					}
				}

				$totals = array(
					'subtotal' => $DisplaySubTot,
					'freight' => $DisplayFreight,
					'tax' => $DisplayTax,
					'paid' => ($AmountPaid > 0 ? locale_number_format($AmountPaid, $MyRow['decimalplaces']) : null),
					'total' => $MyRow['currcode'] . ' ' . locale_number_format($BalanceDue, $MyRow['decimalplaces'])
				);

				// Render Template
				ob_start();
				include 'InvoiceTemplate.php';
				$HTML = ob_get_clean();
			}
"""

new_lines = lines[:start_idx] + [replacement] + lines[end_idx+1:]

with open('/home/zalongwa/taskplace/zerp-backend/PrintCustTrans.php', 'w') as f:
    f.writelines(new_lines)

print("Success")
