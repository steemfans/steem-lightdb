<?php

namespace App\Service;

use Psr\Log\LoggerInterface;
use Doctrine\ORM\EntityManagerInterface;
use App\Service\ConfigManager;

class CustomJsonManager
{
    private $logger;
    private $em;
    private $config_manager;

    public function __construct(
                        LoggerInterface $logger,
                        EntityManagerInterface $em,
                        ConfigManager $config_manager
                    )
    {
        $this->logger = $logger;
        $this->em = $em;
        $this->config_manager = $config_manager;
    }

    public function opProcess($data)
    {
        var_dump('op_process_in_custom_json_service', $data);
    }
}
