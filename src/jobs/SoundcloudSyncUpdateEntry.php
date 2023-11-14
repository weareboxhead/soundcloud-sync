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

use boxhead\soundcloudsync\SoundcloudSync;

use Craft;
use craft\queue\BaseJob;

/**
 * SoundcloudSyncTask job
 *
 * Jobs are run in separate process via a Queue of pending jobs. This allows
 * you to spin lengthy processing off into a separate PHP process that does not
 * block the main process.
 *
 * You can use it like this:
 *
 * use boxhead\soundcloudsync\jobs\SoundcloudSyncUpdateEntry as SoundcloudSyncUpdateEntryJob;
 *
 * $queue = Craft::$app->getQueue();
 * $jobId = $queue->push(new SoundcloudSyncUpdateEntryJob([
 *     'description' => Craft::t('soundcloud-sync', 'This overrides the default description'),
 *     'someAttribute' => 'someValue',
 * ]));
 *
 * The key/value pairs that you pass in to the job will set the public properties
 * for that object. Thus whatever you set 'someAttribute' to will cause the
 * public property $someAttribute to be set in the job.
 *
 * Passing in 'description' is optional, and only if you want to override the default
 * description.
 *
 * More info: https://github.com/yiisoft/yii2-queue
 *
 * @author    Boxhead
 * @package   SoundcloudSync
 * @since     1.0.0
 */
class SoundcloudSyncUpdateEntry extends BaseJob
{
    // Public Properties
    // =========================================================================

    /**
     * Craft Entry
     *
     * @var Entry
     */
    public $entryId;

    /**
     * Soundcloud track data
     *
     * @var Array
     */
    public array $trackData;

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
        SoundcloudSync::getInstance()->soundcloudEntries->updateEntry($this->entryId, $this->trackData);
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
        return Craft::t('soundcloud-sync', 'Soundcloud sync update entry: ' . $this->trackData['title']);
    }
}
