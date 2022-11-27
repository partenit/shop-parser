<?php

namespace App\Services;

use App\Events\RequestPageContentFailed;
use App\Interfaces\ShopParseInterface;
use App\Models\Feature;
use App\Models\Price;
use App\Models\Product;
use Exception;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Http;
use voku\helper\HtmlDomParser;

class TestParseService extends AbstractParseService
{
    protected const DOMAIN = 'httpstat.us';
    protected const SHOP_ID = 2;

    /**
     * @throws Exception
     */
    public function getProductPagesUrls(): array
    {
        return [];
    }

    public function processProductPage($url): array
    {
        return [];
    }

    protected function getDescription(): string
    {
        return '';
    }
}
