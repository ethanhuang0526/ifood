<?php

namespace App\Http\Controllers;

use App\Models\Restaurant;
use Illuminate\Http\Request;

class IFoodController extends Controller
{
    public function index(Request $request)
    {
        $params = $request->only(['search', 'order', 'types', 'distance', 'current_position']);

        $calcu = [];
        if ($params['distance'] > 0 && $params['current_position']) {
            $all = Restaurant::get(['id', 'latitude', 'longitude']);

            foreach($all as $item) {
                $dis = $this->haversineGreatCircleDistance($item->latitude, $item->longitude, json_decode($params['current_position'], true)['lat'], json_decode($params['current_position'], true)['lng']);

                if ($dis <= $params['distance'] * 1000) {
                    $calcu[] = $item->id;
                }
            }
        }

        $stores = Restaurant::when(strlen($params['search']) > 0, function ($query) use ($params) {
            $key = sprintf('%%%s%%', $params['search']);
            $query->where('title', 'like', $key)
                ->orWhere('address', 'like', $key);
        })
            ->when($params['order'], function ($query) use($params) {
                switch($params['order']) {
                    case 1:
                        $query->orderBy('score', 'DESC');
                        break;
                    case 2:
                        $query->orderBy('score', 'ASC');
                        break;
                    case 3:
                        $query->orderBy('min_price', 'DESC');
                        break;
                    case 4:
                        $query->orderBy('min_price', 'ASC');
                        break;
//                    case 5:
//                        $query->orderBy('score', 'DESC');
//                        break;
//                    case 6:
//                        $query->orderBy('score', 'DESC');
//                        break;
                }

            })
            ->when(strlen($params['types']) > 0, function($query) use ($params) {
                $query->where(function ($build) use ($params) {
                    foreach(explode(',', $params['types']) as $type) {
                        $build->orWhereJsonContains('categories', $type);
                    }
                });
            })
            ->when($params['distance'] > 0, function ($query) use ($calcu) {
                $query->whereIn('id', $calcu);
            })
            ->paginate(10);

        return response()->json($stores);
    }

    public function filters(Request $request)
    {
        $filters = [];
        $categories = Restaurant::get(['categories'])->pluck('categories')->flatten();

        foreach($categories as $category) {
            isset($filters[$category]) ? $filters[$category]++ : $filters[$category] = 1;
        }

        $filterLists = [];
        foreach($filters as $key => $amount) {
            $filterLists[] = [
                'type' => $key,
                'amount' => $amount
            ];
        }

        return response()->json($filterLists);
    }

    private function haversineGreatCircleDistance(
        $latitudeFrom, $longitudeFrom, $latitudeTo, $longitudeTo, $earthRadius = 6371000)
    {
        // convert from degrees to radians
        $latFrom = deg2rad($latitudeFrom);
        $lonFrom = deg2rad($longitudeFrom);
        $latTo = deg2rad($latitudeTo);
        $lonTo = deg2rad($longitudeTo);

        $latDelta = $latTo - $latFrom;
        $lonDelta = $lonTo - $lonFrom;

        $angle = 2 * asin(sqrt(pow(sin($latDelta / 2), 2) +
                cos($latFrom) * cos($latTo) * pow(sin($lonDelta / 2), 2)));
        return $angle * $earthRadius;
    }
}
