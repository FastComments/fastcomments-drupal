<?php

/**
 * @file
 * Hooks provided by the FastComments module.
 */

/**
 * Alter user data before it is sent to FastComments via SSO.
 *
 * @param array &$user_data
 *   The user data array.
 * @param \Drupal\Core\Session\AccountProxyInterface $account
 *   The current user account.
 */
function hook_fastcomments_user_data_alter(array &$user_data, \Drupal\Core\Session\AccountProxyInterface $account) {
}
