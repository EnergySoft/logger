<?php 

    include 'class.php';

    $logger = new PhpLogger;
    $logger->echo = true;

    $logger->create_log_file('test');

    $fnames = Array('Billy','James','Steward','Charles','Leo', 'Richard', 'Frank',
                    'Mary','Misty','Eva', 'Helga', 'Judith', 'Linda','Mica','Bobby');
    $snames = Array('Koleman','Stotch','Simpson','Slams','Snow','Smith','Wolf','Loan','Potter','McMillan');

    $cityes = Array('New Yourk', 'San-Francisco', 'Dallas');

    $array1 = Array();

    for($i = 0; $i < 30; $i++){

        $array1[] = Array(
            'name'=>rand_elem($fnames).' '.rand_elem($snames),
            'city'=>rand_elem($cityes),
            'age'=>rand(15,25),
            'calls'=>rand(100,400),
            'last_call'=>'2019-'.str_pad(rand(1,12), 2, '0', STR_PAD_LEFT).'-'.str_pad(rand(1,26), 2, '0', STR_PAD_LEFT)
        );

    }

    $logger->dump_table($array1, Array('group_by'=>'city'));


    function rand_elem($arr){
        return $arr[rand(0, count($arr)-1)];
    }