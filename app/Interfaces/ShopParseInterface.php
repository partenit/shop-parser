<?php

namespace App\Interfaces;

interface ShopParseInterface
{
    public function setPageUrl(string $url): ShopParseInterface;

    public function getGoodPagesUrls(): array;
}
