<?php
/**
 * Soundcloud Sync plugin for Craft CMS 3.x
 *
 * Sync your Soundcloud track data into Craft Entries
 *
 * @link      https://boxhead.io
 * @copyright Copyright (c) 2021 Boxhead
 */

namespace boxhead\soundcloudsync\jobs;

use Craft;
use craft\queue\BaseJob;
use boxhead\soundcloudsync\SoundcloudSync;

/**
 * SoundcloudSyncTask job
 */
class SoundcloudSyncCreateEntry extends BaseJob
{
    // Public Properties
    // =========================================================================

    /**
     * Soundcloud track data
     *
     * @var Array
     */
    public ?array $trackData;

    // Public Methods
    // =========================================================================

    /**
     * When the Queue is ready to run your job, it will call this method.
     * You don't need any steps or any other special logic handling, just do the
     * jobs that needs to be done here.
     *
     * More info: https://github.com/yiisoft/yii2-queue
     */
    public function execute($queue): void
    {
        // Process creation of a new entry
        SoundcloudSync::getInstance()->soundcloudEntries->createEntry($this->trackData);
    }

    // Protected Methods
    // =========================================================================

    /**
     * Returns a default description for [[getDescription()]], if [[description]] isn’t set.
     *
     * @return string The default task description
     */
    protected function defaultDescription(): ?string
    {
        return Craft::t('soundcloud-sync', 'Soundcloud sync create entry: ' . $this->trackData['title']);
    }
}
