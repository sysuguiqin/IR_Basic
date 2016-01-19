<?php
/**
 * Created by PhpStorm.
 * User: Happy
 * Date: 2015/9/26
 * Time: 9:58
 */
require_once './IRService.php';
$IR_service= new IRService();
echo $IR_service ->get_cosine($_GET['docID1'],$_GET['docID2'],$_GET['isTFIDF']);