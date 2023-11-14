<?php
/**
 * Soundcloud Sync plugin for Craft CMS 3.x
 *
 * Sync your Soundcloud track data into Craft Entries
 *
 * @link      https://boxhead.io
 * @copyright Copyright (c) 2021 Boxhead
 */

namespace boxhead\soundcloudsync\controllers;

use Craft;
use craft\web\View;
use craft\web\Response;
use craft\web\Controller;
use boxhead\soundcloudsync\SoundcloudSync;
use boxhead\soundcloudsync\jobs\SoundcloudSyncCreateEntry;
use boxhead\soundcloudsync\jobs\SoundcloudSyncUpdateEntry;

/**
 * SyncController Controller
 *
 * Generally speaking, controllers are the middlemen between the front end of
 * the CP/website and your plugin’s services. They contain action methods which
 * handle individual tasks.
 *
 * A common pattern used throughout Craft involves a controller action gathering
 * post data, saving it on a model, passing the model off to a service, and then
 * responding to the request appropriately depending on the service method’s response.
 *
 * Action methods begin with the prefix “action”, followed by a description of what
 * the method does (for example, actionSaveIngredient()).
 *
 * https://craftcms.com/docs/plugins/controllers
 *
 * @author    Boxhead
 * @package   SoundcloudSync
 * @since     1.0.0
 */
class SyncController extends Controller
{
    // Protected Properties
    // =========================================================================

    /**
     * @var    bool|array Allows anonymous access to this controller's actions.
     *         The actions must be in 'kebab-case'
     * @access protected
     */
    protected array|int|bool $allowAnonymous = ['index', 'get-stream-url'];

    // Public Methods
    // =========================================================================

    /**
     * Handle a request going to our plugin's actionSync URL,
     * e.g.: actions/soundcloud-sync/sync
     *
     * @return mixed
     */
    public function actionIndex(): Response
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

        $message = 'Sync in progress.';

        return $this->getResponse($message);
    }

    public function actionGetStreamUrl() {
        $trackId = Craft::$app->request->getQueryParam('trackId');

        $url = SoundcloudSync::getInstance()->soundcloudEntries->getTrackStreamUrl($trackId);

        return $this->asJson([
            'url' => $url
        ]);
    }

    /**
     * Returns a response.
     */
    private function getResponse(string $message, bool $success = true): Response
    {
        $request = Craft::$app->getRequest();

        // If front-end or JSON request
        // Run the queue to ensure action is completed in full
        if (Craft::$app->getView()->templateMode == View::TEMPLATE_MODE_SITE || $request->getAcceptsJson()) {
            Craft::$app->runAction('queue/run');

            return $this->asJson([
                'success' => $success,
                'message' => Craft::t('soundcloud-sync', $message),
            ]);
        }

        if ($success) {
            Craft::$app->getSession()->setNotice(Craft::t('soundcloud-sync', $message));
        } else {
            Craft::$app->getSession()->setError(Craft::t('soundcloud-sync', $message));
        }

        return $this->redirectToPostedUrl(null, $request->referrer);
    }
}
