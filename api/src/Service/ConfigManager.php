<?php

namespace App\Service;

use Psr\Log\LoggerInterface;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\Config;

class ConfigManager
{
    private $logger;
    private $em;

    public function __construct(
                        LoggerInterface $logger,
                        EntityManagerInterface $em
                    )
    {
        $this->logger = $logger;
        $this->em = $em;
    }

    public function setConfig($param, $val=null)
    {
        $config = $this->getConfig($param, true);
        if ($config) {
            $config->setVal($val);
        } else {
            $config = new Config();
            $config->setParam($param);
            $config->setVal($val);
        }
        $this->em->persist($config);
        $this->em->flush();
    }

    public function getConfig($param, $need_obj)
    {
        $result = $this->em
                    ->getRepository(Config::class)
                    ->findOneBy([
                        'param' => $param,
                    ]);

        if ($result) {
            if ($need_obj) {
                return $result;
            } else {
                return $result->getVal();
            }
        } else {
            return null;
        }
    }
}
