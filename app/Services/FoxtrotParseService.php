<?php

namespace App\Services;

use App\Interfaces\ShopParseInterface;
use Exception;
use voku\helper\HtmlDomParser;

class FoxtrotParseService implements ShopParseInterface
{
    private const SITE_URL = 'https://www.foxtrot.com.ua';
    private string $pageUrl;
    private HtmlDomParser $pageData;
    private HtmlDomParser $htmlDomParser;

    public function __construct()
    {
        $this->htmlDomParser = new HtmlDomParser();
    }

    public function setPageUrl(string $pageUrl): self
    {
        $this->pageUrl = $pageUrl;
        $this->checkPageUrl();
        $this->pageData = $this->htmlDomParser::file_get_html($this->pageUrl);

        return $this;
    }

    public function getGoodPagesUrls(): array
    {
        $this->checkPageData();
        // listing__body-wrap image-switch article card__body a
        $urls = $this->pageData->findMulti('.listing__body-wrap article .card__body > a');

        $urls = array_map(function ($url) {
            return $url->getAttribute('href');
        }, (array) $urls);

        return $urls;
    }

    private function checkPageUrl(): void
    {
        if (! $this->pageUrl) {
            throw new Exception('Page url is empty');
        }

        if (! filter_var($this->pageUrl, FILTER_VALIDATE_URL)) {
            throw new Exception('Page url is not valid');
        }

        if (! str_contains($this->pageUrl, self::SITE_URL)) {
            throw new Exception('Page url is not from easyhata.site');
        }
    }

    /**
     * @throws Exception
     */
    private function checkPageData(): void
    {
        $this->checkPageUrl();

        if (! $this->pageData) {
            throw new Exception('Page data is empty');
        }
    }

}
