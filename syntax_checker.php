<?php

//Checks the basic structure of query
function firstCheck($input_sql){
    $pattern = "/\s*select\s+(?:(?!\s+from\s*).)*\s+from\s+[a-z0-9_]+\s+where\s+[^.,;]+\s*;$/i";
    preg_match($pattern, $input_sql, $first_pass);
    if(!empty($first_pass)){
        return true;
    } else{
        return false;
    }
}

//This function further checks for syntax errors
function secondCheck($input_sql){
    global $star;
    $input_sql = trim($input_sql);
    $star_pattern= "/\s*select\s+(\*)\s+from\s*[a-z0-9_]+\s+where\s+[^.,;]+\s*;$/i";
    preg_match($star_pattern, $input_sql, $star_pass);
    if(!empty($star_pass)){
        $star = 1;
        return true;
    } else{
        $star = 0;
    }

    $pattern = "/\s*select\s+\s*(([a-z0-9_]+,?)+)\s+from\s*[a-z0-9_]+\s+where\s+[^.,;]+\s*;$/i";
    preg_match($pattern, $input_sql, $second_pass);
    if(!empty($second_pass)){
        return true;
    } else{
        return false;
    }
}


//This function checks for errors in SELECT part of query
function selectCheck($input_select){

    $select_pattern = '/[^a-zA-Z_]+/';
    preg_match($select_pattern, $input_select, $select_pass);
    if(!empty($select_pass)){
        return true;
    } else{
        return false;
    }
}

//This function checks for errors in FROM part of query
function fromCheck($input_from) {
    $from_pattern = '/[^A-Za-z0-9 _ .-]/';
    preg_match($from_pattern, $input_from, $from_pass);
    if(!empty($from_pass)){
        return false;
    } else {
        return true;
    }
}



//This function checks for errors in WHERE part of query
function whereCheck($input_where) {
    $where_pattern = "/where\s+\w+\s*(<=>|!=|>=|<=|<>|>|<|=)\s*([0-9]+|'\w+'|\"\w+\")(?:\s+and\s+\w+\s*(<=>|!=|>=|<=|<>|>|<|=)\s*([0-9]+|'\w+'|\"\w+\")\s*?)*;$/i";
    preg_match($where_pattern, $input_where, $where_pass);
    if(!empty($where_pass)){
        return true;
    } else {
        return false;
    }
}


?>
