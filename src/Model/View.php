<?php

namespace App\Model;

use Ramsey\Uuid\Uuid;

class View
{
    const EVENTS = [
        'view-user',
        'view-page',
        'view-project',
        'view-job',
    ];

    /**
     * @var string
     */
    private $id;
    /**
     * @var string
     */
    private $event;
    /**
     * @var string
     */
    private $resourceType;
    /**
     * @var int
     */
    private $resourceId;
    /**
     * @var int
     */
    private $unixTime;
    /**
     * @var int
     */
    private $userId;

    public function __construct(string $event, string $resourceType, int $resourceId, int $userId = null)
    {
        if (!$this->validEvent($event)) {
            throw new \DomainException('Event is not valid');
        }

        $this->id = Uuid::uuid4();
        $this->event = $event;
        $this->resourceType = $resourceType;
        $this->resourceId = $resourceId;
        $this->unixTime = round(microtime(true) * 1000);
        $this->userId = $userId;
    }

    private function validEvent(string $event): bool
    {
        return in_array($event, self::EVENTS, true);
    }

    public function toArray(): array
    {
        return [
            'uuid'       => $this->id,
            'event'      => $this->event,
            //    'userUuid'   => $this->['user-uuid'],
            'userId'     => $this->userId,
            'resourceId' => $this->resourceId,
            'resource'   => $this->resourceType,
            'unixTime'   => $this->unixTime,
        ];
    }
}
