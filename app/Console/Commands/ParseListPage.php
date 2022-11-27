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
    protected $signature = 'parse:execute {url}';

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
        $url = $this->argument('url');

        // Проверяем, что передали валидный URL
        if (! filter_var($url, FILTER_VALIDATE_URL)) {
            $this->error('Переданный параметр не соответствует формату URL');

            return Command::INVALID;
        }

        // находим сервис, умеющий обрабатывать этот URL
        $this->service = $this->resolveService($url);

        // сообщаем, что сервис не найден
        if (is_null($this->service)) {
            $this->error('Наш парсер пока не умеет парсить этот магазин');

            return Command::INVALID;
        }

        $this->comment('Парсим страницу со списком товаров...');

        $data = $this->service
            ->setPageUrl($url)
            ->requestPageContent()
            ->getProductPagesUrls();

        // сообщаем, что не удалось получить список товаров
        if (empty($data)) {
            $this->error('Список товаров на странице не обнаружен');

            return Command::FAILURE;
        }

        $this->withProgressBar($data, function ($url) {
            $this->service->processProductPage($url);
        });

        //$this->service->processProductPages($data);

        $this->info('Парсинг завершен');
        //$this->info($this->service->getReport());

        // выводить статистику:
        // сколько каких ошибок
        // сколько обновлено, сколько добавлено
        // отправлено ли письмо админу
        // сколько времени занял процесс

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
