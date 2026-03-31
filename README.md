# M-PESA Tanzania for WooCommerce

M-PESA Tanzania for WooCommerce is a WordPress payment gateway plugin for stores that want to accept Vodacom Tanzania M-Pesa payments through WooCommerce.

This build is tailored for Tanzanian Shilling (`TZS`) stores and uses the M-Pesa OpenAPI flow for customer-to-business checkout requests.

## Features

- Accept M-Pesa Tanzania payments during WooCommerce checkout
- Collect the customer's Tanzania phone number at checkout
- Support sandbox and live OpenAPI credentials
- Built-in WooCommerce Blocks compatibility
- Callback URL support for provider-side integrations
- Diagnostics page for credentials, cron, currency, and environment checks
- Debug logging through WooCommerce logs

## Requirements

- WordPress with WooCommerce installed and active
- WooCommerce 7.9 or newer
- Store currency set to `TZS`
- OpenSSL enabled on the server
- Valid Vodacom Tanzania M-Pesa OpenAPI credentials

## Installation

1. Download this repository as a ZIP, or clone it locally.
2. In WordPress admin, go to `Plugins > Add New Plugin`.
3. Click `Upload Plugin`.
4. Choose the plugin ZIP file and click `Install Now`.
5. Activate the plugin after installation completes.

## Configuration

1. Go to `WooCommerce > Settings > Payments`.
2. Find `M-Pesa Tanzania` and enable it.
3. Click `Manage`.
4. Set the checkout title and description shown to customers.
5. Choose whether to use sandbox credentials or live credentials.
6. Fill in the required credentials for the selected mode:
   - API Key
   - API Host
   - Public Key
   - Service Provider Code
7. Optionally set the `Origin` value if your OpenAPI app requires it.
8. Save changes.

The plugin generates a unique third-party conversation ID automatically, so the older manual conversation ID fields are not required.

## Checkout Flow

1. The customer selects `M-Pesa Tanzania` at checkout.
2. The customer enters a Tanzania mobile number.
3. WooCommerce submits the order and triggers the M-Pesa request.
4. The customer confirms the payment on their phone.
5. The order updates when the transaction is confirmed and reconciled.

## Diagnostics

The plugin adds a diagnostics page in `WooCommerce > M-Pesa Diagnostics`.

Use it to verify:

- Store currency is `TZS`
- OpenSSL is available
- Required credentials are present
- Callback URL is reachable and correctly generated
- Reconciliation cron is scheduled
- WooCommerce Blocks support is detected

You can also trigger a manual reconciliation run from that page.

## Callback URL

The gateway shows its callback URL on the payment settings page. Use that URL anywhere your M-Pesa provider-side setup expects a callback or notification endpoint.

## Notes

- This build is intended for Tanzania stores and hides itself when the WooCommerce currency is not `TZS`.
- Sandbox and live credentials are stored separately.
- Debug logging can be enabled from the gateway settings when troubleshooting.

## License

This project is released under the MIT License. See the [LICENSE](LICENSE) file for details.
