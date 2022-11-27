<?php

namespace App\Interfaces;

interface ShopParseInterface
{
    public function setPageUrl(string $url): ShopParseInterface;

    public function requestPageContent(): ShopParseInterface;

    public function getProductPagesUrls(): array;

    public function processProductPages(array $urls): void;
}
