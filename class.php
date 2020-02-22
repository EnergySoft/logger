<?php

class PhpLoggerCondition{



    function  __construct(){

    }

}

class PhpLogger
{

    public $log_on = true;

    public $log_path = './logs';
    public $log_folder = '';
    public $log_name = 'log_';
    public $file_name = '';
    public $echo = false;

    public $southants_sep = ',';

    public $format_numbers = true;
    public $output_southants_sep = ' ';
    public $output_decimals_sep = '.';
    public $output_decimals = 2;

    private $old_timestamp = 0;
    private $old_timestamps = Array();

    private $groups = Array();
    private $buffer_output = false;
    private $buffer = '';


    public function create_new_log_file(){
        $this->file_name = $this->log_name.'_'.date('Y-m-d_H-i-s').'.html';
        $this->print($this->get_file_header());
        $this->label('Generation start: '.date('Y-m-d H:i:s'),'#e6e6e6','#7e87ab');
    }

    public function create_log_file($file_name = ''){

        if($file_name != ''){
            $this->file_name = $file_name;
        }

        $this->file_name = $this->log_name.'.html';
        if(file_exists($this->log_path.'/'.$this->file_name)){
            unlink($this->log_path.'/'.$this->file_name);
        }
        $this->print($this->get_file_header());
        $this->label('Generation start: '.date('Y-m-d H:i:s'),'#e6e6e6','#7e87ab');
    }

    //////////////////////////////////////////////// LABEL

    public function label($text, $color = '#000', $background = '#EEE'){
        $html = "<div class='block label' style='color:".$color.";background:".$background."'>";
        $html .= $text;
        $html .= "</div>";
        $this->print($html);
    }

    //////////////////////////////////////////////// DUMPS

    public function dump_auto($var){
        $html = "<div class='block'>";
        $html .= $this->write_var($var);
        $html .= "</div>";
        $this->print($html);
    }

    public function dump($data, $pre = true){

        ob_start();
        var_dump($data);
        $result = ob_get_clean();

        $html = "<div class='block dump'>";
        if($pre) $html.="<pre>";
        $html .= $result;
        if($pre) $html .="</pre>";
        $html = "</div>";
        $this->print($html);

    }


    public function dump_object($data, $extended = false){

        $html ="<div class='block object'>";
        $html.="<div class='object_header'>#".get_class($data)."
                <i class='fa fa-plus-square-o' aria-hidden='true'></i>
                <i class='fa fa-minus-square-o' aria-hidden='true'></i>
            </div>";
        $html.="<div class='block object_content'>";

        if(is_object($data)){

            $html.="<table class='table_object'>";
            foreach($data as $key => $value){
                $html.="<tr>";
                $html.="<td class='noborder'>".$key.":</td>";
                $html.="<td  class='noborder'>";
                $html.=$this->write_var($value);
                $html.="</td>";
                $html.="</tr>";
            }
            $html.="</table>";

        }

        $html.="</div>";
        $html.="</div>";

        $this->print($html);

    }

