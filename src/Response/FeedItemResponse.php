<?php

namespace RZ\MixedFeed\Response;

use RZ\MixedFeed\Canonical\FeedItem;

class FeedItemResponse
{
    /** @var FeedItem[] */
    protected array $items;

    /** @var mixed[] */
    protected array $meta;

    /**
     * @param FeedItem[] $feedItems
     * @param mixed[]    $meta
     */
    public function __construct(array $feedItems, array $meta)
    {
        $this->items = $feedItems;
        $this->meta = $meta;
    }

    /** @return FeedItem[] */
    public function getItems(): array
    {
        return $this->items;
    }

    /** @param FeedItem[] $items */
    public function setItems(array $items): FeedItemResponse
    {
        $this->items = $items;

        return $this;
    }

    /** @return mixed[] */
    public function getMeta(): array
    {
        return $this->meta;
    }

    /** @param mixed[] $meta */
    public function setMeta(array $meta): FeedItemResponse
    {
        $this->meta = $meta;

        return $this;
    }
}
