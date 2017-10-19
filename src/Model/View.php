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
     * @var string
     */
    private $userUuid;

    /**
     * @var int
     */
    private $unixTime;

    /**
     * @var int
     */
    private $userId;

    public function __construct(string $event, int $resourceId, string $userUuid, int $userId = null)
    {
        if (!$this->validEvent($event)) {
            throw new \DomainException('Event is not valid');
        }

        $this->id           = Uuid::uuid4();
        $this->event        = $event;
        $this->resourceType = (string)explode('-', $event)[1];;
        $this->resourceId   = $resourceId;
        $this->userUuid     = $userUuid;
        $this->unixTime     = round(microtime(true) * 1000);
        $this->userId       = $userId;
    }

    private function validEvent(string $event): bool
    {
        return in_array($event, self::EVENTS, true);
    }

    public function toArray(): array
    {
        return [
            'id'           => (string)$this->id,
            'event'        => $this->event,
            'resourceType' => $this->resourceType,
            'resourceId'   => $this->resourceId,
            'userUuid'     => $this->userUuid,
            'unixTime'     => $this->unixTime,
            'userId'       => $this->userId,
        ];
    }
}