    public function dump_table($data, $params = false){

        //For object or array of object

        if(!is_array($data)){
            //echo "[X]";
            $this->write_var($data);
            return 0;
        } else {

            //echo "[Y]";
            if(count($data)>0){

                //echo "[".count($data)."]";

                $key = false;
                foreach ($data as $key => $value) {
                    break;
                }

                //echo "[".$key."]";

                if($key!==false){
                    if(!is_array($data[$key]) && !is_object($data[$key])){
                        $this->dump_simple_array($data);
                        return 0;
                    }
                } else{
                    return 0;
                }

            } else {
                $this->write_var($data);
                return 0;
            }
            
        }
        
        $headers = false;
        $headers_aliases = false;
        $totals = Array();
        $totals_total = Array();

        $start_row = 0;
        $group_by = false;
        $group_by_old_value = false;

        if(!isset($params['csv'])){
            $params['csv'] = false;
        }

        if(isset($params['group_by']) && $params['group_by'] !== false){
            $group_by = $params['group_by'];
        }

        if($params['csv']){
            $start_row = 1;
        }

        if(isset($params['headers'])){
            $headers = $params['headers'];
        }

        if(!$headers){

            $headers = Array();
        
            foreach($data as $key=>$obj){

                foreach($obj as $objkey=>$value){
                    if(!in_array($objkey, $headers)){
                        $headers[] = $objkey;
                    }
                }

                if($params['csv']){
                    break;
                }

            }


        }

        

        if($group_by !== false){

            $temp = Array();

            foreach($data as $key=>$array){

                if(!isset($temp[$array[$group_by]])){
                    $temp[$array[$group_by]] = Array();
                }

                $temp[$array[$group_by]][] = $array;

            }

            $new_array = Array();

            foreach($temp as $key=>$array){
                foreach($array as $a){
                    $new_array[] = $a;
                }
            }

            $data = $new_array;

            if(isset($data[$start_row][$group_by])){
                $group_by_old_value = $data[$start_row][$group_by];
            } else {
                $group_by_old_value = null;
            }
            
        }


        foreach($data as $i=>$value){

            if(is_object($data[$i])){
                $data[$i] = (array)$data[$i];
            }

            foreach($headers as $h){

                $d = $data[$i][$h];

                if(is_numeric(str_replace($this->southants_sep,'',$d))){
                    $d = str_replace($this->southants_sep,'',$d);
                }

                if(isset($data[$i][$h])){   
                    $this->table_process_totals($totals_total,$h,$d);
                }

            }

        }


        $html = "<div class='block'>";

        $html .= "<table>";

            $html.="<thead>";
            
                $html.="<tr>";
                    $html.="<td>#</td>";

                    if($group_by){
                        $html.="<td>#</td>";
                    }

                    foreach($headers as $h){
                        $html.="<th>".$h."</th>";
                    }

                $html.="</tr>";

            $html.="</thead>";

        $html.="<tbody>";

        $n = 0;
        $c = 0;
        $cycle = 0;

        $min_rows_to_hide = 10;

        foreach($data as $i=>$value){

            if(is_object($data[$i])){
                $data[$i] = (array)$data[$i];
            }

            $n++;
            $c++;

            $html.="<tr class='".($c>$min_rows_to_hide?'row_hidden':'')." tr_cycle_".$cycle ."'>";

            $html.="<td class='tablenum'>".$n."</td>";
            if($group_by){
                $html.="<td class='tablenum'>".$c."</td>";
            }

            foreach($headers as $h){

                $html.="<td>";

                if(isset($data[$i][$h])){

                    $d = $data[$i][$h];

                    if(is_numeric(str_replace($this->southants_sep,'',$d))){
                        $d = str_replace($this->southants_sep,'',$d);
                    }

                    $this->table_process_totals($totals,$h,$d);
                    
                    $html.= $this->write_var($d);

                }

                $html.="</td>";

            }

            $html.="</tr>";

            if($group_by !== false){

                if(isset($data[$i+1][$group_by])){
                    $group_by_new_value = $data[$i+1][$group_by];
                } else {
                    $group_by_new_value = null;
                }

                if($group_by_new_value != $group_by_old_value || $i + 1 >= count($data)){


                    if($c > $min_rows_to_hide){
                        $html.= $this->table_get_table_ext($cycle, $c);
                    }

                    $c = 0;
                    $cycle++;

                    $html.=$this->table_get_table_footer($totals, $headers, $group_by);
    
                    $totals = Array();
    
                }

                $group_by_old_value = $group_by_new_value;

            }

        }


        if($c > $min_rows_to_hide){
            $html.= $this->table_get_table_ext($cycle, $c);
        }

        $html.="</tbody>";

        $html.="<tfoot>";
        $html.=$this->table_get_table_footer($totals_total, $headers, $group_by);
        $html.="</tfoot>";

        $html.="</table>";

        $html.="</div>";

        $this->print($html);

    }

