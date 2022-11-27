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

class TestParseService implements ShopParseInterface
{
    private const DOMAIN = 'httpstat.us';
    private const SHOP_ID = 1;
    private string $pageUrl;
    private ?HtmlDomParser $pageData = null;
    private HtmlDomParser $htmlDomParser;

    public function __construct()
    {
        $this->htmlDomParser = new HtmlDomParser();
    }

    /**
     * @throws Exception
     */
    public function setPageUrl(string $pageUrl): self
    {
        $this->pageUrl = $pageUrl;
        $this->checkPageUrl();

        return $this;
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

        } catch (Exception $e) {
            throw new Exception('Не удалось получить данные с сайта' . $e->getMessage());
        }

        return $this;
    }

    /**
     * @throws Exception
     */
    public function getProductPagesUrls(): array
    {
        $this->checkPageData();

        $urls = array_map(function ($url) {
            return $url->getAttribute('href');
        }, (array) $this->pageData->findMulti('.listing__body-wrap article .card__body > a'));

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

        if (! str_contains($this->pageUrl, self::DOMAIN)) {
            throw new Exception('Page url is not from appropriate site');
        }
    }

    /**
     * @throws Exception
     */
    private function checkPageData(): void
    {
        if (! $this->pageData) {
            throw new Exception('Страница не содержит данных');
        }
    }

    public function processProductPages(array $urls): void
    {
        array_walk($urls, function ($url) use (&$count) {
            $this->processProductPage($url);
        });
    }

    public function processProductPage($url)
    {
        $this->setPageUrl('https://' . self::DOMAIN . $url)->requestPageContent();

        $product = [
            'shop_id'           => self::SHOP_ID,
            'url'               => $url,
            'code'              => preg_replace("/[^\d,]/", "", $this->pageData->findOne('.product-menu__code')->innertext),
            'name'              => $this->pageData->findOne('h1')->innertext,
            'description'       => $this->getDescription(),
            'prices'            => [
                'discounted'    => preg_replace("/[^\d,]/", "", $this->pageData->findOne('.product-box__main_price')->innertext),
                'normal'        => preg_replace("/[^\d,]/", "", $this->pageData->findOne('.product-box__main_discount label')->innertext),
            ],
            'features'          => array_combine(
                array_map(function ($item) {
                    return trim(strip_tags($item->innertext));
                }, (array) $this->pageData->findMulti('#section-properties .main-details__item_name')),
                array_map(function ($item) {
                    return trim(strip_tags($item->innertext));
                }, (array) $this->pageData->findMulti('#section-properties .main-details__item_value'))
            ),
        ];

        $product['is_available'] = $product['prices']['discounted'] ? 1 : 0;

        $this->storeProduct($product);

        return $product;
    }

    private function getDescription()
    {
        // описание может быть оформлено по разному - обрабатываем два варианта
        $description = $this->pageData->findOneOrFalse('.product-about__container-for-content .main_wrap');
        $description = $description
            ? $description->innerhtml
            : $this->pageData->findOne('.product-about__container-for-content')->innerhtml;

        $description = str_replace("\n", '', strip_tags($description));
        // убираем цепочки пробелов больше одного
        $description = preg_replace('/^([ ]+)|([ ]){2,}/m', '$2', $description);

        return $description;
    }

    private function storeProduct($product)
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
