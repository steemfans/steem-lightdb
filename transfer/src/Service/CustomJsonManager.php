<?php

namespace App\Service;

use Psr\Log\LoggerInterface;
use Doctrine\ORM\EntityManagerInterface;
use App\Service\ConfigManager;

use App\Service\UserManager;

class CustomJsonManager
{
    private $logger;
    private $em;
    private $config_manager;
    private $user_manager;

    public function __construct(
                        LoggerInterface $logger,
                        EntityManagerInterface $em,
                        ConfigManager $config_manager,
                        UserManager $user_manager
                    )
    {
        $this->logger = $logger;
        $this->em = $em;
        $this->config_manager = $config_manager;
        $this->user_manager = $user_manager;
    }

    public function handle($data)
    {
        var_dump('op_process_in_custom_json_service', $data);
        extract($data);
        $operation_type = $operation[1]['id'];
        switch ($operation_type) {
            case 'follow':
                $json = $operation[1]['json'];
                if ($json) {
                    $json = json_decode($json, true);
                } else {
                    $tmp_data = compact('block_num', 'transaction_id');
                    $msg = 'custom_json_follow_json_empty:'.json_encode($tmp_data);
                    $this->logger->error($msg);
                    return;
                }
                $this->user_manager->addFollowing(
                                        $json[1]['follower'],
                                        $json[1]['following'],
                                        $block_num,
                                        $transaction_id
                                    );
                break;
            default:
                $msg = 'unknown custom_json type: '.$operation_type;
                $this->logger->info($msg);
                break;
        }
        return;
    }
}
