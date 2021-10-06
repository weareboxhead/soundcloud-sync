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
 * Soundcloud Model
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
class Oauth extends Model
{
    // Public Properties
    // =========================================================================

    /**
     * Access Token 
     *
     * @var string
     */
    public $accessToken;
    
    /**
     * Refresh Token 
     *
     * @var string
     */
    public $refreshToken;
    
    /**
     * Access Token expiry date/time 
     *
     * @var datetime
     */
    public $expires;

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
            [['accessToken', 'refreshToken'], 'string'],
            [['accessToken', 'refreshToken', 'expires'], 'required'],
        ];
    }
}
