<?php
/**
 * Created by PhpStorm.
 * User: Happy
 * Date: 2015/9/26
 * Time: 9:58
 */

require_once './Cluster.php';
$cluster= new Cluster();
$cluster->cluster_prepare();
$cluster->centroid_clustering();
echo "Finish the job!!";

