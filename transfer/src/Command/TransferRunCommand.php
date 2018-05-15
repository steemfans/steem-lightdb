<?php

namespace App\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Psr\Log\LoggerInterface;

class TransferRunCommand extends Command
{
    protected static $defaultName = 'transfer:run';
    private $conn;
    private $logger;

    public function __construct(LoggerInterface $logger)
    {
        parent::__construct();
        $this->logger = $logger;
    }

    protected function configure()
    {
        $this
            ->setDescription('run the transfer shell')
            // ->addArgument('arg1', InputArgument::OPTIONAL, 'Argument description')
            ->addOption('start_num', null, InputOption::VALUE_OPTIONAL, 'Start block number', 1)
            ->addOption('sleep_time', null, InputOption::VALUE_OPTIONAL, 'sleep time', 3)
            ->addOption('step', null, InputOption::VALUE_OPTIONAL, 'step', 100)
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $start_num = $input->getOption('start_num');
        $sleep_time = $input->getOption('sleep_time');
        $step = $input->getOption('step');
        $debug = getenv('APP_ENV');

        // connect chain db
        if (!getenv('CHAIN_DB')) {
            $this->logger->error('CHAIN_DB undefined');
            exit();
        }
        $chain_db = $this->parseDBStr(getenv('CHAIN_DB'));
        try {
            $this->conn = new \PDO("{$chain_db[1]}:host={$chain_db[4]};dbname={$chain_db[6]}", $chain_db[2], $chain_db[3]);
        } catch (\Exception $e) {
            $this->logger->error(sprintf('DB error: %s', $e->getMessage()));
            exit();
        }

        $output->writeln('<info>StartNum: '.$start_num.'</info>');
        $this->logger->info('<info>StartNum: '.$start_num.'</info>');
        // main loop
        $last_block_num = $start_num;
        while (1) {
            if ($debug == 'prod') {
                $latest_block_num = $this->getLatestBlockNum();
            } else {
                $latest_block_num = getenv('test_latest_block_num');
                $latest_block_num = $latest_block_num ? $latest_block_num : 10;
            }

            if ($latest_block_num && ($latest_block_num - $last_block_num) > 0) {
                $output->writeln("<info>Get block from {$last_block_num} to {$latest_block_num}");
                $this->logger->info("<info>Get block from {$last_block_num} to {$latest_block_num}");
                $tmp_start = $last_block_num;
                while($tmp_start <= $latest_block_num) {
                    $tmp_end = $tmp_start + $step;
                    if ($tmp_end >= $latest_block_num) {
                        $tmp_data = $this->getDataFromChain($tmp_start, $latest_block_num+1, $output);
                    } else {
                        $tmp_data = $this->getDataFromChain($tmp_start, $tmp_end, $output);
                    }
                    $tmp_start = $tmp_end;
                    switch ($tmp_data['status']) {
                        case 1:
                            $this->transferData($tmp_data['data']);
                            break;
                        case -1:
                            $this->logger->error('[error]get blocks: start > end');
                            exit();
                            break;
                        case -2:
                            $this->logger->error('[error]get blocks: length error');
                            exit();
                            break;
                        case -3:
                            $this->logger->error('[error]get blocks: block data wrong', $tmp_data['data']);
                            exit();
                            break;
                        default:
                            $this->logger->error('undefined status');
                            exit();
                            break;
                    }
                }
            }
            if ($debug != 'prod') {
                $output->writeln('<info>Not Prod ENV</info>');
                exit();
            } else {
                sleep($sleep_time);
            }
        }
    }

