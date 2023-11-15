<?php

namespace boxhead\soundcloudsync\console\controllers;

use Craft;
use craft\helpers\Queue;
use yii\console\ExitCode;
use craft\helpers\Console;
use craft\console\Controller;
use boxhead\soundcloudsync\SoundcloudSync;
use boxhead\soundcloudsync\jobs\SoundcloudSyncCreateEntry;
use boxhead\soundcloudsync\jobs\SoundcloudSyncUpdateEntry;

/**
 * Soundcloud API Sync
 */
class SyncController extends Controller
{
    public $defaultAction = 'index';

    public function options($actionID): array
    {
        $options = parent::options($actionID);
        switch ($actionID) {
            case 'index':
                // $options[] = '...';
                break;
        }
        return $options;
    }

     /**
     * Runs the Soundcloud track data sync
     *
     * @throws Throwable
     */
    public function actionIndex(): int
    {
        $remoteData = SoundcloudSync::getInstance()->soundcloudEntries->getRemoteData();

        $localData = SoundcloudSync::getInstance()->soundcloudEntries->getLocalData();

        // Determine which entries we are missing by id
        $missingTracks = array_diff($remoteData['ids'], $localData['ids']);

        // Determine which entries need updating (all active tracks which we aren't about to create)
        $updatingTracks = array_diff($remoteData['ids'], $missingTracks);

        // Get the Craft queue
        $queue = Craft::$app->getQueue();

        // For each missing id
        foreach ($missingTracks as $id) {
            $queue->push(new SoundcloudSyncCreateEntry([
                'trackData' => $remoteData['tracks'][$id]
            ]));
        }

        // For each updating track
        foreach ($updatingTracks as $id) {
            $queue->push(new SoundcloudSyncUpdateEntry([
                'entryId' => $localData['tracks'][$id]->id,
                'trackData' => $remoteData['tracks'][$id]
            ]));
        }

        $this->stdout("Running Soundcloud sync" . PHP_EOL, Console::FG_GREEN);

        return ExitCode::OK;
    }
}
