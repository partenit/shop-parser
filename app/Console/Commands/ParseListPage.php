<?php

namespace App\Console\Commands;

use App\Interfaces\ShopParseInterface;
use App\Services\FoxtrotParseService;
use App\Services\TestParseService;
use Exception;
use Illuminate\Console\Command;

class ParseListPage extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'parse:execute {url?} {--limit=0}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Парсим страницу со списком товаров, затем страницы каждого товара, полученные данные сохраняем в БД';


    protected ShopParseInterface|null $service;

    /**
     * Execute the console command.
     *
     * @return int
     * @throws Exception
     */
    public function handle()
    {
        $url    = $this->argument('url');
        $limit  = $this->option('limit');
        $count  = 0;

        if (! $url) {
            $this->error('Ошибка: не указан url страницы со списком товаров');

            return Command::INVALID;
        }

        // Проверяем, что передали валидный URL
        if (! filter_var($url, FILTER_VALIDATE_URL)) {
            $this->error('Переданный параметр не соответствует формату URL');

            return Command::INVALID;
        }

        // находим сервис, умеющий обрабатывать этот магазин
        $this->service = $this->resolveService($url);

        // сообщаем, что сервис не найден
        if (is_null($this->service)) {
            $this->error('Наш парсер пока не умеет парсить этот магазин');

            return Command::INVALID;
        }

        $start = microtime(true);
        $this->comment('Обрабатываем страницу со списком товаров...');

        $data = $this->service
            ->setPageUrl($url)
            ->requestPageContent()
            ->getProductPagesUrls();

        // сообщаем, что не удалось получить список товаров
        if (empty($data)) {
            $this->error('Список товаров на странице не обнаружен');

            return Command::FAILURE;
        }

        $this->comment('Обрабатываем страницы товаров...');
        $this->withProgressBar($data, function ($url) use (&$count, $limit) {
            $count++;

            if ($limit && $count > $limit) {
                return;
            }

            $product    = $this->service->processProductPage($url);
            $is_exists  = $this->service->isProductExists($product);
            $is_success = $this->service->storeProduct($product);

            $this->service->updateReport($is_exists, $is_success, (bool) $product['is_available']);
        });

        $this->newLine(2);
        $this->info('Парсинг завершен');
        $this->newLine();
        $this->info('Время выполнения: ' . number_format(microtime(true) - $start, 2) . ' сек.');
        $this->newLine();
        $this->info($this->service->getReport());

        return Command::SUCCESS;
    }

    protected function resolveService(string $url): ShopParseInterface|null
    {
        return match(parse_url($url, PHP_URL_HOST)) {
            'httpstat.us' => app(TestParseService::class), // для проверки 404 и 503
            'foxtrot.com.ua', 'www.foxtrot.com.ua' => app(FoxtrotParseService::class),
            default => null
        };
    }
}
