<?php

namespace App\Console\Commands;

use App\Interfaces\ShopParseInterface;
use App\Services\FoxtrotParseService;
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
     */
    public function handle()
    {
        $url = $this->argument('url');

        $this->service = $this->resolveService($url);

        if (is_null($this->service)) {
            $this->error('Наш парсер пока не умеет парсить этот магазин');

            return Command::INVALID;
        }

        // проверять на формат URL

        $data = $this->service
            ->setPageUrl($url)
            ->getGoodPagesUrls();

        $this->info(json_encode($data));
        // выводить статистику:
        // сколько каких ошибок
        // сколько обновлено, сколько добавлено
        // отправлено ли письмо админу
        // сколько времени занял процесс

        return Command::SUCCESS;
    }

    protected function resolveService(string $url): ShopParseInterface|null
    {
        return match($url) {
            'foxtrot.com.ua' => app(FoxtrotParseService::class),
            default => null
        };
    }
}
