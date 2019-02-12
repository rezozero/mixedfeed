<?php
/**
 * mixedfeed - FeedItem.php
 *
 * Initial version by: ambroisemaupate
 * Initial version created on: 2019-02-12
 */

namespace RZ\MixedFeed\Canonical;

class FeedItem
{
    /**
     * @var string
     */
    protected $platform;
    /**
     * @var string
     */
    protected $author;
    /**
     * @var string
     */
    protected $link;
    /**
     * @var string
     */
    protected $title;
    /**
     * @var Image[]
     */
    protected $images = [];
    /**
     * @var string
     */
    protected $message;
    /**
     * @var \DateTime
     */
    protected $dateTime;

    /**
     * @return string
     */
    public function getPlatform()
    {
        return $this->platform;
    }

    /**
     * @param string $platform
     *
     * @return FeedItem
     */
    public function setPlatform($platform)
    {
        $this->platform = $platform;

        return $this;
    }

    /**
     * @return string
     */
    public function getAuthor()
    {
        return $this->author;
    }

    /**
     * @param string $author
     *
     * @return FeedItem
     */
    public function setAuthor($author)
    {
        $this->author = $author;

        return $this;
    }

    /**
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * @param string $title
     *
     * @return FeedItem
     */
    public function setTitle($title)
    {
        $this->title = $title;

        return $this;
    }

    /**
     * @return Image[]
     */
    public function getImages()
    {
        return $this->images;
    }

    /**
     * @param Image[] $images
     *
     * @return FeedItem
     */
    public function setImages($images)
    {
        $this->images = $images;

        return $this;
    }

    /**
     * @param Image $image
     *
     * @return FeedItem
     */
    public function addImage(Image $image)
    {
        $this->images[] = $image;
        return $this;
    }

    /**
     * @return string
     */
    public function getMessage()
    {
        return $this->message;
    }

    /**
     * @param string $message
     *
     * @return FeedItem
     */
    public function setMessage($message)
    {
        $this->message = $message;

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getDateTime()
    {
        return $this->dateTime;
    }

    /**
     * @param \DateTime $dateTime
     *
     * @return FeedItem
     */
    public function setDateTime($dateTime)
    {
        $this->dateTime = $dateTime;

        return $this;
    }

    /**
     * @return string
     */
    public function getLink()
    {
        return $this->link;
    }

    /**
     * @param string $link
     *
     * @return FeedItem
     */
    public function setLink($link)
    {
        $this->link = $link;

        return $this;
    }
}
