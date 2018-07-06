<?php


namespace TenN\VaultHealth\Model;

use Magento\Cron\Model\ConfigInterface;
use Magento\Cron\Model\Schedule;
use Magento\Cron\Model\ScheduleFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\HTTP\Client\Curl;
use Psr\Log\LogLevel;
use TenN\VaultHealth\Helper\Data;

class Client implements ClientInterface
{

    protected $helper;
    protected $scheduleFactory;
    protected $httpClient;
    protected $config;
    protected $jobs;

    public function __construct(
        Data $helper,
        ScheduleFactory $scheduleFactory,
        ConfigInterface $config,
        Curl $httpClient
    )
    {
        $this->helper = $helper;
        $this->scheduleFactory = $scheduleFactory;
        $this->httpClient = $httpClient;
        $this->config = $config;
    }

    public function sendExecutionEvent(Schedule $schedule, $elapsed, $status = null)
    {
        if ($this->helper->isEnabled()) {
            if (!$this->helper->getToken()) {
                $this->helper->log('Missing token from system configuration');
                return;
            }
            $startTime = microtime(true);
            $url = $this->buildUrl($schedule, $elapsed, $status);
            if ($schedule->getMessages()) {
                $this->helper->log('Sending POST with job payload request to ' . $url);
                $this->httpClient->post($url, $schedule->getMessages());
                $result = $this->httpClient->getBody();
                $this->helper->log('Result: ' . $result . $this->getElapsedMessage($startTime), LogLevel::NOTICE);
                return;
            }
            $this->helper->log('Sending GET request to ' . $url);
            $this->httpClient->get($url);
            $result = $this->httpClient->getBody();
            $this->helper->log('Result: ' . $result . $this->getElapsedMessage($startTime), LogLevel::NOTICE);
        }
    }

    protected function getElapsedMessage($startTime)
    {
        return sprintf(' in %.03f seconds', (microtime(true) - $startTime));
    }

    public function getCronExpression($jobCode)
    {
        if (!$this->jobs) {
            $this->jobs = [];
            $groups = $this->config->getJobs();
            foreach ($groups as $jobs) {
                foreach ($jobs as $job => $config) {
                    if (!empty($config['schedule'])) {
                        $this->jobs[$job] = $config['schedule'];
                    }
                }
            }
        }
        return empty($this->jobs[$jobCode])?null:$this->jobs[$jobCode];
    }

    public function buildUrl(Schedule $schedule, $elapsed, $status = null)
    {
        if ($schedule->getStatus() != Schedule::STATUS_SUCCESS) {
            $status = 'failed';
        }
        $params = [
            'elapsed' => $elapsed,
            'tz' => $this->helper->getConfigTimeZone()
        ];
        if ($status) {
            $params['status'] = $status;
        }
        $cronExpr = $this->getCronExpression($schedule->getJobCode());
        $nextRun = $this->getNextRun($schedule);
        if ($nextRun) {
            $params['next_run'] = (int)$nextRun->format('U');
        } else if (!empty($cronExpr)) {
            $params['cron'] = $cronExpr;
        }

        $url = 'https://vh.10n-software.com/i/'
            . $this->helper->getToken() . '/'
            . $schedule->getJobCode() . '?'
            . http_build_query($params);
        return $url;
    }

    public function getNextRun(Schedule $schedule)
    {
        $collection = $this->scheduleFactory->create()->getCollection();
        $collection->addFieldToFilter('job_code', $schedule->getJobCode());
        $collection->addFieldToFilter('status', Schedule::STATUS_PENDING);
        $collection->setOrder('scheduled_at', 'ASC');
        $collection->setPageSize(1);
        $nextSchedule = $collection->getFirstItem();
        if ($nextSchedule instanceof Schedule && $nextSchedule->getId()) {
            return new \DateTime($nextSchedule->getScheduledAt());
        }
        return null;
    }

}
