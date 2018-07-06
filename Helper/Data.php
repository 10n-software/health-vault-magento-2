<?php

namespace TenN\VaultHealth\Helper;

use Magento\Framework\App\Helper\Context;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Psr\Log\LogLevel;
use TenN\VaultHealth\Model\Logger\Logger;

class Data extends \Magento\Framework\App\Helper\AbstractHelper
{
    const CONFIG_ENABLED = 'system/job_health/enabled';
    const CONFIG_TOKEN = 'system/job_health/token';
    const CONFIG_LOGGING_ENABLED = 'system/job_health/logging_enabled';
    const CONFIG_LOG_FILE = 'system/job_health/log_file';

    protected $timezone;
    protected $logger;

    public function __construct(
        Context $context,
        TimezoneInterface $timezone,
        Logger $logger
        )
    {
        parent::__construct($context);
        $this->timezone = $timezone;
        $this->logger = $logger;
    }


    public function isEnabled()
    {
        return $this->scopeConfig->isSetFlag(self::CONFIG_ENABLED);
    }

    public function isLoggingEnabled()
    {
        return $this->scopeConfig->isSetFlag(self::CONFIG_LOGGING_ENABLED);
    }

    public function getToken()
    {
        return $this->scopeConfig->getValue(self::CONFIG_TOKEN);
    }

    public function getConfigTimeZone()
    {
        return $this->timezone->getConfigTimezone();
    }

    public function log($message, $level = LogLevel::INFO)
    {
        if ($this->isLoggingEnabled()) {
            $this->logger->log($level, $message);
        }
    }

}
