<?php
/**
 * Soundcloud Sync plugin for Craft CMS 3.x
 *
 * Sync your Soundcloud track data into Craft Entries
 *
 * @link      https://boxhead.io
 * @copyright Copyright (c) 2021 Boxhead
 */

namespace boxhead\soundcloudsync\assetbundles\soundcloudsync;

use Craft;
use craft\web\AssetBundle;
use craft\web\assets\cp\CpAsset;

/**
 * SoundcloudSyncAsset AssetBundle
 *
 * AssetBundle represents a collection of asset files, such as CSS, JS, images.
 *
 * Each asset bundle has a unique name that globally identifies it among all asset bundles used in an application.
 * The name is the [fully qualified class name](http://php.net/manual/en/language.namespaces.rules.php)
 * of the class representing it.
 *
 * An asset bundle can depend on other asset bundles. When registering an asset bundle
 * with a view, all its dependent asset bundles will be automatically registered.
 *
 * http://www.yiiframework.com/doc-2.0/guide-structure-assets.html
 *
 * @author    Boxhead
 * @package   SoundcloudSync
 * @since     1.0.0
 */
class SoundcloudSyncAsset extends AssetBundle
{
    // Public Methods
    // =========================================================================

    /**
     * Initializes the bundle.
     */
    public function init()
    {
        // define the path that your publishable resources live
        $this->sourcePath = "@boxhead/soundcloudsync/assetbundles/soundcloudsync/dist";

        // define the dependencies
        $this->depends = [
            CpAsset::class,
        ];

        // define the relative path to CSS/JS files that should be registered with the page
        // when this asset bundle is registered
        $this->js = [
            'js/SoundcloudSync.js',
        ];

        $this->css = [
            'css/SoundcloudSync.css',
        ];

        parent::init();
    }
}
