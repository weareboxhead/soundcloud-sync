<?php

namespace boxhead\soundcloudsync\models;

use craft\base\Model;
use craft\helpers\App;
use craft\behaviors\EnvAttributeParserBehavior;

/**
 * SoundcloudSync Settings Model
 *
 */
class Settings extends Model
{
    // Public Properties
    // =========================================================================
    public int $sectionId = 1;
    public int $entryTypeId = 1;
    public ?string $categoryGroups;
    public string $soundcloudClientId;
    public string $soundcloudClientSecret;
    public string $soundcloudUserId;

    // Public Methods
    // =========================================================================
    public function defineRules(): array
    {
        return [
            [['soundcloudClientId', 'soundcloudClientSecret', 'soundcloudUserId', 'sectionId', 'entryTypeId'], 'required'],
            [['categoryGroups', 'soundcloudClientId', 'soundcloudClientSecret', 'soundcloudUserId'], 'string'],
            ['sectionId', 'integer'],
            ['entryTypeId', 'integer'],
        ];
    }

    public function behaviors(): array
    {
        return [
            'parser' => [
                'class' => EnvAttributeParserBehavior::class,
                'attributes' => ['soundcloudClientId', 'soundcloudClientSecret', 'soundcloudUserId'],
            ],
        ];
    }

    /**
     * @return string the parsed soundcloud client id key (e.g. 'XXXXXXXXXXX')
     */
    public function getSoundcloudClientId(): string
    {
        return App::parseEnv($this->soundcloudClientId);
    }

    /**
     * @return string the parsed soundcloud client secret key (e.g. 'XXXXXXXXXXX')
     */
    public function getSoundcloudClientSecret(): string
    {
        die('here');
        return App::parseEnv($this->soundcloudClientSecret);
    }

    /**
     * @return string the parsed soundcloud user id (e.g. 'XXXXXXXXXXX')
     */
    public function getSoundcloudUserId(): string
    {
        return App::parseEnv($this->soundcloudUserId);
    }
}
