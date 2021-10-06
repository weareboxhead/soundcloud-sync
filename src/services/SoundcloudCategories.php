<?php
/**
 * Soundcloud Sync plugin for Craft CMS 3.x
 *
 * Sync your Soundcloud track data into Craft Entries
 *
 * @link      https://boxhead.io
 * @copyright Copyright (c) 2021 Boxhead
 */

namespace boxhead\soundcloudsync\services;

use boxhead\soundcloudsync\SoundcloudSync;

use Craft;
use craft\base\Component;
use craft\elements\Category;
use craft\helpers\StringHelper;
use DateTime;

use boxhead\soundcloudsync\wrapper\services\Services_Soundcloud;

/**
 * SoundcloudCategories Service
 *
 * All of your plugin’s business logic should go in services, including saving data,
 * retrieving data, etc. They provide APIs that your controllers, template variables,
 * and other plugins can interact with.
 *
 * https://craftcms.com/docs/plugins/services
 *
 * @author    Boxhead
 * @package   SoundcloudSync
 * @since     1.0.0
 */
class SoundcloudCategories extends Component
{
    // Any multiple word tags will be split by this
	private $quote;
	private $quoteLength;

	function __construct()
	{
		$this->quote = '"';
		$this->quoteLength = strlen($this->quote);
	}

    // Public Methods
    // =========================================================================

    /**
     * This function can literally be anything you want, and you can have as many service
     * functions as you want
     *
     * From any other plugin file, call it like this:
     *
     *     SoundcloudSync::$plugin->soundcloudCategories->exampleService()
     *
     * @return mixed
     */
    // public function exampleService()
    // {
    //     $result = 'something';
    //     // Check our Plugin's settings for `someAttribute`
    //     if (SoundcloudSync::$plugin->getSettings()->someAttribute) {
    //     }

    //     return $result;
    // }

	private function camelCase($string)
	{
		$parts = explode(' ', strtolower($string));

		$string = '';

		foreach ($parts as $part) {
			if (strlen($string)) {
				$part = ucfirst($part);
			}

			$string .= $part;
		}

		return $string;
	}

	private function existingCategorysId($categoryGroupId, $category)
	{
		// Get all the categories from this group
		$query = Category::find()
			->groupId($categoryGroupId)
			->all();

		foreach ($query as $existingCategory) {
			// If this category was found, return its id
			if ($existingCategory->slug === StringHelper::toKebabCase($category)) {
				return $existingCategory->id;
			}
		}

		// If none were found, return false
		return false;
	}

	private function saveSpecialCategory($group, $category)
	{
		// Get the handle for this group
		$groupHandle = $this->camelCase($group);
		
		// Get the category group
		$categoryGroup = Category::find()
			->group($groupHandle)
			->one();

		// Get the category group id
		$categoryGroupId = $categoryGroup->groupId;

		// Remove any quotes from the category
		if (substr($category, 0, 1) === $this->quote) {
			$category = substr($category, 1, strlen($category) - 2);
		}

		// Remove the marker (Remove the length of the marker plus the colon)
		$category = substr($category, strlen($group) + 1);

		// Get the id of this category
		$id = $this->existingCategorysId($categoryGroupId, $category);

		// If this category doesn't currently exist (no id was found), create it
		if (!$id) {
			// Create a new category model
			$newCategory = new Category();
			
			// Set the group id
			$newCategory->groupId = $categoryGroupId;
			
			// Set the title
			$newCategory->title = $category;
			
			// Save the category
			if (!Craft::$app->elements->saveElement($newCategory)) {
				Craft::error('SoundcloudSync: Couldn’t save the category "' . $newCategory->title . '"', __METHOD__);

				return false;
			}
			
			// Get this id
			$id = $newCategory->id;
		}

		return [$id];
	}

	private function separateSpecialCategories($data, $standardCategories, $categoryGroups)
	{
		// Set up an empty array
		$categoriesArray = array();

		foreach ($categoryGroups as $group) {
			$startingIndex = strpos($standardCategories, strtolower($group) . ':');

			// If we found this special marker in the standard categories
			if ($startingIndex !== false) {
				// If this category has a quote before it, look for the next quote
				// If $starting index is the beginning of the string (0), using -1 will get the last character in the string, we don't want that
				if ($startingIndex !== 0 && substr($standardCategories, $startingIndex - 1, 1) === $this->quote) {
					// The ending index is the next quote, plus the starting index, plus 1 to include the quote
					$endingIndex = strpos(substr($standardCategories, $startingIndex), $this->quote) + $startingIndex + 1;
					
					// Reduce the starting index by 1 to include the quote
					$startingIndex --;
				}
				// If this category doesn't have a quote before it
				else {
					// Find the next space
					$endingIndex = strpos(substr($standardCategories, $startingIndex), ' ') + $startingIndex;

					// If there wasn't one found, the end will be the end of the categories string
					if (!$endingIndex) {
						$endingIndex = strlen($standardCategories);
					}
				}

				// Get this special category
				$specialCategory = substr($standardCategories, $startingIndex, $endingIndex - $startingIndex);

				// Remove it from the 'standard' category list and trim any now existing white space
				$standardCategories = trim(substr($standardCategories, 0, $startingIndex) . substr($standardCategories, $endingIndex));

				// Create a key for the entry which matches this category's group
				$categoriesArray['soundcloudCategories' . ucfirst($group)] = $this->saveSpecialCategory($group, $specialCategory);
			}
		}

		// Assign what is left of the category string as the standard categories
		// $categoriesArray['standardCategories'] = $standardCategories;

		return $categoriesArray;
	}

	public function getCategories($data, $categoryGroups)
	{
		$categories 		= array();
		$standardCategories = '';
		$genreList 			= $data['genre'];
		$tagList 			= $data['tag_list'];

		// If this entry has tags (first one is counted as the genre)
		if (!empty($genreList) || !empty($tagList))
		{
			// If the genre is set
			if (!empty($genreList))
			{
				// Add it to the list
				$standardCategories .= trim($genreList);

				// If the genre has a space inside of it, we need to quote it so it's formatting is consistent with the other tags
				if (strpos($standardCategories, ' '))
				{
					$standardCategories = $this->quote . $standardCategories . $this->quote;
				}
			}

			// If there is a remaining tag list
			if (!empty($tagList))
			{
				// Add in the rest of the tags
				$standardCategories .= ' ' . $tagList;
			}

			// Lower case the tags to help limit the effects of human error on tag input
			$standardCategories = strtolower($standardCategories);

			// If we need to check for special categories present, handle that separately
			if (is_array($categoryGroups)) {
				$categories = $this->separateSpecialCategories($data, $standardCategories, $categoryGroups);
			}
			// Otherwise, just assign the standard categories as a key here
			else {
				// $categories['standardCategories'] = $standardCategories;
			}
		}

		return $categories;
	}

    // Private Methods
    // =========================================================================

    private function dd($data)
    {
        echo '<pre>';
        print_r($data);
        echo '</pre>';
        die();
    }
}

