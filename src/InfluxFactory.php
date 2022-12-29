<?php declare(strict_types=1);

use GuzzleHttp\Client as Guzzle;
use InfluxDB2\Client as InfluxDB;
use InfluxDB2\Model\WritePrecision;
use InfluxDB2\WriteApi;
use InfluxDB2\WriteType;
use Vatradar\Workers\Env;

class InfluxFactory
{
    private static InfluxDB $instance;
    private static array $config;

    public static function new(): InfluxDB
    {
        if(self::$instance instanceof InfluxDB) {
            return self::$instance;
        }

        self::$config = [
            'url' => Env::get('INFLUX_URL'),
            'token' => Env::get('INFLUX_TOKEN'),
            'org' => Env::get('INFLUX_ORG'),
            'precision' => WritePrecision::S,
            'httpClient' => new Guzzle()
        ];

        return self::$instance = new InfluxDB(self::$config);
    }

    public static function writer(bool $batch = false, int $batchSize = 100, array $tags = []): WriteApi
    {
        if(!(self::$instance instanceof InfluxDB)) {
            self::new();
        }

        if($batch) {
            $opts = ['writeType' => WriteType::BATCHING, 'batchSize' => $batchSize];
        } else {
            $opts = null;
        }

        return self::$instance->createWriteApi($opts, $tags);
    }
}
