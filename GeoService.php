<?php

declare(strict_types=1);

namespace App\Services;

use Carbon\Carbon;
use GuzzleHttp\Client;

class GeoService
{
    const BASE_URL = 'http://geo.secret-url.com';
    const SESSION_ZIP_KEY = 'zip_by_ip';

    /**
     * @var string
     */
    private $apiKey;

    /**
     * GeoService constructor.
     */
    public function __construct()
    {
        $this->apiKey = config('services.secret-url.apiKey');
    }

    /**
     * @param string $ip
     *
     * @return string
     */
    public function getZipByIp(string $ip): string
    {
        if (! config('services.secret-url.enable', false)) {
            return '';
        }

        $zip = session()->get(self::SESSION_ZIP_KEY);
        if (! is_null($zip)) {
            return (string) $zip;
        }

        $zip = $this->getZipFromApi($ip);
        session()->put(self::SESSION_ZIP_KEY, $zip);

        return $zip;
    }

    /**
     * Fetch user ZIP by IP from.
     *
     * @param string $ip
     *
     * @return string
     */
    private function getZipFromApi(string $ip): string
    {
        return \Cache::remember('geo-ip-'.$ip, Carbon::now()->addHours(1), function () use ($ip) {
            try {
                $client = new Client();
                $res = $client->get(self::BASE_URL, [
                    'query' => [
                        'ip' => $ip,
                        'apiKey' => $this->apiKey,
                    ],
                ]);

                $json = (string) $res->getBody();
                $jsonData = json_decode($json, true);

                return (string) ($jsonData['zip'] ?? '');
            } catch (\Exception $e) {
                \Log::error($e);
            }

            return '';
        });
    }
}
