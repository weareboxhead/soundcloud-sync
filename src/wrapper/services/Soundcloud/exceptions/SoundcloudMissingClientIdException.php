<?php
namespace boxhead\soundcloudsync\wrapper\services\Soundcloud\exceptions;

/**
 * Soundcloud missing client id exception.
 *
 * @category Services
 * @package Services_Soundcloud
 * @author Anton Lindqvist <anton@qvister.se>
 * @copyright 2010 Anton Lindqvist <anton@qvister.se>
 * @license http://www.opensource.org/licenses/mit-license.php MIT
 * @link http://github.com/mptre/php-soundcloud
 */
class SoundcloudMissingClientIdException extends \Exception {

    /**
     * Default message.
     *
     * @access protected
     *
     * @var string
     */
    protected $message = 'All requests must include a consumer key. Referred to as client_id in OAuth2.';

}