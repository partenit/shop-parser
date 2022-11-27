<?php

namespace Tests\Feature;

use App\Models\Product;
use Illuminate\Support\Str;
use Tests\TestCase;
use Faker\Factory;

class ParseListPageCommandTest extends TestCase
{
    public function testParseListPageCommandWithoutParameter()
    {
        $this->artisan('parse:execute')->assertExitCode(2);
    }

    public function testParseListPageCommandWithWrongUrl()
    {
        $this->artisan('parse:execute ' . Str::random(10))->assertExitCode(2);
    }

    public function testParseListPageCommandWithUnknownUrl()
    {
        $this->artisan('parse:execute ' . Factory::create()->url)->assertExitCode(2);
    }

    public function testParseListPageCommandWithNoCategoryUrl()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Страница не содержит данных');

        $this->artisan('parse:execute ' . 'https://foxtrot.com.ua/' . Str::random(10));
    }

    public function testParseListPageCommandWithCorrectUrl()
    {
        $this->assertTrue(Product::count() === 0);

        $this->artisan('parse:execute ' . 'https://www.foxtrot.com.ua/ru/shop/stiralki_whirlpool.html --limit=1')
            ->assertExitCode(0);

        $this->assertTrue(Product::count() === 1);

    }

}
