<?php
/**
 * Created by PhpStorm.
 * User: dick
 * Date: 2015/12/26
 * Time: 10:40
 */
require_once './IRService.php';


class Cluster {
    // centroid clustering
    public function centroid_clustering( $cluster_num_array=array(8,13,20,35)){
        $IR_service= new IRService();
        //获取全部的文件名
        $origin_files_array =  $IR_service ->get_origin_files();
        $origin_files_array =$origin_files_array [ "file_only_name"];
        $cluster_max_num = count($origin_files_array);
        $clusters_array = $this->centroid_clustering_recursion($origin_files_array,$cluster_max_num);

        foreach(  $origin_files_array as $cluster_num){
            if( $cluster_num >  $cluster_max_num){
                echo "The amount of cluster is bigger than the amount of documents!";
            }else{
                $this->centroid_clustering_result_output($cluster_num,$clusters_array[ $cluster_num]);
            }
        }
       echo "Finish the job!";
    }
     //按照指定格式将centroid clustering 的结果输出到指定文件夹下
    public function centroid_clustering_result_output($cluster_num,$clusters_array){
        $handle_result=fopen(dirname(__FILE__) . "\\program_result\\".$cluster_num."_cluster.txt","w");
        foreach($clusters_array as $docId_array){
            foreach( $docId_array as $docId){
                $line_content=$docId."\r\n";
                fwrite(  $handle_result,$line_content);
            }
            $line_content="\r\n";
            fwrite(  $handle_result,$line_content);
        }
        fclose( $handle_result);
    }
    /*
*  centroid clustering 的循环处理过程
     * centroid clustering 的初始化处理，生成 N 篇文章即为N个cluster的原始cluster数组，N个cluster的terms_TFIDF数组，N个cluster两两之间的相似度数组
     * centroid clustering 的循环处理，依次得到cluster数组，cluster的terms_TFIDF数组，cluster两两之间的相似度数组
     * centroid clustering 最后结果，得到N、N-1、N-2...1个cluster
* */
    public function centroid_clustering_recursion($origin_files_array,$cluster_max_num){
            $IR_service= new IRService();
            $array_temp = $this->centroid_clustering_clusters_N();
            $clusters_array[ $cluster_max_num]= $array_temp["clusters_N"];//N 篇文章即为N个cluster
            //var_dump($clusters_array[ $cluster_max_num]);
            $clusters_terms_TFIDF_array= $array_temp["clusters_terms_TFIDF_array"];//得到N个cluster的terms_TFIDF
            //var_dump($clusters_terms_TFIDF_array);
            $array_temp=$this->clusters_similarity_init($origin_files_array,$clusters_terms_TFIDF_array);//计算N个cluster之间的相似度
            $cluster1=$array_temp['cluster1'];
            $cluster2=$array_temp['cluster2'];
            $clusters_similarity=$array_temp['clusters_similarity'];
            //var_dump($array_temp);
			 $IR_service->array_into_file( $file="\\program_result\\record.txt" ,$type='a',$title="",array(array($cluster_max_num,count( $clusters_array[$cluster_max_num]),count($clusters_terms_TFIDF_array), $cluster1, $cluster2)),$line_element_num=5);
             //clustering中间循环
             for($recursion_time=$cluster_max_num-1;$recursion_time>1;$recursion_time--){
				 $clusters_array=$this->clusters_record($cluster1,$cluster2,$clusters_array,$cluster_max_num,$recursion_time);
                  $clusters_terms_TFIDF_array=$this->cluster_terms_TFIDF_merge( $cluster1,$cluster2,$clusters_terms_TFIDF_array,$cluster_max_num);//cluster terms_TFIDF merge
                 $IR_service->array_into_file( $file="\\program_result\\record.txt" ,$type='a',$title="",array(array($recursion_time,count( $clusters_array[$recursion_time]),count($clusters_terms_TFIDF_array), $cluster1, $cluster2)),$line_element_num=5);
				 //var_dump($clusters_similarity);
                 //var_dump($clusters_terms_TFIDF_array);
                 $array_temp=$this->cluster_similarity_merge($cluster1,$cluster2,$clusters_similarity,$clusters_terms_TFIDF_array,$cluster_max_num);//cluster similarity merge
				 //var_dump(count($array_temp['clusters_similarity']));
			     $cluster1=$array_temp['cluster1'];
                 $cluster2=$array_temp['cluster2'];
                 $clusters_similarity=$array_temp['clusters_similarity'];        
             }
            //clustering最后处理
            $clusters_array[1]=array($origin_files_array);
        return $clusters_array;
    }
 //记录centroid clustering 处理过程中生成的N、N-1、N-2...1个cluster所拥有的document id
    public function clusters_record($cluster1,$cluster2,$clusters_array,$cluster_max_num,$recursion_time){
        $clusters_array_temp=$clusters_array[$recursion_time+1];
        //var_dump($clusters_array_temp);
        foreach( $clusters_array_temp[$cluster1] as $element){
            $clusters_array_temp[$cluster1+$cluster_max_num][]=$element;
        }
        foreach( $clusters_array_temp[$cluster2] as $element){
            $clusters_array_temp[$cluster1+$cluster_max_num][]=$element;
        }
        unset($clusters_array_temp[$cluster1]);
        unset($clusters_array_temp[$cluster2]);
        $clusters_array[$recursion_time]=$clusters_array_temp;
        return $clusters_array;
    }
 //计算两两文章的相似度，为合并文章，形成新的cluster提供依据
    public function clusters_similarity_init($clusters_array,$clusters_terms_TFIDF_array){
        $clusters_similarity= array();
        $clusters_similarity_top= array();
        foreach($clusters_array as $cluster1){
            $cluster_done_arrays[]=$cluster1;
            $clusters_similarity_temp=array();
           // var_dump($cluster1);
            foreach($clusters_array as $cluster2){
                if(!in_array($cluster2, $cluster_done_arrays, true)){
                    $clusters_similarity_temp[$cluster2]= $this->centroid_clustering_cosine_TFIDF($clusters_terms_TFIDF_array[$cluster1],$clusters_terms_TFIDF_array[$cluster2]);
                }
            }
            //var_dump(current( array_keys($clusters_similarity_temp)));
            //var_dump(current( array_values($clusters_similarity_temp)));
            //var_dump( $clusters_similarity_temp);
            //var_dump( count($clusters_similarity_temp));
            if(!empty($clusters_similarity_temp)){
                arsort( $clusters_similarity_temp);
                $clusters_similarity[$cluster1]=$clusters_similarity_temp;
                $clusters_similarity_top[$cluster1."=".current( array_keys($clusters_similarity_temp))]= current( array_values($clusters_similarity_temp));
            }
           // var_dump($clusters_similarity[$cluster1."=".current( array_keys($clusters_similarity_temp))]);
        }
        arsort( $clusters_similarity_top);
        list($cluster_left, $cluster_right) =explode ('=', current( array_keys($clusters_similarity_top)));
       // var_dump( $cluster_left);
      //  var_dump( $cluster_right);
       // var_dump( $clusters_similarity);
        return array(
            'cluster1' => $cluster_left,
            'cluster2' => $cluster_right,
            'clusters_similarity'=>$clusters_similarity
        );
    }
 //根据相似度，合并文章/cluster
    public function cluster_similarity_merge($cluster1,$cluster2, $clusters_similarity,$clusters_terms_TFIDF_array,$documents_amount){
        unset($clusters_similarity[$cluster1]);
        unset($clusters_similarity[$cluster2]);
        $clusters_similarity_top= array();
        foreach($clusters_similarity as $elements_key =>$elements_value ){
		
           //var_dump(count($clusters_similarity[$elements_key]));
            unset($clusters_similarity[$elements_key][$cluster1]);
			unset($clusters_similarity[$elements_key][$cluster2]);
			//var_dump($clusters_terms_TFIDF_array[$cluster1+$documents_amount]);
           // var_dump($clusters_terms_TFIDF_array[$elements_key]);
			$clusters_similarity[$elements_key][$cluster1+$documents_amount] =$this->centroid_clustering_cosine_TFIDF($clusters_terms_TFIDF_array[$cluster1+$documents_amount],$clusters_terms_TFIDF_array[$elements_key]);
			arsort( $clusters_similarity[$elements_key]);
            $clusters_similarity_top[$elements_key."=".current( array_keys( $clusters_similarity[$elements_key]))]= current( array_values( $clusters_similarity[$elements_key]));
            //var_dump(count($clusters_similarity[$elements_key]));
        }
       // var_dump(count($clusters_similarity));
        arsort( $clusters_similarity_top);
        list($cluster_left, $cluster_right) =explode ('=', current( array_keys($clusters_similarity_top)));
        // var_dump( $cluster_left);
        // var_dump( $cluster_right);
        // var_dump( $clusters_similarity);
        return array(
            'cluster1' => $cluster_left,
            'cluster2' => $cluster_right,
            'clusters_similarity'=>$clusters_similarity
        );
	
	}
 //合并文章/cluster的terms_TFIDF情况
    public function cluster_terms_TFIDF_merge($cluster1,$cluster2,$clusters_terms_TFIDF_array,$documents_amount){
		//var_dump(count($clusters_terms_TFIDF_array));
          foreach($clusters_terms_TFIDF_array[$cluster2] as $key =>$value){
              if(!empty($clusters_terms_TFIDF_array[$cluster1][$key])){
                  $clusters_terms_TFIDF_array[$cluster1][$key][1]+=$value[1];
              }else{
                  $clusters_terms_TFIDF_array[$cluster1][$key][]=$key;
                  $clusters_terms_TFIDF_array[$cluster1][$key][]=$value[1];
              }
          }
        $clusters_terms_TFIDF_array[$cluster1+$documents_amount]= $clusters_terms_TFIDF_array[$cluster1];
        unset($clusters_terms_TFIDF_array[$cluster1]);
        unset($clusters_terms_TFIDF_array[$cluster2]);
		//var_dump(count($clusters_terms_TFIDF_array));
        return $clusters_terms_TFIDF_array;
    }
 //将N篇文章转化为N个cluster（附带cluster对应的document id 以及 document numbers）
    public function centroid_clustering_clusters_N(){
        $IR_service= new IRService();
        $docs_terms_TFIDF =$IR_service ->get_terms_TFIDF();
        $clusters_N_array= array();
        $clusters_terms_TFIDF_array= array();
        foreach( $docs_terms_TFIDF as $doc_key => $terms_TFIDF_value){
            $clusters_N_array[]=array($doc_key);
            foreach($terms_TFIDF_value as $term_key => $term_value){
                $clusters_terms_TFIDF_array[$doc_key][$term_key][]=$term_value[0];
                $clusters_terms_TFIDF_array[$doc_key][$term_key][]=$term_value[1];
            }
            $clusters_terms_TFIDF_array[$doc_key]['DOCS_TOTAL'][]='DOCS_TOTAL';
            $clusters_terms_TFIDF_array[$doc_key]['DOCS_TOTAL'][]=1;
        }
        sort( $clusters_N_array);
       // var_dump($clusters_N_array);
       // var_dump( $clusters_terms_TFIDF_array);
        return array(
            "clusters_N"=> $clusters_N_array,
            "clusters_terms_TFIDF_array"=> $clusters_terms_TFIDF_array
        );
    }

