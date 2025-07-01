<?php

namespace App\Service;

use App\Api\ShoperConnectionApi;
use App\Exception\ApiException;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

class AvailabilityService
{

    private $shoperApi;
    private $loggerService;
    private $availabilitiesCached = [];

    public function __construct(ShoperConnectionApi $shoperApi, LoggerService $loggerService)
    {
        $this->shoperApi = $shoperApi;
        $this->loggerService = $loggerService;
    }

    /**
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws ClientExceptionInterface
     * @throws ApiException
     */
    public function getAvailabilities(): array
    {
        $availabilities = [];

        if(!empty($this->availabilitiesCached)) {
            return $this->availabilitiesCached;
        }

        try {
            $data = $this->shoperApi->fetchAllPages('availabilities');
            if($data) {
                foreach ($data as $item) {
                    $availabilities[] = $this->buildAvailability($item);
                }
            }
            $this->availabilitiesCached = $availabilities;

        } catch (\Exception $e) {
            $this->loggerService->getApiLogger()->error('Failed to get availabilities',[
                'message' => $e->getMessage(),
                'code' => $e->getCode(),
            ]);
            throw new ApiException('Product statuses not downloaded');
        }
        return $availabilities;
    }

    /**
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws ClientExceptionInterface
     * @throws ApiException
     */
    public function getAvailabilityById(int $availabilityId): array
    {
        $availabilities = $this->getAvailabilities();
        foreach ($availabilities as $availability) {
            if(intval($availability['id']) === $availabilityId) {
                return $availability;
            }
        }
        return [];
    }

    public function buildAvailability($availability): ?array
    {
        if(is_array($availability) && !empty($availability)){
            return [
                'id' => $availability['availability_id'],
                'name' => $availability['translations']['pl_PL']['name'],
            ];
        }
        return null;

    }

}