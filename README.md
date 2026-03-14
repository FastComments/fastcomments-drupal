# FastComments for Drupal

Integrates the [FastComments](https://fastcomments.com) commenting widget with Drupal 10/11, replacing native Drupal comments with a fast, real-time commenting system.

## Installation

1. Place this module in your Drupal site's `modules/custom/fastcomments/` directory (or install via Composer).
2. Enable the module:
   ```bash
   drush en fastcomments
   ```
   Or enable via the admin UI at **Extend** (`/admin/modules`).

## Configuration

Navigate to **Administration > Configuration > Content authoring > FastComments** (`/admin/config/content/fastcomments`).

### Settings

- **Tenant ID** (required) - Your FastComments Tenant ID, found in your [FastComments dashboard](https://fastcomments.com) under My Account.
- **API Secret** - Required when SSO Mode is "Secure". Found in your FastComments dashboard under My Account.
- **SSO Mode** - Single Sign-On integration:
  - **None** - No SSO, users comment as guests or create FastComments accounts.
  - **Simple** - Passes Drupal user info (name, email, avatar) to FastComments without server-side verification.
  - **Secure** - Uses HMAC-SHA256 verification to securely authenticate Drupal users with FastComments (recommended).
- **Commenting Style** - The type of widget to display:
  - **Comments** - Standard threaded comments.
  - **Streaming Chat** - Live chat interface.
  - **Collab Chat** - Collaborative chat overlay on the main content area.
  - **Collab Chat + Comments** - Both collab chat and standard comments.
- **CDN URL** - FastComments CDN URL (default: `https://cdn.fastcomments.com`).
- **Site URL** - FastComments site URL (default: `https://fastcomments.com`).
- **Enabled Content Types** - Select which node types should display the FastComments widget. Native Drupal comment fields are automatically hidden on these content types.

### EU Data Residency

For EU data residency, update:
- **CDN URL** to `https://cdn-eu.fastcomments.com`
- **Site URL** to `https://eu.fastcomments.com`

## Block Placement

In addition to automatic injection on enabled content types, you can manually place the FastComments widget using the **FastComments Widget** block via **Structure > Block layout** (`/admin/structure/block`).

The block automatically detects the current node context. On non-node pages, it generates a URL ID from the current path. The block will not render on pages where automatic injection is already active (to prevent duplicates).

## Permissions

- **Administer FastComments** - Access to the FastComments settings form. Granted to administrators by default.

## How It Works

When a user visits a node of an enabled content type:

1. Native Drupal comment fields are hidden.
2. The FastComments JavaScript widget is loaded from the CDN.
3. If SSO is configured, the user's Drupal identity is passed to FastComments.
4. A `<noscript>` fallback provides server-rendered comments for users without JavaScript (comments and live chat modes only).

## Requirements

- Drupal 10 or 11
- PHP 8.1+
- The Node and User core modules (included with Drupal)

## License

GPL-2.0-or-later
