# Soundcloud Sync plugin for Craft CMS 4.x

Sync your Soundcloud track data into Craft Entries

## Requirements

This plugin requires Craft CMS 4.0.0 or later.

## Installation

To install the plugin, follow these instructions.

1.  Open your terminal and go to your Craft project:

        cd /path/to/project

2.  Then tell Composer to load the plugin:

        composer require boxhead/soundcloud-sync

3.  In the Control Panel, go to Settings → Plugins and click the “Install” button for Soundcloud Sync.

## Soundcloud Sync Overview

Take the headache out of syncing your Soundcloud data into your site with Soundcloud Sync. For the time being syncing only happens one-way, Soundcloud » Craft.

This plugin authorizes with the Soundcloud API using the 'client_credentials' grant type and can therefore only access plublic content and that from the Soundcloud account owner of the Soundcloud app.

## Configuring Soundcloud Sync

1. Open up the Soundcloud Sync settings and specify your Soundcloud app details including Client ID, Client Secret and Soundcloud User ID.

2. Specify which Craft Section and Entry Type to have the plugin sync the data to by their respective IDs.

3. You may also optionally specify a comma separated list of Craft category groups by their handle. If these are present the plugin will search for tags associated with a Soundcloud tag that are prefixed with these handles allowing it to automatically create and attach those categories to the resulting Craft Entry. FOr example you may set 'speaker' as a category group handle. If a track contains a tag for 'speaker:Joe Bloggs', 'Joe Bloggs' will be created as a Craft category added to the Craft entry for that track.

## Fields

SoundCloud Sync works by saving API data to Craft fields. The fields it looks for by handle include:

-   `soundcloudArtwork500`
-   `soundcloudArtwork300`
-   `soundcloudBpm`
-   `soundcloudCommentCount`
-   `soundcloudDescription`
-   `soundcloudDownloadCount`
-   `soundcloudDownloadUrl`
-   `soundcloudDuration`
-   `soundcloudDurationHuman`
-   `soundcloudFavoritingsCount`
-   `soundcloudFileId`
-   `soundcloudPermalinkUrl`
-   `soundcloudPlaybackCount`
-   `soundcloudPurchaseUrl`
-   `soundcloudRelease`
-   `soundcloudStreamUrl`
-   `soundcloudStreamable`
-   `soundcloudUserPermalink`
-   `soundcloudWaveformUrl`

You can setup one, multiple or all of these fields in your Craft installation.

## Categories

As described above the plugin can automatically save genre and tag data to Craft categorfies if formatted in an expected way.

1. Setup your Craft category groups e.g. 'Theme', 'Genre', 'Artist', 'Speaker'

2. Setup category fields that can map to these groups using the following format for the field handles `soundcloudCategories{{ category group handle}}`, so in the case of an 'Artists' categroy group you'd have a field called `soundcloudCategoriesArtist`

## Using Soundcloud Sync

Soundcloud Sync can be set to run periodically using a Cron task pointing at `{{ your site url }}/actions/soundcloud-sync/sync`. This will search for an create new Craft entries for any Soundcloud tracks that don't yet exist, and will update any existing entries with an updated counts for playback, favourited and comments.

## Soundcloud Sync Roadmap

Brought to you by [Boxhead](https://boxhead.io)
