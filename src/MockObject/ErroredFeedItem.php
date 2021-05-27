<?php
namespace RZ\MixedFeed\MockObject;

/**
 * ErroredFeedItem for displaying something even if feed
 * provider errored.
 */
class ErroredFeedItem extends \stdClass
{
    /**
     * @var \Datetime
     */
    public $normalizedDate;
    /**
     * @var string
     */
    public $message;
    /**
     * @var string
     */
    public $feedItemPlatform;

    /**
     * @param string $message
     * @param string $feedItemPlatform
     */
    public function __construct($message, $feedItemPlatform)
    {
        $this->message = $message;
        $this->feedItemPlatform = $feedItemPlatform . '[errored]';
        $this->normalizedDate = new \Datetime('now');
        $this->canonicalMessage = $message;
    }
}
