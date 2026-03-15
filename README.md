# FastComments for Drupal

Integrates the [FastComments](https://fastcomments.com) commenting widget with Drupal 10/11, replacing native Drupal comments with a fast, real-time commenting system.

## Installation

1. Place this module in your Drupal site's `modules/custom/fastcomments/` directory (or install via Composer: `composer require drupal/fcom`).
2. Enable the module:
   ```bash
   drush en fastcomments
   ```
   Or enable via the admin UI at **Extend** (`/admin/modules`).

## Configuration

Navigate to **Administration > Configuration > Content > FastComments** (`/admin/config/content/fastcomments`).

### Settings

- **Tenant ID** (required) - Your FastComments Tenant ID. Find this under [Settings > API/SSO](https://fastcomments.com/auth/my-account/api) ([EU](https://eu.fastcomments.com/auth/my-account/api)).
- **API Secret** - Required for Secure SSO, webhook verification, and page sync. Found under [Settings > API/SSO](https://fastcomments.com/auth/my-account/api) ([EU](https://eu.fastcomments.com/auth/my-account/api)).
- **SSO Mode** - Single Sign-On integration:
  - **None** - No SSO, users comment as guests or create FastComments accounts.
  - **Simple** - Passes Drupal user info (name, email, avatar) to FastComments without server-side verification.
  - **Secure** - Uses HMAC-SHA256 verification to securely authenticate Drupal users with FastComments (recommended).
- **Commenting Style** - The type of widget to display:
  - **Live Comments** - Real-time threaded comments.
  - **Streaming Chat** - Live chat interface.
  - **Collab Chat** - Collaborative text-selection annotation on the main content area.
  - **Collab Chat + Comments** - Both collab chat and standard comments.
- **CDN URL** - FastComments CDN URL (default: `https://cdn.fastcomments.com`).
- **Site URL** - FastComments site URL (default: `https://fastcomments.com`).
- **Email notifications** - Send an email to content authors when a new comment is posted on their content.

### Adding Comments to Content Types

Add the **FastComments** field to your content types via **Structure > Content types > [type] > Manage fields**. The field has a status toggle and an optional custom identifier per entity.

### EU Data Residency

For EU data residency, update:
- **CDN URL** to `https://cdn-eu.fastcomments.com`
- **Site URL** to `https://eu.fastcomments.com`

## Widget Blocks

Several blocks are available via **Structure > Block layout** (`/admin/structure/block`):

- **FastComments Widget** - The main commenting widget. Auto-detects the current entity. Skips entities that already have the FastComments field (to prevent duplicates).
- **FastComments Live Chat** - Real-time streaming chat. Can be placed alongside the comment field on the same page.
- **FastComments Collab Chat** - Text-selection annotation and discussion.
- **FastComments Image Chat** - Coordinate-based annotation on images.
- **FastComments Recent Comments** - Displays recent comments across your site. Configurable comment count.
- **FastComments Top Pages** - Shows pages with the most comments.

Content-centric blocks (Live Chat, Collab Chat, Image Chat) auto-detect the current entity and fall back to a path-based identifier on non-entity pages.

## Multilingual

The module automatically passes the current Drupal site language to all widgets.

## Permissions

- **Administer FastComments** - Access to the FastComments settings form.
- **View FastComments** - Required to see the commenting widget.
- **Toggle FastComments** - Allows users to enable/disable comments per entity via the field widget.

## How It Works

When a user visits an entity with the FastComments field enabled:

1. The FastComments JavaScript widget is loaded from the CDN.
2. If SSO is configured, the user's Drupal identity is passed to FastComments.
3. A `<noscript>` fallback provides server-rendered comments for users without JavaScript (Live Comments and Streaming Chat modes only).

## Requirements

- Drupal 10 or 11
- PHP 8.1+
- A [FastComments](https://fastcomments.com) account

## License

GPL-2.0-or-later
