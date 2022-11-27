<?php

namespace App\Services;

use Exception;

class FoxtrotParseService extends AbstractParseService
{
    protected const DOMAIN = 'foxtrot.com.ua';
    protected const SHOP_ID = 1;

    /**
     * @throws Exception
     */
    public function getProductPagesUrls(): array
    {
        $this->checkPageData();

        return array_map(function ($url) {
            return $url->getAttribute('href');
        }, (array) $this->pageData->findMulti('.listing__body-wrap article .card__body > a'));
    }

    public function processProductPage($url): array
    {
        $this->setPageUrl('https://' . self::DOMAIN . $url)->requestPageContent();

        $product = [
            'shop_id'           => static::SHOP_ID,
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

    protected function getDescription(): string
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
}
