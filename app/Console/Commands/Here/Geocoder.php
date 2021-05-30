<?php

namespace App\Console\Commands\Here;

use App\Models\Restaurant;
use Illuminate\Console\Command;
use Thiagoprz\HereGeocoder\HereGeocoder;

class Geocoder extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'here:geocoder';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '取得餐廳地址座標';

    protected $restaurant;

    public function __construct(
        Restaurant $restaurant
    ) {
        parent::__construct();

        $this->restaurant = $restaurant;
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $geocoder = new HereGeocoder();
        $this->restaurant->whereNotNull('address')->whereNull('latitude')->whereNull('longitude')
            ->chunkById(100, function ($restaurants) use ($geocoder) {
                foreach ($restaurants as $restaurant) {
                    var_dump($restaurant->address);
                    $response = $geocoder->geocode($restaurant->address);
                    $position = $response->Response->View[0]->Result[0]->Location->DisplayPosition;
                    $restaurant->update([
                        'latitude' => $position->Latitude,
                        'longitude' => $position->Longitude
                    ]);
                }
        });
    }
}
