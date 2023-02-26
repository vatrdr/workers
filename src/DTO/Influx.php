<?php declare(strict_types=1);

namespace Vatradar\Dataobjects\DTO;

/**
 * Data transfer object for InfluxDB Point data
 *
 * Metadata contains the bucket name for the point data as well
 * as whether this should be transmitted via batch mode
 * (defaults to true).
 *
 * Data is an array of InfluxDB Point data classes (InfluxDB2\Point)
 */
class Influx implements DTOInterface
{
    private bool $batch;
    /** @var array <InfluxDB2\Point> */
    private array  $data;
    private string $bucket;
    private string $ts;

    public function __construct(array $data, string|int $ts, string $bucket, bool $batch = true) {
        $this->data   = $data;
        $this->bucket = $bucket;
        $this->batch  = $batch;
        $this->ts     = (string) $ts;
    }

    public function getTimestamp(): string {
        return $this->ts;
    }

    public function getMetadata(): array|object {
        return [
            'bucket' => $this->bucket,
            'batch'  => $this->batch,
        ];
    }

    public function getData(): mixed {
        return $this->data;
    }
}
