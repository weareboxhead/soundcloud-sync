<?php
/**
 * Soundcloud Sync plugin for Craft CMS 3.x
 *
 * Sync your Soundcloud track data into Craft Entries
 *
 * @link      https://boxhead.io
 * @copyright Copyright (c) 2021 Boxhead
 */

namespace boxhead\soundcloudsync\models;

use boxhead\soundcloudsync\SoundcloudSync;

use Craft;
use craft\base\Model;

/**
 * SoundcloudSync Settings Model
 *
 * This is a model used to define the plugin's settings.
 *
 * Models are containers for data. Just about every time information is passed
 * between services, controllers, and templates in Craft, it’s passed via a model.
 *
 * https://craftcms.com/docs/plugins/models
 *
 * @author    Boxhead
 * @package   SoundcloudSync
 * @since     1.0.0
 */
class Settings extends Model
{
    // Public Properties
    // =========================================================================

     /**
     * @var int Section ID
     */
    public $sectionId = 1;

     /**
     * @var int Entry Type ID
     */
    public $entryTypeId = 1;

     /**
     * @var string Category Groups
     */
    public $categoryGroups;

    /**
     * @var string Soundcloud Client ID
     */
    public $soundcloudClientId;

    /**
     * @var string Soundcloud Client Secret
     */
    public $soundcloudClientSecret;

    /**
     * @var string Soundcloud User ID
     */
    public $soundcloudUserId;

    // Public Methods
    // =========================================================================

    /**
     * Returns the validation rules for attributes.
     *
     * Validation rules are used by [[validate()]] to check if attribute values are valid.
     * Child classes may override this method to declare different validation rules.
     *
     * More info: http://www.yiiframework.com/doc-2.0/guide-input-validation.html
     *
     * @return array
     */
    public function rules()
    {
        return [
            [['soundcloudClientId', 'soundcloudClientSecret', 'soundcloudUserId', 'sectionId', 'entryTypeId'], 'required'],
            [['categoryGroups', 'soundcloudClientId', 'soundcloudClientSecret', 'soundcloudUserId'], 'string'],
            ['sectionId', 'integer'],
            ['entryTypeId', 'integer'],
        ];
    }

    /**
     * @return string the parsed soundcloud client id key (e.g. 'XXXXXXXXXXX')
     */
    public function getSoundcloudClientId(): string
    {
        return Craft::parseEnv($this->soundcloudClientId);
    }
    
    /**
     * @return string the parsed soundcloud client secret key (e.g. 'XXXXXXXXXXX')
     */
    public function getSoundcloudClientSecret(): string
    {
        return Craft::parseEnv($this->soundcloudClientSecret);
    }
}
