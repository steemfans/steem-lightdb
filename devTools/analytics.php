<?php
    $servername = "steem-lightdb.com";
    $username = "steem";
    $password = "steem";
    $conn = new PDO("mysql:host=$servername;dbname=steemdb", $username, $password);

    $start = 4260000;
    $end = 4270000;
    $step = 100;
    $operation_type = [];

    exec('rm -f *.txt');

    while ($start <= $end) {
        $tmp_end = $start + $step;
        $sql = "select block_num, block_info from blocks where block_num >= {$start} and block_num <= {$tmp_end}";
        $sth = $conn->prepare($sql);
        $sth->execute();
        $res = $sth->fetchAll();
        print($start.'    '. $tmp_end."\n");

        foreach($res as $k => $v) {
            $tmp_info = json_decode($v['block_info'], true);
            if (isset($tmp_info['transaction_ids']) && count($tmp_info['transaction_ids'])) {
                $txids_length = count($tmp_info['transaction_ids']);
                $sql2 = "select id, block_num, content from transactions where block_num = {$v['block_num']} order by id asc";
                $sth2 = $conn->prepare($sql2);
                $sth2->execute();
                $trans = $sth2->fetchAll();
                if (count($trans) == $txids_length) {
                    foreach($trans as $kk => $vv) {
                        $tmp_content = json_decode($vv['content'], true);
                        if (count($tmp_content['operations']) > 0) {
                            foreach($tmp_content['operations'] as $kkk => $vvv) {
                                if (!in_array($vvv[0], $operation_type)) {
                                    print_r($vvv[0]."\n");
                                    array_push($operation_type, $vvv[0]);
                                    file_put_contents('type.txt', $vvv[0]."\n", FILE_APPEND);
                                }
                                file_put_contents($vvv[0].'.txt', json_encode($vvv, JSON_PRETTY_PRINT)."\n\n", FILE_APPEND);
                            }
                        }
                        // echo json_encode($tmp_content, JSON_PRETTY_PRINT)."\n";
                    }
                } else {
                    echo "lost block {$v['block_num']}\n";
                    exit();
                }
            }
        }

        $start = $tmp_end + 1;
    }
