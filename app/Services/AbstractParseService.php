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

abstract class AbstractParseService implements ShopParseInterface
{
    protected const DOMAIN = 'abstract';
    protected const SHOP_ID = 'abstract';
    protected string $pageUrl;
    protected ?HtmlDomParser $pageData = null;
    protected HtmlDomParser $htmlDomParser;

    public function __construct()
    {
        $this->htmlDomParser = new HtmlDomParser();
    }

    /**
     * @throws Exception
     */
    abstract public function getProductPagesUrls(): array;

    /**
     * @throws Exception
     */
    public function setPageUrl(string $pageUrl): self
    {
        $this->pageUrl = $pageUrl;
        $this->checkPageUrl();

        return $this;
    }

    protected function checkPageUrl(): void
    {
        if (! $this->pageUrl) {
            throw new Exception('Page url is empty');
        }

        if (! filter_var($this->pageUrl, FILTER_VALIDATE_URL)) {
            throw new Exception('Page url is not valid');
        }

        if (! str_contains($this->pageUrl, static::DOMAIN)) {
            throw new Exception('Page url is not from appropriate site');
        }
    }

    /**
     * @throws Exception
     */
    public function requestPageContent(): ShopParseInterface
    {
        try {
            //TODO учесть таймаут и повторы
            $response = Http::get($this->pageUrl);

            if ($response->status() === Response::HTTP_OK) {
                $this->pageData = $this->htmlDomParser->load($response->body());
            } else {
                event(new RequestPageContentFailed($response, $this->pageUrl));
            }

        } catch (Exception) {
            throw new Exception('Не удалось получить данные с сайта');
        }

        return $this;
    }

    public function processProductPages(array $urls): void
    {
        array_walk($urls, function ($url) use (&$count) {
            $this->processProductPage($url);
        });
    }

    abstract public function processProductPage($url): array;

    abstract protected function getDescription(): string;

    /**
     * @throws Exception
     */
    protected function checkPageData(): void
    {
        if (! $this->pageData) {
            throw new Exception('Страница не содержит данных');
        }
    }

    protected function storeProduct($product):void
    {
        $product_item = Product::updateOrCreate([
            'shop_id'       => $product['shop_id'],
            'code'          => $product['code'],
        ], [
            'shop_id'       => $product['shop_id'],
            'code'          => $product['code'],
            'url'           => $product['url'],
            'name'          => $product['name'],
            'description'   => $product['description'],
            'is_available'  => $product['is_available'],
        ]);


        Price::where('product_id', $product_item->id)->delete();

        foreach ($product['prices'] as $label => $price) {
            Price::create([
                'product_id' => $product_item->id,
                'label' => $label,
                'price' => $price,
            ]);
        }

        Feature::where('product_id', $product_item->id)->delete();

        foreach ($product['features'] as $label => $value) {
            Feature::create([
                'product_id' => $product_item->id,
                'label' => $label,
                'value' => $value,
            ]);
        }
    }
}
