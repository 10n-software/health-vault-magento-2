<?php


namespace TenN\VaultHealth\Model;

use Magento\Cron\Model\Schedule;

interface ClientInterface
{

    public function sendExecutionEvent(Schedule $schedule, $elapsed);

}
