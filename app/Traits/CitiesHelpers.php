<?php

namespace App\Traits;

use App\Models\City;
use Google\Service\ServiceUsage\Http;

trait CitiesHelpers
{
    private function getCitiesLocations($city_id)
    {
        // Predefined cities with latitudes and longitudes
        $cities = [
            3766 => [24.4539, 54.3773],
            3767 => [25.4052, 55.5136],
            3768 => [25.276987, 55.296249],
            3769 => [25.8007, 55.9761],
            3770 => [25.3533, 55.381],
            3771 => [25.3533, 55.381],
            3772 => [25.589, 55.5635],
            3773 => [25.1287, 56.3269]
        ];

        // Check if the city_id exists in the predefined cities array
        if (isset($cities[$city_id])) {
            return $cities[$city_id]; // Return the lat and lon directly
        }

        // Fetch the city name from your database using the city_id
        $city = City::find($city_id);
        if (!$city) {
            return null; // or handle the error as needed
        }

        $city_name = $city->name; // Assuming the city name is stored in the 'name' field

        // Google Maps Geocoding API endpoint
        $apiKey = 'AIzaSyClrFqfOqOGTSGWpiZby6POa-AEFjGmJoM'; // Replace with your actual API key
        $url = "https://maps.googleapis.com/maps/api/geocode/json?address=" . urlencode($city_name) . "&key={$apiKey}";

        // Send the request to the Google Maps API
        $response = \Illuminate\Support\Facades\Http::get($url);
        // Check if the response is successful
        if ($response->successful()) {
            $data = $response->json();

            if (!empty($data['results'])) {
                // Get the latitude and longitude from the first result
                $lat = $data['results'][0]['geometry']['location']['lat'];
                $lng = $data['results'][0]['geometry']['location']['lng'];

                // Store the result in the array for future reference (optional)
//                $cities[$city_id] = ['lat' => $lat, 'lon' => $lng];

                return [
                    $lat,
                    $lng,
                ];
            }
        }

        return [null, null]; // or handle the error as needed
    }

}
