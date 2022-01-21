<?php

namespace RZ\MixedFeed\Canonical;

use DateTime;
use stdClass;

class FeedItem
{
    protected string $id = '';

    protected string $platform = '';

    protected string $author = '';

    protected string $link = '';

    protected string $title = '';

    /**
     * @var Image[]
     */
    protected array $images = [];

    protected string $message = '';

    protected ?DateTime $dateTime = null;

    /** @var string[] */
    protected array $tags = [];

    protected ?int $likeCount = null;

    protected ?stdClass $raw = null;

    /**
     * Share, comments or retweet count depending on platform.
     */
    protected ?int $shareCount = null;

    public function getId(): string
    {
        return $this->id;
    }

    public function setId(string $id): FeedItem
    {
        $this->id = $id;

        return $this;
    }

    public function getRaw(): ?stdClass
    {
        return $this->raw;
    }

    public function setRaw(stdClass $object): FeedItem
    {
        $this->raw = $object;

        return $this;
    }

    public function getPlatform(): string
    {
        return $this->platform;
    }

    public function setPlatform(string $platform): FeedItem
    {
        $this->platform = $platform;

        return $this;
    }

    public function getAuthor(): string
    {
        return $this->author;
    }

    public function setAuthor(string $author): FeedItem
    {
        $this->author = $author;

        return $this;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): FeedItem
    {
        $this->title = $title;

        return $this;
    }

    /** @return Image[] */
    public function getImages(): array
    {
        return $this->images;
    }

    /** @param Image[] $images */
    public function setImages(array $images): FeedItem
    {
        $this->images = $images;

        return $this;
    }

    public function addImage(Image $image): FeedItem
    {
        $this->images[] = $image;

        return $this;
    }

    public function getMessage(): string
    {
        return $this->message;
    }

    public function setMessage(string $message): FeedItem
    {
        $this->message = $message;

        return $this;
    }

    public function getDateTime(): ?DateTime
    {
        return $this->dateTime;
    }

    public function setDateTime(?DateTime $dateTime): FeedItem
    {
        $this->dateTime = $dateTime;

        return $this;
    }

    public function getLink(): string
    {
        return $this->link;
    }

    public function setLink(string $link): FeedItem
    {
        $this->link = $link;

        return $this;
    }

    /** @return string[] */
    public function getTags(): array
    {
        return $this->tags;
    }

    /** @param string[] $tags */
    public function setTags(array $tags): FeedItem
    {
        $this->tags = $tags;

        return $this;
    }

    public function getLikeCount(): ?int
    {
        return $this->likeCount;
    }

    public function setLikeCount(int $likeCount): FeedItem
    {
        $this->likeCount = $likeCount;

        return $this;
    }

    public function getShareCount(): ?int
    {
        return $this->shareCount;
    }

    public function setShareCount(int $shareCount): FeedItem
    {
        $this->shareCount = $shareCount;

        return $this;
    }
}
