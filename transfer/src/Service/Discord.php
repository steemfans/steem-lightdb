<?php

namespace App\Service;

use Psr\Log\LoggerInterface;
use Doctrine\ORM\EntityManagerInterface;
use GuzzleHttp\Client;

class Discord 
{
    private $logger;
    private $webhook;

    public function __construct(
                        LoggerInterface $logger
                    )
    {
        $this->logger = $logger;
        $this->webhook = getenv('DISCORD');
    }

    public function notify($type, $msg)
    {
        if ($this->webhook) {
            try {
                $client = new Client();
                switch ($type) {
                    case 'warning':
                        $type = '[warning] ';
                        break;
                    case 'error':
                        $type = '[error] ';
                        break;
                    case 'info':
                        $type = '[info] ';
                        break;
                    default:
                        $type = '';
                        break;
                }
                $r = $client->request('POST', $this->webhook, [
                    'form_params' => [
                        'content' => $type.$msg,
                    ],
                ]);
                $body = $r->getBody();
                $result = $body->getContents();
                $this->logger->info('send_notify: '.json_encode($result));
            } catch (Exception $e) {
                $this->logger->error('notify_error: '.$e->getMessage());
            }
        }
    }
}
