<?php
/**
 * Created by PhpStorm.
 * User: Happy
 * Date: 2015/9/26
 * Time: 9:58
 */
require_once './IRService.php';
$IR_service= new IRService();
$IR_service ->MBNB_classifier();
echo "finish";