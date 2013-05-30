<?php
$autoloadFile=__DIR__.'/../autoload.php';
if (!file_exists($autoloadFile)) {
    $autoloadFile=__DIR__.'/../autoload.php.dist';
}
if (!($loader=@include $autoloadFile)) {
    die("please use auto loader!\n");
}