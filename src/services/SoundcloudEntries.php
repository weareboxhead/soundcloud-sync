<?php

/**
 * Soundcloud Sync plugin for Craft CMS 3.x
 *
 * Sync your Soundcloud track data into Craft Entries
 *
 * @link      https://boxhead.io
 * @copyright Copyright (c) 2021 Boxhead
 */

namespace boxhead\soundcloudsync\services;

use Craft;
use DateTime;
use craft\helpers\App;
use craft\base\Component;
use craft\elements\Entry;
use boxhead\soundcloudsync\SoundcloudSync;
use boxhead\soundcloudsync\wrapper\services\Soundcloud;
use boxhead\soundcloudsync\records\SoundcloudOauthRecord;
use boxhead\soundcloudsync\services\SoundcloudCategories;
use boxhead\soundcloudsync\wrapper\services\Soundcloud\exceptions\SoundcloudInvalidHttpResponseCodeException;

/**
 * SoundcloudEntries Service
 *
 * All of your plugin’s business logic should go in services, including saving data,
 * retrieving data, etc. They provide APIs that your controllers, template variables,
 * and other plugins can interact with.
 *
 * https://craftcms.com/docs/plugins/services
 *
 * @author    Boxhead
 * @package   SoundcloudSync
 * @since     1.0.0
 */
class SoundcloudEntries extends Component
{
    private $settings;
    private $clientId;
    private $clientSecret;
    private $redirectUrl;
    private $callLimit = 200;
    private $sectionId;
    private $entryTypeId;
    private $soundcloud;
    private $userId;
    private $categoryGroups;

    public function __construct()
    {
        $this->settings = SoundcloudSync::getInstance()->getSettings();

        if (!$this->settings->soundcloudClientId) {
            echo 'Soundcloud Client ID not set';
            die();
        }

        if (!$this->settings->soundcloudClientSecret) {
            echo 'Soundcloud Client Secret not set';
            die();
        }

        if (!$this->settings->soundcloudUserId) {
            echo 'Soundcloud User ID not set';
            die();
        }

        if (!$this->settings->sectionId) {
            echo 'Craft Section ID not set';
            die();
        }

        if (!$this->settings->entryTypeId) {
            echo 'Craft EntryType ID not set';
            die();
        }

        $this->clientId = $this->settings->soundcloudClientId;
        $this->clientSecret = $this->settings->soundcloudClientSecret;
        $this->sectionId = $this->settings->sectionId;
        $this->entryTypeId = $this->settings->entryTypeId;
        $this->userId = $this->settings->soundcloudUserId;
        $this->categoryGroups = $this->settings->categoryGroups ? explode(',', str_replace(' ', '', $this->settings->categoryGroups)) : [];

        // Create our calling object
        $this->soundcloud = new Soundcloud($this->clientId, $this->clientSecret);

        // Check for valid access token
        $existingToken = $this->checkAccessToken();

        if (!$existingToken) {
            $this->getAccessToken();
        }

        // Save access token to this soundcloud client
        $this->soundcloud->setAccessToken($existingToken);

        return;
    }

    private function saveToken($tokenData, $existingRecord = false)
    {
        if (!$existingRecord) {
            $oauth = new SoundcloudOauthRecord();
        } else {
            $oauth = $existingRecord;
        }

        $oauth->accessToken = $tokenData['access_token'];
        $oauth->refreshToken = $tokenData['refresh_token'];
        $oauth->expires = date('Y-m-d H:i:s', time() + $tokenData['expires_in']); //expires_in is number of seconds e.g. 3600 = 1 hour
        $oauth->siteId = Craft::$app->sites->currentSite->id;

        if ($existingRecord) {
            // Update existing record
            $oauth->update();
        } else {
            // Save new record
            $oauth->insert();
        }
    }

    public function checkAccessToken()
    {
        $record = SoundcloudOauthRecord::find()->one();

        // No OAuth record exists
        if (!$record) {
            return false;
        }

        // No access token set in record
        if (!$record->accessToken) {
            return false;
        }

        // Has access token has expired?
        if (time() >= strtotime($record->expires)) {
            // Try to refresh access token
            try {
                $tokenData = $this->soundcloud->accessTokenRefresh($record->refreshToken);

                // Update DB Oauth record
                $this->saveToken($tokenData, $record);
            } catch (SoundcloudInvalidHttpResponseCodeException $e) {
                // 401 unauthorised, probably because the refresh tokens are one time use even if they fail to get a successful response
                if ($e->getHttpCode() == 401) {
                    // Try and get a new access token from scratch
                    $this->getAccessToken($record);
                }
            }
        }

        return $record->accessToken;
    }

    public function getAccessToken($record = false)
    {
        // No access token exists request one
        $tokenData = $this->soundcloud->accessToken();

        // Save access Token to DB
        $this->saveToken($tokenData, $record);
    }