    private function table_process_totals(&$totals,$h,$d){

        if(is_numeric($d)){

            $d = $d*1;

            if(!isset($totals[$h])){
                $totals[$h] = Array('amount'=>0, 'sum'=>0, 'min'=>null, 'max'=>null, 'avg'=>0);
            }

            $totals[$h]['amount']++;

            $totals[$h]['sum'] += $d;
            $totals[$h]['avg'] = $totals[$h]['sum'] / $totals[$h]['amount'];

            if($totals[$h]['min'] == null || $totals[$h]['min'] > $d){
                $totals[$h]['min'] = $d;
            }

            if($totals[$h]['max'] == null || $totals[$h]['max'] < $d){
                $totals[$h]['max'] = $d;
            }

        }

    }

    private function table_get_table_ext($cycle, $total){
        $html="<tr class='tr_extend' id='tr_ex_cycle_".$cycle."'><td colspan='999' class='extend_td'><span onclick='extend_table(".$cycle.");'>extend (".$total.")</span></td></tr>";
        return $html;
    }

    private function table_get_table_footer($totals_total, $headers, $group_by){

        $html = "";

        //$html.="<tfoot>";

            $params = Array('sum','avg','min','max');

            $i = 0;

            foreach($params as $param){

                $i++;

                $html.="<tr class='tr_tfoot ".(($i == count($params))?'tr_summary_last':'')."'>";

                $html.="<td ".($group_by?'colspan=2':'').">".$param."</td>";

                foreach($headers as $h){
                    
                    $html.="<th>";
                    if(isset($totals_total[$h]) && isset($totals_total[$h][$param])){
                        $html.= $this->write_var($totals_total[$h][$param]);
                    }
                    $html.="</th>";

                }

                $html.="</tr>";

            }

        //$html.="</tfoot>";

        return $html;

    }

    public function dump_simple_array($array, $detailed = true, $pre = false){

        $row1 = "";
        $row2 = "";

        foreach($array as $key=>$value){

            $row1.="<th>".$key."</th>";

            $row2.="<td>";
            $row2.=$this->write_var($value);
            $row2.="</td>";

        }

        $html = "<table>";

        $html .= "<thead><tr>";
        $html .= $row1;
        $html .= "</tr></thead>";

        $html .= "<tbody><tr>";
        $html .= $row2;
        $html .= "</tr></tbody>";

        $html .= "</table>";

        $html = "<div class='scrollblock'>".$html."</div>";

        $this->print($html);

    }

    /////////////////////////////////////////////// TIMESTAMP

    public function log_timestamp($comment = '',$name = ''){

        $time_text = date('Y-m-d H:i:s');
        $timestamp = $this->microtime_float();
        $time_diff = 0;

        if($name == ''){

            if($this->old_timestamp == 0){
                $this->old_timestamp = $timestamp;
            }
            $time_diff = $timestamp - $this->old_timestamp;
            $this->old_timestamp = $timestamp;

        } else {

            if(!isset($this->old_timestamps[$name])){
                $this->old_timestamps[$name] = $timestamp;
            }

            $time_diff = $timestamp - $this->old_timestamps[$name];
            $this->old_timestamps[$name] = $timestamp;

        }

        $html = "
            <div class='block timestamp'>
                <i class='fa fa-clock-o'></i>
                <span class='timestamp_time'>[".$time_text."] [".$this->write_ms($time_diff)."]</span>
                <span class='timestamp_comment'><b>".$comment."</b></span>
            </div>
            ";

        $this->print($html);

    }

    /////////////////////////////////////////////// ERRORS

    public function error($text = 'error', $code = ''){

        if($code!=''){
            $code = '<b>#'.$code.':</b> ';
        }

        $this->print_block('error','fa fa-exclamation-circle' ,$code.$text);

    }

    public function warning($text = 'warning'){

        $this->print_block('warning','fa fa-exclamation-circle', $text);

    }

    public function info($text = 'info'){

        $this->print_block('info','fa fa-info-circle',$text);

    }

