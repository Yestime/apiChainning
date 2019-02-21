<?php

namespace apiChain;

trait UtilsTrait {

  public function extract_date($dateString, $start, $length, $delimeter="-"){
    
    
    if($dateString && gettype($dateString)== "string"){
        $dateString = substr($dateString,$start,$length);

        $dateArray = explode($delimeter, $dateString);
        $dateArray[1] = str_pad($dateArray[1],2,"0",STR_PAD_LEFT);
        $dateArray[2] = str_pad($dateArray[2],2,"0",STR_PAD_LEFT);

        return implode($delimeter, $dateArray);
    }
    return "";
  }


  public function format_time($timeString, $delimeter=":"){
    
    
    if($timeString && gettype($timeString)== "string"){
        

        $timeArray = explode($delimeter, $timeString);
        $timeArray[0] = str_pad($timeArray[0],2,"0",STR_PAD_LEFT);
        return implode($delimeter, $timeArray);
    }
    return "";
  }

  public function num_compare($variable1, $operator, $variable2){
    
    
    if($variable1 && is_numeric($variable1) && $operator && $variable2 &&  is_numeric($variable2)){

        switch($operator){
                case "<":
                    return $variable1 < $variable2;
                case ">":
                    return $variable1 > $variable2;
                case ">=":
                    return $variable1 >= $variable2;
                case "<=":
                    return $variable1 <= $variable2;
                case "!=":
                    return $variable1 != $variable2;
                case "!==":
                    return $variable1 !== $variable2;
                case "===":
                    return $variable1 === $variable2;
                case "==":
                    return $variable1 == $variable2;
                default:
                return null;
        }
    }
    return null;
  }  


  public function has_results($value){
    
    switch(gettype($value)){
        case "boolean":
            return $value == true?$value:false;
        case "integer":
            return true;
        case  "double": 
            return true;
        case  "string":
            return strlen(trim($value))>0?true:false;
        case "array":
            return count($value)>0?true:false;
        case "object":
            return count( (array) $value)>0?true:false;
        case "resource": 
            return $value?true:false;
        case "NULL":
            return false;
        case "unknown type":
            return false;
        default:
            return $value?true:false;
        }
  }  
}