    private function getRemoteTracks()
    {
        // Get the user's tracks
        $tracks = [];
        $cursor = null;
        $options = [
            'limit' => 200,
            'linked_partitioning' => 1
        ];

        // While the soundcloud API is still returning tracks (call limit 200), keep appending to the array
        do {
            if ($cursor) {
                $options['cursor'] = $cursor;
            }

            $apiResponse = json_decode($this->soundcloud->get('users/' . $this->userId . '/tracks', $options), true);

            // Combine tracks array with returned data
            $tracks = array_merge($tracks, $apiResponse['collection']);

            if ($apiResponse['next_href']) {
                $nextUrlParts = parse_url($apiResponse['next_href']);
                parse_str($nextUrlParts['query'], $query);
                $cursor = $query['cursor'];
            }
        } while ($apiResponse['next_href']);

        return $tracks;
    }

    public function getRemoteData()
    {
        $data = [
            'ids' => [],
            'tracks' => [],
        ];

        $tracks = $this->getRemoteTracks();

        // For each track, add it to our data object with its id as the key
        foreach ($tracks as $track) {
            $id = $track['id'];

            // Add this id to our array
            $data['ids'][] = $id;

            // Add this track to our array
            $data['tracks'][$id] = $track;
        }

        return $data;
    }

    public function getLocalData()
    {
        $data = [
            'ids' => [],
            'tracks' => [],
        ];

        // Create a Craft Element Criteria Model
        $query = Entry::find()
            ->sectionId($this->sectionId)
            ->typeId($this->entryTypeId)
            ->status(null)
            ->limit(null)
            ->all();

        // For each track, add it to our data object with its id as the key
        foreach ($query as $track) {
            $id = $track->soundcloudFileId;

            // Add this id to our array
            $data['ids'][] = $id;

            // Add this track to our array
            $data['tracks'][$id] = $track;
        }

        return $data;
    }

    public function getPublishDate($data)
    {
        $release_year = $data['release_year'];
        $release_month = $data['release_month'];
        $release_day = $data['release_day'];

        // If there is a custom release date set, use this instead of the 'created_at' date
        if (!empty($release_year) && !empty($release_month) && !empty($release_day)) {
            $string = $release_year . '-' .  $release_month . '-' .  $release_day;
        } else {
            $string = $data['created_at'];
        }

        return new DateTime($string);
    }

    public function getTrackStreamUrl($id)
    {
        $streamData = json_decode($this->soundcloud->get('tracks/' . $id . '/streams'), true);
        return $streamData['http_mp3_128_url'] ?? '';
    }

    private function saveFieldData($entry, $data)
    {
        // Check for whether this track has its own artwork, and if not, use the avatar
        if (!empty($data['artwork_url'])) {
            $artworkUrl = $data['artwork_url'];
        } else {
            $artworkUrl = $data['user']['avatar_url'];
        }

        $fieldContent = [];

        // If soudcloudFileId field exists in the entry type, add it to the content
        if ($entry->getFieldLayout()->getFieldByHandle('soundcloudFileId')) {
            $fieldContent['soundcloudFileId'] = $data['id'];
        }

        if ($entry->getFieldLayout()->getFieldByHandle('soundcloudUserPermalink')) {
            $fieldContent['soundcloudUserPermalink'] = $data['user']['permalink'];
        }

        if ($entry->getFieldLayout()->getFieldByHandle('soundcloudDescription')) {
            $fieldContent['soundcloudDescription'] = $data['description'];
        }

        if ($entry->getFieldLayout()->getFieldByHandle('soundcloudDuration')) {
            $fieldContent['soundcloudDuration'] = $data['duration'];
        }

        if ($entry->getFieldLayout()->getFieldByHandle('soundcloudDurationHuman')) {
            $fieldContent['soundcloudDurationHuman'] = $this->formatTime($data['duration']);
        }

        if ($entry->getFieldLayout()->getFieldByHandle('soundcloudBpm')) {
            $fieldContent['soundcloudBpm'] = $data['bpm'];
        }

        if ($entry->getFieldLayout()->getFieldByHandle('soundcloudRelease')) {
            $fieldContent['soundcloudRelease'] = $data['release'];
        }

        if ($entry->getFieldLayout()->getFieldByHandle('soundcloudStreamable')) {
            $fieldContent['soundcloudStreamable'] = $data['streamable'];
        }

        if ($entry->getFieldLayout()->getFieldByHandle('soundcloudPermalinkUrl')) {
            $fieldContent['soundcloudPermalinkUrl'] = $data['permalink_url'];
        }

        if ($entry->getFieldLayout()->getFieldByHandle('soundcloudPurchaseUrl')) {
            $fieldContent['soundcloudPurchaseUrl'] = $data['purchase_url'];
        }

        if ($entry->getFieldLayout()->getFieldByHandle('soundcloudWaveformUrl')) {
            $fieldContent['soundcloudWaveformUrl'] = $data['waveform_url'];
        }

        if ($entry->getFieldLayout()->getFieldByHandle('soundcloudStreamUrl')) {
            $fieldContent['soundcloudStreamUrl'] = $data['stream_url'] ?? '';
        }

        if ($entry->getFieldLayout()->getFieldByHandle('soundcloudPlaybackCount')) {
            $fieldContent['soundcloudPlaybackCount'] = $data['playback_count'];
        }

        if ($entry->getFieldLayout()->getFieldByHandle('soundcloudDownloadCount')) {
            $fieldContent['soundcloudDownloadCount'] = $data['download_count'];
        }

        if ($entry->getFieldLayout()->getFieldByHandle('soundcloudFavoritingsCount')) {
            $fieldContent['soundcloudFavoritingsCount'] = $data['favoritings_count'];
        }

        if ($entry->getFieldLayout()->getFieldByHandle('soundcloudCommentCount')) {
            $fieldContent['soundcloudCommentCount'] = $data['comment_count'];
        }

        if ($entry->getFieldLayout()->getFieldByHandle('soundcloudDownloadUrl')) {
            $fieldContent['soundcloudDownloadUrl'] = ($data['download_url'] && $data['downloadable']) ? $data['download_url'] : '';
        }

        if ($entry->getFieldLayout()->getFieldByHandle('soundcloudArtwork500')) {
            $fieldContent['soundcloudArtwork500'] = str_replace('large.jpg', 't500x500.jpg', $artworkUrl);
        }

        if ($entry->getFieldLayout()->getFieldByHandle('soundcloudArtwork300')) {
            $fieldContent['soundcloudArtwork300'] = str_replace('large.jpg', 't300x300.jpg', $artworkUrl);
        }

        $categories = new SoundcloudCategories();
        $categories = $categories->getCategories($data, $this->categoryGroups);

        // Merge the parsed categories into the content
        $fieldContent = array_merge($fieldContent, $categories);

        $entry->setFieldValues($fieldContent);

        // Save the entry!
        if (!Craft::$app->elements->saveElement($entry)) {
            Craft::error('SoundcloudSync: Couldn\'t save the entry "' . $entry->title . '"', __METHOD__);

            return false;
        }
    }

