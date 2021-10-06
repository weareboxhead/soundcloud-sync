<?php
/**
 * Soundcloud Sync plugin for Craft CMS 3.x
 *
 * Sync your Soundcloud track data into Craft Entries
 *
 * @link      https://boxhead.io
 * @copyright Copyright (c) 2021 Boxhead
 */

/**
 * Soundcloud Sync config.php
 *
 * This file exists only as a template for the Soundcloud Sync settings.
 * It does nothing on its own.
 *
 * Don't edit this file, instead copy it to 'craft/config' as 'soundcloud-sync.php'
 * and make your changes there to override default settings.
 *
 * Once copied to 'craft/config', this file will be multi-environment aware as
 * well, so you can have different settings groups for each environment, just as
 * you do for 'general.php'
 */

return [
    'soundcloudClientId' => getenv('SOUNDCLOUD_CLIENT_ID', ''),
    'soundcloudClientSecret' => getenv('SOUNDCLOUD_CLIENT_SECRET', ''),
    'soundcloudUserId' => getenv('SOUNDCLOUD_USER_ID', ''),
];