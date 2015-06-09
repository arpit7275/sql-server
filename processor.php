<?php
require_once 'Console/Table.php';
require_once 'syntax_checker.php';

function process($tokens) {
    $prev_category = "";
    $token_category = "";
    $skip_next = 0;
    $out = false;
    global $final_output;
    global $star;
    $tokenCount = count($tokens);
    for ($tokenNumber = 0; $tokenNumber < $tokenCount; ++$tokenNumber) {
        $token = $tokens[$tokenNumber];
        $trim = trim($token); // this removes also \n and \t!

        $upper = strtoupper($trim);
        
        switch ($upper) {
            case 'SELECT':

            case 'WHERE':

            case 'FROM':
                $token_category = $upper;
            break;

            case '':

            case ',':

            case ';':
            break;

            default:
            break;
        }

        if ($token_category !== "" && ($prev_category === $token_category)) {

            $out[$token_category][] = $token;
        }

        $prev_category = $token_category;
    }

    $trimmed_out = array();
    $trimmed_sql = "";
    foreach($out as $key=>$value){
        $trimmed_sql .=" ". $key." ";
        foreach($value as $params) {
            if($params !=" "){
                $trimmed_out[$key][] = $params;
                $trimmed_sql .=$params;
            }
        }
    }

    if(secondCheck($trimmed_sql)){
        $select = array();
        $from = array();
        $where = array();
        $select = $trimmed_out['SELECT'];
        $from = $trimmed_out['FROM'];
        $where = $out['WHERE'];
        if(!$star) { 
            $select_length = count($select);
            if(!($select_length % 2)){
                $final_output = "";
                $final_output = "syntax error\n";
                return;
            } else if(selectCheck($select[$select_length-1])) {
                $final_output = "";
                $final_output = "syntax error\n";
                return;
            } else{
                for($i=0; $i<$select_length; $i=$i+2) {
                    if(!selectCheck($select[$i])){
                        if($i+1 < $select_length){
                            if($select[$i+1] == ","){
                                continue;
                            } else{
                                $final_output = "";
                                $final_output = "syntax error\n";
                                return;
                            }
                        } else {
                            continue;
                        }
                    } else{
                        $final_output = "";
                        $final_output = "syntax error\n";
                        return;
                    }

                }
            }
        }
        if(count($from) != 1 || !fromCheck($from[0])){
            $final_output = "";
            $final_output = "syntax error\n";
            return;
        }

        $where_string = "where";
        foreach($where as $words) {
            $where_string .= $words;
        }
        $where_string = trim($where_string);
        if(!whereCheck($where_string)){
            $final_output = "";
            $final_output = "syntax error\n";
            return;
        }
        query($trimmed_out);

    } else {
        $final_output = "";
        $final_output = "syntax error\n";
    }
}


function spli($sqli) {
    $splitters = array("<=>", "\r\n", "!=", ">=", "<=", "<>", "<<", ">>", ":=", "\\", "&&", "||", ":=", ">", "<", "|", "=", "^", "(", ")", "\t", "\n", "'", "\"", "`", ",", "@", " ", "+", "-", "*", "/", ";");
    $tokenSize = strlen($splitters[0]);
    $hashSet =array_flip($splitters);

    if (!is_string($sqli)) {
        //   throw new InvalidParameterException($sql);
    }

    $tokens = array();
    $token = "";

    $splitLen = 3;
    $found = false;
    $len = strlen($sqli);
    $pos = 0;

    while ($pos < $len) {

        for ($i = $splitLen; $i > 0; $i--) {
            $substr = substr($sqli, $pos, $i);
            if (isset($hashSet[$substr])) {

                if ($token !== "") {
                    $tokens[] = $token;
                }

                $tokens[] = $substr;
                $pos += $i;
                $token = "";

                continue 2;
            }
        }

        $token .= $sqli[$pos];
        $pos++;
    }

    if ($token !== "") {
        $tokens[] = $token;
    }
    process($tokens);
}

