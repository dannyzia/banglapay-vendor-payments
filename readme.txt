=== BanglaPay Vendor Payments ===
Contributors: yourusername
Donate link: https://yourwebsite.com/donate
Tags: woocommerce, payment gateway, bangladesh, bkash, nagad, vendor, multivendor, dokan
Requires at least: 5.8
Tested up to: 6.4
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Vendor-specific manual payment gateways for WooCommerce. Each vendor receives payments directly to their own bKash, Nagad, Rocket, Upay, or Bank account.

== Description ==

BanglaPay Vendor Payments enables manual payment collection through popular Bangladeshi mobile financial services and bank transfers. Perfect for multi-vendor marketplaces where each vendor needs to receive payments to their own accounts.

**Key Features:**

* **Vendor-Specific Accounts** - Each vendor configures their own payment accounts
* **Multiple Payment Methods** - bKash, Nagad, Rocket, Upay, and Bank Transfer
* **Payment Verification System** - Vendors manually verify customer payments
* **Receipt Upload** - Customers can upload payment screenshots
* **Transaction Tracking** - Complete payment history and management
* **Multi-Vendor Support** - Works with Dokan, WCFM, and WC Vendors
* **Direct to Vendor** - Payments go directly to vendor accounts (no middleman)

**How It Works:**

1. Customer selects a payment method at checkout
2. System displays the specific vendor's payment account details
3. Customer makes payment using their mobile wallet or bank
4. Customer submits transaction ID and optional payment receipt
5. Order is placed with "On Hold" status
6. Vendor verifies payment in their account
7. Vendor approves or rejects the payment
8. Order proceeds to processing or is cancelled accordingly

**Supported Payment Methods:**

* **bKash** - Bangladesh's leading mobile financial service
* **Nagad** - Digital financial service by Bangladesh Post Office
* **Rocket** - Dutch-Bangla Bank's mobile banking service
* **Upay** - Mobile financial service
* **Bank Transfer** - Direct bank account transfers with full account details

**Multi-Vendor Plugin Compatibility:**

* Dokan (tested)
* WCFM Marketplace
* WC Vendors
* Works with any vendor plugin that uses WordPress post_author

**Use Cases:**

* Multi-vendor marketplaces in Bangladesh
* Stores wanting to avoid payment gateway fees
* Vendors who prefer direct payments to their accounts
* Businesses operating without merchant accounts

== Installation ==

**Minimum Requirements:**

* WordPress 5.8 or higher
* WooCommerce 6.0 or higher
* PHP 7.4 or higher
* MySQL 5.6 or higher

**Automatic Installation:**

1. Log in to your WordPress dashboard
2. Navigate to Plugins > Add New
3. Search for "BanglaPay Vendor Payments"
4. Click "Install Now" and then "Activate"

**Manual Installation:**

1. Download the plugin zip file
2. Log in to your WordPress dashboard
3. Navigate to Plugins > Add New > Upload Plugin
4. Choose the zip file and click "Install Now"
5. Click "Activate Plugin"

**Setup Instructions:**

1. After activation, each vendor should go to their dashboard
2. Navigate to "BanglaPay" or "Payment Settings" menu
3. Enable desired payment methods (bKash, Nagad, etc.)
4. Enter account numbers for each enabled method
5. For bank transfers, enter complete bank details
6. Save settings

For Dokan users: Settings appear in Dokan > Payment Settings
For standard WooCommerce: Settings in Dashboard > BanglaPay

**For Site Administrators:**

1. Go to WooCommerce > Settings > Payments
2. Enable the vendor payment gateways you want to offer
3. Configure titles and descriptions for each gateway
4. No global configuration needed - vendors set their own accounts

== Frequently Asked Questions ==

= Does this plugin process payments automatically? =

No. BanglaPay provides manual payment gateways. Customers pay directly to vendor accounts using their mobile wallets or bank, then submit proof. Vendors manually verify payments.

= Do I need merchant accounts or API keys? =

No. This plugin uses manual verification. Vendors simply need personal or business accounts with bKash, Nagad, Rocket, Upay, or their bank.

= How do vendors receive payments? =

Customers see the vendor's account number and pay directly to that account. The plugin does not handle money - it only facilitates information exchange and verification.

= Can customers upload payment receipts? =

Yes. Customers can optionally upload screenshots or PDFs of their payment receipts, which vendors can view when verifying payments.

= What happens if a vendor rejects a payment? =

The order status changes to "Failed" and the customer can attempt payment again with a new transaction ID.

= Does this work with multi-vendor plugins? =

Yes. Tested with Dokan. Also compatible with WCFM and WC Vendors. Works with any plugin where products have vendor authors.

= Can a single store use this without vendors? =

Yes. The store owner can configure their own payment accounts and use the plugin as a regular payment gateway for manual verification.

= Is this compliant with payment regulations? =

This plugin facilitates manual payment collection. Compliance depends on your local regulations and how you use it. Consult legal/financial advisors for your jurisdiction.

= Does this plugin store customer payment information? =

The plugin stores transaction IDs, sender numbers (if provided), and optional receipt files. No sensitive banking credentials are stored.

= What WooCommerce order statuses are used? =

* Pending - Initial order creation
* On-Hold - Payment submitted, awaiting vendor verification
* Processing - Payment verified by vendor
* Failed - Payment rejected by vendor

= Can I customize the payment instructions? =

Yes. Each payment gateway has title and description fields in WooCommerce > Settings > Payments that you can customize.

= Does this work with WooCommerce HPOS? =

Yes. The plugin declares compatibility with WooCommerce High-Performance Order Storage (HPOS).

== Screenshots ==

1. Customer payment page showing vendor's bKash account details
2. Vendor dashboard showing payment verification interface
3. Vendor payment settings configuration page
4. Order details with payment information and receipt
5. Transaction history in vendor dashboard

== Changelog ==

= 1.0.0 - 2025-01-15 =
* Initial release
* bKash payment gateway
* Nagad payment gateway
* Rocket payment gateway
* Upay payment gateway
* Bank Transfer payment gateway
* Vendor payment settings interface
* Payment verification system
* Receipt upload functionality
* Dokan integration
* Transaction tracking
* HPOS compatibility

== Upgrade Notice ==

= 1.0.0 =
Initial release of BanglaPay Vendor Payments.

== Additional Information ==

**Support:**

For support, please use the WordPress.org support forums. For bug reports and feature requests, visit our GitHub repository.

**Contributing:**

Contributions are welcome! Please visit our GitHub repository to submit pull requests or report issues.

**Translations:**

This plugin is translation-ready. If you'd like to contribute a translation, please visit translate.wordpress.org.

**Privacy:**

This plugin stores order payment information including transaction IDs and optional receipt files. This data is visible to vendors and site administrators. No data is sent to external services.

== Technical Details ==

**Database Tables:**

The plugin creates two custom tables:
* wp_banglapay_transactions - Payment transaction records
* wp_banglapay_vendor_settings - Vendor payment configuration

**AJAX Endpoints:**

* banglapay_submit_bkash_payment
* banglapay_submit_nagad_payment
* banglapay_submit_rocket_payment
* banglapay_submit_upay_payment
* banglapay_submit_bank_payment
* banglapay_upload_receipt
* banglapay_verify_payment

**Hooks and Filters:**

Developers can extend functionality using standard WordPress hooks. Documentation available on GitHub.