    protected function transferData($data) {
        foreach($data as $k => $block) {
            $block_num = $block['block_num'];
            $block_info = $block['block_info'];
            foreach($block['transactions'] as $kk => $transaction) {
                if (isset($block_info['transaction_ids'][$kk])) {
                    $transaction_id = $block_info['transaction_ids'][$kk];
                } else {
                    $transaction_id = $kk;
                }
                foreach($transaction['content']['operations'] as $kkk => $operation) {
                    $operation_index = $kkk;
                    var_dump($block_num.' : '.$transaction_id.' : '.$operation[0]);
                    switch($operation[0]) {
                        case 'pow':
                            break;
                        case 'comment':
                            break;
                        case 'comment_options':
                            break;
                        case 'delete_comment':
                            break;
                        case 'vote':
                            break;
                        case 'custom_json':
                            break;
                        case 'account_create':
                            break;
                        case 'account_update':
                            break;
                        case 'limit_order_cancel':
                            break;
                        case 'limit_order_create':
                            break;
                        case 'pow2':
                            break;
                        case 'transfer':
                            break;
                        case 'transfer_from_savings':
                            break;
                        case 'transfer_to_savings':
                            break;
                        case 'transfer_to_vesting':
                            break;
                        case 'convert':
                            break;
                        case 'withdraw_vesting':
                            break;
                        case 'witness_update':
                            break;
                        case 'feed_publish':
                            break;
                        case 'account_witness_vote':
                            break;
                    }
                }
            }
        }
    }
    
    protected function parseDBStr($str)
    {
        preg_match('/([A-Za-z]+):\/\/([A-Za-z]+):(.+)@(.+):([0-9]+)\/([A-Za-z]+)/', $str, $matches);
        return $matches;
    }

    /**
     * [$start, $end)
     */
    protected function getDataFromChain($start, $end, OutputInterface $output)
    {
        if ($start >= $end) {
            return ['status' => -1];
        }
        $output->writeln("<info>Will get blocks: [{$start}, {$end})</info>");
        $sql = "select * from blocks where block_num >= {$start} and block_num < {$end} order by block_num asc";
        $sth = $this->conn->prepare($sql);
        $sth->execute();
        $res = $sth->fetchAll(\PDO::FETCH_ASSOC);
        // var_dump($res);die();
        $length = $end - $start;
        if (count($res) < $length) {
            return ['status' => -2];
        }
        
        $tmp = $this->getBlocks([$start-1]);
        $last_block_id = $tmp ? $tmp[0]['block_id'] : '0000000000000000000000000000000000000000';

        $final = [];
        foreach($res as $k => $v) {
            if ($v['previous'] == $last_block_id) {
                $v['block_info'] = json_decode($v['block_info'], true);
                if (count($v['block_info']['transaction_ids']) > 0) {
                    $v['transactions'] = $this->getTransactions($v['block_num']);
                } else {
                    $v['transactions'] = [];
                }
                $final[$v['block_num']] = $v;
                $last_block_id = $v['block_id'];
            } else {
                return ['status' => -3, 'data' => [$v, $last_block_id]];
            }
        }
        return ['status' => 1, 'data' => $final];
    }

    protected function getBlocks($block_nums)
    {
        if (!is_array($block_nums) || count($block_nums) == 0) {
            return false;
        }
        $block_nums_str = implode(',', $block_nums);
        $sql = "select * from blocks where block_num in ({$block_nums_str}) order by block_num asc";
        $sth = $this->conn->prepare($sql);
        $sth->execute();
        $res = $sth->fetchAll(\PDO::FETCH_ASSOC);
        if (count($res) == 0) {
            return false;
        } else {
            return $res;
        }
    }

    protected function getTransactions($block_num)
    {
        $sql = "select * from transactions where block_num = {$block_num} order by id asc";
        $sth = $this->conn->prepare($sql);
        $sth->execute();
        $res = $sth->fetchAll(\PDO::FETCH_ASSOC);
        if (count($block_num) > 0) {
            foreach($res as $k => $v) {
                $res[$k]['content'] = json_decode($v['content'], true);
            }
            return $res;
        } else {
            return false;
        }
    }

    protected function getLatestBlockNum()
    {
        $sql = "select block_num from blocks order by block_num desc limit 1";
        $sth = $this->conn->prepare($sql);
        $sth->execute();
        $res = $sth->fetchAll(\PDO::FETCH_ASSOC);
        if ($res) {
            return $res[0]['block_num'];
        } else {
            return false;
        }
    }
}