function query($final_input){
    global $final_output;
    global $star;
    $all_operators = array("!=", ">=", "<=", "<>", ">", "<", "=");

    $equ_operators = array("!="=>"!=", ">="=>">=", "<="=>"<=", "<>"=>"!=", ">"=>">", "<"=>"<", "="=>"==");
    $table = @fopen($final_input['FROM'][0].".csv", "r");
    if (!$table) {
        $final_output = "";
        $final_output = "Error -  No such table \"" . $final_input['FROM'][0] . "\" exists\n";
        return;
    }
    $rows_csv = fgetcsv($table);
    $rows = array();
    if($star == 1){
        $rows = $rows_csv;
    }else {
        foreach($final_input['SELECT'] as $row) {
            if($row != ",")
                $rows[] = $row;
        }
    }
    $flip_row = array_flip($rows_csv);
    $valid_rows = array_diff($rows, $rows_csv);
    if($valid_rows) {
        $final_output = "";
        foreach($valid_rows as $row) {
            $final_output .= "Error - No such row  \"" . $row . "\" exists\n";
        }
        return;
    }
    $tb = new Console_Table();
    $tb->setHeaders($rows);
    $condition = $final_input['WHERE'];
    $clength = count($condition);
    $operation = array();
    $operand = array();
    $absolute = array();
    $flag = 0;
    for($i=0; $i<$clength-1; $i++) {
        if(in_array($condition[$i], $all_operators)){
            $operation[] = $condition[$i];
        } else if(strcasecmp($condition[$i], "and") && $condition[$i] != "\"" && $condition[$i] != "'" && $flag == 0){
            $operand[] = $condition[$i];
            $flag = 1;
        } else if(strcasecmp($condition[$i], "and") && $condition[$i] != "\"" && $condition[$i] != "'" && $flag == 1){
            $absolute[] = $condition[$i];
            $flag = 0;
        }

    }
    $valid_cond = array_diff($operand, $rows_csv);
    if($valid_cond) {
        $final_output = "";
        foreach($valid_cond as $row) {
            $final_output .= "Error - No such row  \"" . $row . "\" exists in WHERE clause\n";
        }
        return;
    }
    while (($data = fgetcsv($table)) !== FALSE) {
        $temp = array();
        $temp = array_fill(0, count($operand), 0);
        $g = 0;
        foreach($operand as $key=>$row) {
            switch ($operation[$key]) {
            case "=":
                if($data[$flip_row[$row]] == $absolute[$key]) {
                    $temp[$g] = 1;
                    $g++;
                }
                break;

            case "!=":
                if($data[$flip_row[$row]] != $absolute[$key]){
                    $temp[$g] = 1;
                    $g++;
                }
                break;

            case ">=":
                if($data[$flip_row[$row]] >= $absolute[$key]){
                    $temp[$g] = 1;
                    $g++;
                }
                break;

            case "<=":
                if($data[$flip_row[$row]] <= $absolute[$key]){
                    $temp[$g] = 1;
                    $g++;
                }
                break;

            case ">":
                if($data[$flip_row[$row]] >  $absolute[$key]){
                    $temp[$g] = 1;
                    $g++;
                }
                break;

            case "<":
                if($data[$flip_row[$row]] <  $absolute[$key]){
                    $temp[$g] = 1;
                    $g++;
                }
                break;

            case "<>":
                if($data[$flip_row[$row]] != $absolutep[$key]){
                    $temp[$g] = 1;
                    $g++;
                }
                break;

            }

        }
        if(!in_array(0,$temp)) {
            $single_row = array();
            foreach($rows as $row_name){
                $single_row[] = $data[$flip_row[$row_name]];
            }
            $tb->addRow($single_row);
        }
    }
    $final_output = "";
    $final_output .= $tb->getTable();


}
?>