    public function createEntry($data)
    {
        // Create a new instance of the Craft Entry Model
        $entry = new Entry();

        // Set the section id
        $entry->sectionId = $this->sectionId;

        // Set the entry type
        $entry->typeId = $this->entryTypeId;

        // Set the author as super admin
        $entry->authorId = 1;

        // Set disabled to begin with
        $entry->enabled = false;

        // Set the publish date as post date
        $entry->postDate = $this->getPublishDate($data);

        // Set the title
        $entry->title = $data['title'];

        // Set the other content
        $this->saveFieldData($entry, $data);

        return true;
    }

    public function updateEntry($entryId, $remoteEntry)
    {
        $entry = Entry::find()
            ->sectionId($this->sectionId)
            ->status(null)
            ->id($entryId)
            ->one();

        $fieldContent = [];

        if ($entry->getFieldLayout()->getFieldByHandle('soundcloudPlaybackCount')) {
            $fieldContent['soundcloudPlaybackCount'] = $remoteEntry['playback_count'];
        }

        if ($entry->getFieldLayout()->getFieldByHandle('soundcloudDownloadCount')) {
            $fieldContent['soundcloudDownloadCount'] = $remoteEntry['download_count'];
        }

        if ($entry->getFieldLayout()->getFieldByHandle('soundcloudFavoritingsCount')) {
            $fieldContent['soundcloudFavoritingsCount'] = $remoteEntry['favoritings_count'];
        }

        $entry->setFieldValues($fieldContent);

        if (!Craft::$app->elements->saveElement($entry)) {
            Craft::error('SoundcloudSync: Couldn’t update the entry "' . $entry->title . '"', __METHOD__);

            return false;
        }

        return true;
    }

    // Private Methods
    // =========================================================================

    private function dd($data)
    {
        echo '<pre>';
        print_r($data);
        echo '</pre>';
        die();
    }

    private function padTime($val)
    {
        // Add a '0' if the length is less than 2
        if (strlen($val) < 2) {
            $val = '0' . $val;
        }

        return $val;
    }

    private function formatTime($time)
    {
        // Convert to seconds
        $time = $time / 1000;

        // Get seconds
        $seconds = $this->padTime(strval(floor($time % 60)));

        // Reduce to minutes
        $time = $time / 60;

        // Get minues
        $mins = $this->padTime(strval(floor($time % 60)));

        // Reduce to hours
        $time = $time / 60;

        // Get hours
        $hours = $time % 60;

        // Assume just minutes and seconds
        $time = $mins . ':' . $seconds;

        // If there are hours
        if ($hours >= 1) {
            // Format correctly and prepend to time string
            $hours = $this->padTime(strval(floor($hours)));
            $time = $hours . ':' . $time;
        }

        return $time;
    }
}
