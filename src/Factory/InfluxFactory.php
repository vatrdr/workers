<?php declare(strict_types=1);

namespace Workers\Factory;

use GuzzleHttp\Client as Guzzle;
use InfluxDB2\Client as InfluxDB;
use InfluxDB2\Model\WritePrecision;
use InfluxDB2\WriteApi;
use InfluxDB2\WriteType;
use Workers\Env;

class InfluxFactory
{
    private static InfluxDB $instance;
    /** @var array <string, mixed> */
    private static array $config;

    /**
     * @param bool $batch
     * @param int  $batchSize
     * @return WriteApi
     */
    public static function getWriter(bool $batch = false, int $batchSize = 250): WriteApi {
        if($batch) {
            $opts = ['writeType' => WriteType::BATCHING, 'batchSize' => $batchSize];
        } else {
            $opts = null;
        }

        return self::getClient()->createWriteApi($opts, []);
    }

    /**
     * @return InfluxDB
     */
    public static function getClient(): InfluxDB {
        if(isset(self::$instance)) {
            return self::$instance;
        }

        self::$config = [
            'url'        => Env::get('INFLUX_URL'),
            'token'      => Env::get('INFLUX_TOKEN'),
            'org'        => Env::get('INFLUX_ORG'),
            'precision'  => WritePrecision::S,
            'httpClient' => new Guzzle(['http_errors' => false]),
        ];

        self::$instance = new InfluxDB(self::$config);

        return self::$instance;
    }
}
