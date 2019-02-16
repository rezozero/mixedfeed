<?php

namespace RZ\MixedFeed\Response;

class FeedItemResponse
{
    /**
     * @var array
     */
    protected $items;

    /**
     * @var array
     */
    protected $meta;

    /**
     * FeedItemResponse constructor.
     *
     * @param array $feedItems
     * @param array $meta
     */
    public function __construct(array $feedItems, array $meta)
    {
        $this->items = $feedItems;
        $this->meta = $meta;
    }

    /**
     * @return array
     */
    public function getItems(): array
    {
        return $this->items;
    }

    /**
     * @param array $items
     *
     * @return FeedItemResponse
     */
    public function setItems(array $items): FeedItemResponse
    {
        $this->items = $items;

        return $this;
    }

    /**
     * @return array
     */
    public function getMeta(): array
    {
        return $this->meta;
    }

    /**
     * @param array $meta
     *
     * @return FeedItemResponse
     */
    public function setMeta(array $meta): FeedItemResponse
    {
        $this->meta = $meta;

        return $this;
    }
}