    private function print_block($type,$icon,$text){

        $html = "<div class='block infoblock block-".$type."'>";
        $html.= "<div><i class='".$icon."'></i></div>";
        $html.= $text;
        $html.= "</div>";
        $this->print($html);

    }

    /////////////////////////////////////////////// GROUPS

    public function open_group($name, $timestamp = false){

        $this->groups[] = $name;
        if($timestamp){
            $this->log_timestamp('Group '.$name.' start','group_'.$name);
        }
        $html = "<div class='block group'>
                        <div class='group_header'>
                            <i class='fa fa-plus-square-o' aria-hidden='true'></i>
                            <i class='fa fa-minus-square-o' aria-hidden='true'></i>
                            <span class='group_name'>GROUP: <b>".$name."</b></span>
                        </div>
                        <div class='group_content'>";
        $this->print($html);

    }

    public function close_group($timestamp = false){

        $name = array_pop($this->groups);
        $html = "</div></div>";
        $this->print($html);
        if($timestamp){
            $this->log_timestamp('Group '.$name.' end','group_'.$name);
        }

    }

    /////////////////////////////////////////////// WRITERS

    private function write_ms($ms){
        if($ms < 1000) return $ms.' ms';
        if($ms > 1000) return round($ms/1000,3).' s';
    }

    public function write_var($var){

        if(is_object($var)){
            $this->buffer_output = true;
            $this->dump_object($var);
            return $this->buffer;
        }

        if(is_array($var)){
            $this->buffer_output = true;

            $table = false;

            foreach($var as $key=>$value){
                if(is_object($value) || is_array($value)){
                    $table = true;
                }    
            break;
            }

            if($table){
                $this->dump_table($var);
            } else {
                $this->dump_simple_array($var);
            }
            return $this->buffer;
        }

        if(is_null($var)){
            return "<span class='span_var span_null'>null</span>";
        }

        if(is_bool($var)){
            if($var){
                return "<span class='span_var span_bool bool_true'>true</span>";
            } else {
                return "<span class='span_var span_bool bool_false'>false</span>";
            }
        }

        if(is_string($var)){
            return "<span class='span_var span_string'>\"".$var."\"</span>";
        }

        if(is_numeric($var)){
            if($this->format_numbers){
                if(is_float($var)){
                    return "<span style='white-space:nowrap'>".number_format($var, $this->output_decimals, $this->output_decimals_sep,$this->output_southants_sep)."</span>";
                } 
                if(is_integer($var)){
                    return number_format($var, 0, $this->output_decimals_sep,$this->output_southants_sep);
                }
            }
        }

        return $var;

    }

    /////////////////////////////////////////////// PRIVATE

    private function get_table_html($object){

    }

    private function microtime_float()
    {
        return round(microtime(true) * 1000);
    }

    private function print($text){

        if($this->log_on){

            if(!$this->buffer_output){

                if($this->echo){
                    echo $text;
                } else {
                    file_put_contents($this->log_path.'/'.$this->file_name, $text, FILE_APPEND);
                }

            } else {
                $this->buffer = $text;
                $this->buffer_output = false;
            }            

        }

    }

    private function get_file_header(){

        $text_color = '#000';
        $border_color = '#DDD';
        $border_table_color = '#444';
        $border_radius = '5px';

        $html = "
                <html>
                    <head> 
                    <script
                        src='https://code.jquery.com/jquery-3.4.1.min.js' 
                        integrity='sha256-CSXorXvZcTkaix6Yvo6HppcZGetbYMGWSFlBw8HfCJo='
                        crossorigin='anonymous'></script>
                        <link rel='stylesheet' href='https://stackpath.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css'>
                        <script src='/res/script.js'></script>
                        <link rel='stylesheet' href='/res/style.css'>
                    </head>
                    <body>

                    <div style='padding:5px;background:#EEE;'><button id='ext_all_groups'>EXTEND ALL GROUPS</button></div>

                    <script>

                    </script>

                    <style>

                    </style>

            ";

        return $html;

    }

    private function get_file_footer(){
        $html = "
                </body>
                </html>
            ";
        return $html;
    }

}