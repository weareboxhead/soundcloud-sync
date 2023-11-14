<?php
/**
 * Soundcloud Sync plugin for Craft CMS 3.x
 *
 * Sync your Soundcloud track data into Craft Entries
 *
 * @link      https://boxhead.io
 * @copyright Copyright (c) 2021 Boxhead
 */

namespace boxhead\soundcloudsync;

use Craft;
use yii\base\Event;
use craft\base\Model;
use craft\base\Plugin;
use craft\web\UrlManager;
use craft\services\Plugins;
use craft\events\PluginEvent;
use craft\services\Utilities;
use craft\events\RegisterUrlRulesEvent;
use boxhead\soundcloudsync\models\Settings;
use craft\web\twig\variables\CraftVariable;
use craft\events\RegisterComponentTypesEvent;
use boxhead\soundcloudsync\utilities\SyncUtility;
use boxhead\soundcloudsync\variables\SoundcloudSyncVariable;
use boxhead\soundcloudsync\services\SoundcloudEntries as SoundcloudEntriesService;
use boxhead\soundcloudsync\services\SoundcloudCategories as SoundcloudCategoriesService;

/**
 * Craft plugins are very much like little applications in and of themselves. We’ve made
 * it as simple as we can, but the training wheels are off. A little prior knowledge is
 * going to be required to write a plugin.
 *
 * For the purposes of the plugin docs, we’re going to assume that you know PHP and SQL,
 * as well as some semi-advanced concepts like object-oriented programming and PHP namespaces.
 *
 * https://docs.craftcms.com/v3/extend/
 *
 * @author    Boxhead
 * @package   SoundcloudSync
 * @since     1.0.0
 *
 * @property  SoundcloudEntriesService $soundcloudEntries
 * @property  SoundcloudCategoriesService $soundcloudCategories
 * @property  Settings $settings
 * @method    Settings getSettings()
 */
class SoundcloudSync extends Plugin
{
    // Public Properties
    // =========================================================================

    public string $schemaVersion = '1.0.0';
    public bool $hasCpSettings = true;
    public bool $hasCpSection = false;

    // Public Methods
    // =========================================================================

    public function init()
    {
        parent::init();

        Craft::setAlias('@soundcloud', __DIR__);

        Craft::$app->onInit(function () {
            $this->attachEventHandlers();
        });
        

        Craft::info(
            Craft::t(
                'soundcloud-sync',
                '{name} plugin loaded',
                ['name' => $this->name]
            ),
            __METHOD__
        );
    }

    private function attachEventHandlers(): void
    {
        // Register our site routes
        Event::on(
            UrlManager::class,
            UrlManager::EVENT_REGISTER_SITE_URL_RULES,
            function (RegisterUrlRulesEvent $event) {
                // $event->rules['siteActionTrigger1'] = 'soundcloud-sync/soundcloud-controller';
                $event->rules['/soundcloud/sync'] = 'soundcloud-sync/sync';
                $event->rules['/soundcloud/authorize'] = 'soundcloud-sync/soundcloud/authorize';
            }
        );

        // Register our CP routes
        Event::on(
            UrlManager::class,
            UrlManager::EVENT_REGISTER_CP_URL_RULES,
            function (RegisterUrlRulesEvent $event) {
                $event->rules['cpActionTrigger1'] = 'soundcloud-sync/soundcloud/do-something';
            }
        );

        // Register our variables
        Event::on(
            CraftVariable::class,
            CraftVariable::EVENT_INIT,
            function (Event $event) {
                /** @var CraftVariable $variable */
                $variable = $event->sender;
                $variable->set('soundcloudSync', SoundcloudSyncVariable::class);
            }
        );

        // Sync Utility
        Event::on(Utilities::class, Utilities::EVENT_REGISTER_UTILITY_TYPES, function (RegisterComponentTypesEvent $event) {
            $event->types[] = SyncUtility::class;
        });
    }

    // Protected Methods
    // =========================================================================

    /**
     * Creates and returns the model used to store the plugin’s settings.
     *
     * @return \craft\base\Model|null
     */
    protected function createSettingsModel(): ?Model
    {
        return Craft::createObject(Settings::class);
    }

    /**
     * Returns the rendered settings HTML, which will be inserted into the content
     * block on the settings page.
     *
     * @return string The rendered settings HTML
     */
    protected function settingsHtml(): ?string
    {
        return Craft::$app->view->renderTemplate(
            'soundcloud-sync/settings',
            [
                'plugin' => $this,
                'settings' => $this->getSettings()
            ]
        );
    }
}