    public function cluster_prepare(){
        require_once './IRService.php';
        $IR_service= new IRService();
        $IR_service ->consin_prepare();
    }
  // 计算出两个cluster的中心点相似度
    public function centroid_clustering_cosine_TFIDF($cluster1,$cluster2){
        // var_dump($cluster1);
        $inner_product =0;
        foreach($cluster1 as $cluster1_terms_IDF){
            if((!@is_null($cluster2[ $cluster1_terms_IDF[0]]))&&($cluster2[ $cluster1_terms_IDF[0]]!='TFIDF_TOTAL')&&($cluster2[ $cluster1_terms_IDF[0]]!='DOCS_TOTAL')){
                $inner_product +=($cluster1_terms_IDF[1]/$cluster1['DOCS_TOTAL'][1])*($cluster2[ $cluster1_terms_IDF[0]][1]/$cluster2['DOCS_TOTAL'][1]);
                // print_r($inner_product."   ".$cluster1_terms_IDF[1]."    ".$cluster2[ $cluster1_terms_IDF[0]][1]);
            }
        }
        $cosine =  $inner_product/(sqrt($cluster1['TFIDF_TOTAL'][1]/$cluster1['DOCS_TOTAL'][1])*sqrt($cluster2['TFIDF_TOTAL'][1]/$cluster2['DOCS_TOTAL'][1]));
        return $cosine;
    }
}