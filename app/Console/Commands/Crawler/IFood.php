<?php

namespace App\Console\Commands\Crawler;

use GuzzleHttp\Client;
use Illuminate\Console\Command;
use Symfony\Component\DomCrawler\Crawler;

class IFood extends Command
{
    const ENDPOINT = 'https://ifoodie.tw/explore/%s/list/%s?page=%d';
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'crawler:ifood
                            {--C|city=新北市 : 城市}
                            {--A|area=三重區 : 地區}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '爬取 IFood 網站餐廳資料';

    protected $page = 1;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->client = new Client();

        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $crawler = $this->getContent($this->option('city'), $this->option('area'), 1);

        $target = $crawler->filterXPath('//*/div[contains(@class, "search-condition")]/div/div/span');
        preg_match('/\d+/m', $target->first()->html(), $match);
        $restaurants = $crawler->filterXPath('//*/div[contains(@class, "restaurant-item")]');
        $totalPage = ceil($match[0] / $restaurants->count());

        for ($page = $this->page; $page <= $totalPage; $page++) {
            var_dump($page);

            $crawler = $this->getContent($this->option('city'), $this->option('area'), $page);
            $restaurants = $crawler->filterXPath('//*/div[contains(@class, "restaurant-item")]');

            $insertData = [];
            $restaurants->each(function ($node) use (&$insertData) {
                $categories = [];
                $node->filterXPath('//div[contains(@class, "category-row")]/a')->each(function ($node) use (&$categories) {
                    $categories[] = $node->text();
                });

                // 均消
                $price = @$node->filterXPath('//div[contains(@class, "review-row")]//div[contains(@class, "avg-price")]');
                preg_match('/\d+/m', $price->count() > 0 ? $price->text() : '' , $match);

                $score = @$node->filterXPath('//div[contains(@class, "review-row")]//div/div[1]/div[1]');
                $insertData[] = [
                    'image' => @$node->filterXPath('//img')->evaluate('string(@src)')[0],
                    'title' => @$node->filterXPath('//div[contains(@class, "title-row")]//a')->text(),
                    'address' => @$node->filterXPath('//div[contains(@class, "address-row")]')->text(),
                    'categories' => json_encode($categories),
                    'score' => $score->count() > 0 ? $score->text() : 0,
                    'min_price' => count($match) > 0 ? $match[0] : '',
                    'created_at' => now(),
                    'updated_at' => now()
                ];
            });

            \App\Models\Restaurant::insert($insertData);
        }
    }

    private function getContent($city, $area, $page)
    {
        $url = sprintf(self::ENDPOINT, $city, $area, $page);

        $content = $this->client->get($url)->getBody()->getContents();
        $crawler = new Crawler();
        $crawler->addHtmlContent($content);

        return $crawler;
    }
}
