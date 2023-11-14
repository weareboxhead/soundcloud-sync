<?php

namespace boxhead\soundcloudsync\utilities;

use Craft;
use craft\base\Utility;

/**
 * Sync Utility
 */
class SyncUtility extends Utility
{
    public static function displayName(): string
    {
        return Craft::t('soundcloud-sync', 'Soundcloud Sync');
    }

    public static function id(): string
    {
        return 'soundcloud-sync';
    }

    public static function iconPath(): ?string
    {
        $iconPath = Craft::getAlias('@soundcloud/icon-mask.svg');

        if (!is_string($iconPath)) {
            return null;
        }

        return $iconPath;
    }

    public static function contentHtml(): string
    {
        return Craft::$app->getView()->renderTemplate('soundcloud-sync/_utilities/actions.twig', [
            'actions' => self::getActions(),
        ]);
    }

    /**
     * Returns available actions.
     */
    public static function getActions(bool $showAll = false): array
    {
        $actions = [];

        $actions[] = [
            'id' => 'sync',
            'label' => Craft::t('soundcloud-sync', 'Sync Now'),
            'instructions' => Craft::t('soundcloud-sync', 'Run the Soundcloud sync operation now.'),
        ];

        return $actions;
    }
}
