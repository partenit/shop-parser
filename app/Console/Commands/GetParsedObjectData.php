<?php

namespace App\Console\Commands;

use App\Models\Price;
use App\Models\Product;
use Exception;
use Illuminate\Console\Command;

class GetParsedObjectData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'parse:object_data {code}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Получить все данные спарсенного объекта по его коду';


    /**
     * Execute the console command.
     *
     * @return int
     * @throws Exception
     */
    public function handle()
    {
        dd(
            Product::where('code', $this->argument('code'))
                ->with('prices')
                ->with('features')
                ->first()
                ?->toArray()
        );
    }
}
