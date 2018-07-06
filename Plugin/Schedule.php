<?php

namespace TenN\VaultHealth\Plugin;

use TenN\VaultHealth\Model\ClientInterface;

class Schedule
{

    protected $client;
    protected $startTime;
    protected $jobStatus;
    protected $ignoredStatuses = [
      \Magento\Cron\Model\Schedule::STATUS_PENDING,
      \Magento\Cron\Model\Schedule::STATUS_RUNNING
    ];

    public function __construct(
        ClientInterface $client
    )
    {
        $this->client = $client;
    }

    public function afterTryLockJob(\Magento\Cron\Model\Schedule $schedule, $result)
    {
        if ($result === true && $schedule->getStatus() === \Magento\Cron\Model\Schedule::STATUS_RUNNING) {
            $this->startTime = microtime(true);
        }
        return $result;
    }

    public function afterSave(\Magento\Cron\Model\Schedule $subject, $result)
    {
        if ($this->startTime && !in_array($subject->getStatus(), $this->ignoredStatuses)) {
            $elapsed = intval((microtime(true) - $this->startTime) * 1000);
            $this->client->sendExecutionEvent($subject, $elapsed);
            $this->startTime = null;
        }
        return $result;
    }

}
