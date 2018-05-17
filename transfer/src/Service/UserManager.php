<?php

namespace App\Service;

use Psr\Log\LoggerInterface;
use Doctrine\ORM\EntityManagerInterface;
use App\Service\ConfigManager;

class UserManager
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

    public function addUser($data)
    {
        var_dump('add_user_in_user_service', $data);
    }

    public function updateUser($data)
    {
        var_dump('update_user_in_user_service', $data);
    }
}
