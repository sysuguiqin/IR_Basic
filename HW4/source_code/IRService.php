<?php
/**
 * Created by PhpStorm.
 * User: Happy
 * Date: 2015/10/19
 * Time: 9:06
 */
/*
 * 词频 (TF) 是一词语出现的次数除以该文件的总词语数。 在本程序中，总词语数是指有效单词的总词语数，即不包括被stop_word 删除的数量
 * */
class IRService {

//====================================================文章分类===========start==========================================
//====================================================文章分类===========Multinomial Model=========start================
/*
 *Multinomial Model
 * */
    public function MBNB_classifier(){
        //获取全部的文件名单
        $origin_files_array = $this -> get_origin_files();
        $origin_files_array= $origin_files_array["file_only_name"];
        //var_dump( $origin_files_array);

        //获取training_data class-file 信息
        $training_data = $this->get_classifier_training_data();

        // naive_bayes 前期处理
        $this->naive_bayes_prepare();

        //获取所有文件所有特异单词CF
        $termsCF_array=$this -> get_terms_CF();
        //var_dump($termsCF_array);

        //mbnb training process
        $training_data_dictionary = $this -> mbnb_training_process($training_data,$termsCF_array);
       // var_dump( $training_data_dictionary);

        //mbnb testing process
        $testing_process_data= $this ->mbnb_testing_process(  $training_data_dictionary,$training_data,$termsCF_array);
        //var_dump($testing_process_data);

        //mbnb testing result 保存到指定文件
        $this  -> array_into_file( $file= "\\program_result\\MBNB_output.txt",$type="w",$title="doc_id   class_id",$array=$testing_process_data,$line_element_num=2);
    }

/*
 *  获取training_data class-file 信息
 * */
    public function get_classifier_training_data(){
        $file_path =dirname(__FILE__)."\\program_result\\tool_file\\classify\\training.txt";
        $nb_training_data_class_array = array();
        if(file_exists($file_path)){
            $file_content=file($file_path);//读取文件内容
            foreach($file_content as $line){
                $line = str_replace(array("\r\n", "\r", "\n"),'',$line);
                $line=explode(" ",$line);
                $is_first =true;
                foreach($line as $element){
                    if($is_first){
                        $nb_training_data_class_array['class'][] = $element;
                        $is_first =false;
                    }else{
                        if(!empty($element)){
                            $nb_training_data_class_array['file'][]= $element;
                            $nb_training_data_class_array['class_file'][$line[0]][]= $element;
                        }
                    }
                }
            }
        }
        // var_dump( $nb_training_data_class_array);
        return $nb_training_data_class_array;
    }

    /*
     * mbnb training process
     * */
    public function  mbnb_training_process($training_data,$termsCF_array){
        //MBNB_classifier training process
        $training_data_dictionary = array();//training_data 中的term
        $training_data_dictionary['dictionary_terms_amount']=0;
        $class_element_dictionary = array();//training_data 中每一类的term 以及term 出现的总次数还有所有terms出现的总次数
        foreach( $training_data['class'] as $class_element){
            $class_element_probability[ $class_element]=count($training_data['class_file'][$class_element])/count($training_data['file']);
            /*
             var_dump($class_element);
             var_dump(count($training_data['class_file'][$class_element]));
             var_dump(count($training_data['file']));
            */
            $class_element_dictionary[ $class_element."_terms_amount"]=0;
            foreach($training_data['class_file'][$class_element] as $file ){
                foreach($termsCF_array[$file] as  $key=>$value){
                    if(!@in_array($key , $class_element_dictionary[ $class_element."_terms"],true)){
                        $class_element_dictionary[ $class_element."_terms"][]= $key;
                        $class_element_dictionary[ $class_element."_".$key]=0+$value[1];
                        if(!@in_array($key , $training_data_dictionary['dictionary_terms_element'],true)){
                            $training_data_dictionary['dictionary_terms_element'][] =$key;
                        }
                    }else{
                        $class_element_dictionary[ $class_element."_".$key] += $value[1];
                    }
                    $training_data_dictionary['dictionary_terms_amount'] += $value[1];
                    $class_element_dictionary[ $class_element."_terms_amount"] +=$value[1];
                }
            }
        }
        $class_element_irrevelant_dictionary= array();
        foreach( $training_data['class'] as $class_element_outer){
            $class_element_irrevelant_dictionary[ $class_element_outer."_irrevelant_terms_amount"]=  0 ;
            foreach( $training_data['class'] as $class_element_inner){
                if($class_element_inner !=$class_element_outer){
                    foreach( $class_element_dictionary[ $class_element_inner."_terms"] as $term){
                        if(!@in_array($term,  $class_element_irrevelant_dictionary[ $class_element_outer."irrevelant_terms"],true)){
                            $class_element_irrevelant_dictionary[$class_element_outer."_irrevelant_terms"][]=  $term;
                            // $class_element_irrevelant_dictionary[ $class_element_outer."_irrevelant_".$term][]= $class_element_inner."-".$class_element_dictionary[ $class_element_inner."_". $term];//
                            $class_element_irrevelant_dictionary[ $class_element_outer."_irrevelant_".$term]=0+$class_element_dictionary[ $class_element_inner."_". $term] ;
                        }else{
                            // $class_element_irrevelant_dictionary[ $class_element_outer."_irrevelant_".$term][]= $class_element_inner."-".$class_element_dictionary[ $class_element_inner."_". $term];//+=  $class_element_dictionary[ $class_element_inner."_". $term] ;
                            $class_element_irrevelant_dictionary[ $class_element_outer."_irrevelant_".$term]+=$class_element_dictionary[ $class_element_inner."_". $term] ;
                        }
                    }
                    $class_element_irrevelant_dictionary[ $class_element_outer."_irrevelant_terms_amount"] += $class_element_dictionary[ $class_element_inner."_terms_amount"];

                }
            }
        }
        /*
              var_dump(  $class_element_dictionary);
              var_dump( $training_data_dictionary);
            */
        return array(
            "whole_dictinoary"  =>$training_data_dictionary,
            "class_dictionary" =>$class_element_dictionary,
            "class_probability" => $class_element_probability,
            "class_no_dictionary" => $class_element_irrevelant_dictionary
        );
    }
    /*
     * mbnb testing process
     * */
    public function mbnb_testing_process(  $training_data_dictionary,$training_data,$termsCF_array){
        //testing process
        $testing_process_data =array();
        $testing_result_data =array();
        sort($origin_files_array);
        foreach( $origin_files_array as $origin_file ){
            if(!in_array( $origin_file,$training_data['file'],true)){

                foreach(  $training_data['class'] as $class_element ){
                    $probability_docs_yes = $training_data_dictionary[ "class_probability"][$class_element];
                    $probability_docs_no = 1-$training_data_dictionary[ "class_probability"][$class_element];

                    // $probability_terms_yes_log =0;
                    // $probability_terms_no_log =0;
                    /*
                     * 取消 adding logarithms of probabilities 方式计算class的概率说明：
                     * 由于log(1)=0而实际上所有的log(p(x =t|c))中p(x =t|c)都会远远小于1，因此都为负数，这带来很大的计算困难
                     * 其次当t不曾出现在training_data中p(x =t|c)=0，即为log(0)，其值是一个无限大的负数，很难估量
                     * */
                    $probability_terms_yes_add =1;
                    $probability_terms_no_add =1;
                    foreach( $termsCF_array[$origin_file] as  $key=>$value){

                        if(empty($training_data_dictionary[ "class_dictionary"][$class_element."_".$key])){

                            //adding logarithms of probabilities
                            //$probability_terms_yes_log += $value[1]*log( 0/$training_data_dictionary[ "class_dictionary"][$class_element.'_terms_amount']);
                            //var_dump( $probability_terms_yes_log);

                            //add-one smoothing
                            $probability_terms_yes_add *= pow((0+1)/($training_data_dictionary[ "class_dictionary"][$class_element.'_terms_amount']+ count($training_data_dictionary[ 'whole_dictinoary']['dictionary_terms_element'])),$value[1]);
                            //var_dump($value[1]."=====================".'1'."========". $training_data_dictionary[ "class_dictionary"][$class_element.'_terms_amount']."=======" .count($training_data_dictionary[ 'whole_dictinoary']['dictionary_terms_element']));
                            //  var_dump($probability_terms_yes_add);
                        }else{
                            //adding logarithms of probabilities
                            //$probability_terms_yes_log += $value[1]*log($training_data_dictionary[ "class_dictionary"][$class_element."_".$key]/$training_data_dictionary[ "class_dictionary"][$class_element.'_terms_amount']);
                            //var_dump( $probability_terms_yes_log);

                            //add-one smoothing
                            $probability_terms_yes_add *= pow(($training_data_dictionary[ "class_dictionary"][$class_element."_".$key]+1)/($training_data_dictionary[ "class_dictionary"][$class_element.'_terms_amount']+ count($training_data_dictionary[ 'whole_dictinoary']['dictionary_terms_element'])),$value[1]);
                            // var_dump( $probability_terms_yes_add);
                            //var_dump($value[1]."=====================".$training_data_dictionary[ "class_dictionary"][$class_element."_".$key]."========". $training_data_dictionary[ "class_dictionary"][$class_element.'_terms_amount']."=======" .count($training_data_dictionary[ 'whole_dictinoary']['dictionary_terms_element']));

                        }
                        if(empty($training_data_dictionary[ "class_no_dictionary"][$class_element."_irrevelant_".$key])){
                            //$probability_terms_no_log +=  $value[1]*log(0/$training_data_dictionary[ "class_no_dictionary"][$class_element.'_irrevelant_terms_amount']);

                            $probability_terms_no_add *=  pow((0+1)/($training_data_dictionary[ "class_no_dictionary"][$class_element.'_irrevelant_terms_amount']+count($training_data_dictionary[ 'whole_dictinoary']['dictionary_terms_element'])),$value[1]);

                        }else{
                            //$probability_terms_no_log +=  $value[1]*log($training_data_dictionary[ "class_no_dictionary"][$class_element."_irrevelant_".$key]/$training_data_dictionary[ "class_no_dictionary"][$class_element.'_irrevelant_terms_amount']);

                            $probability_terms_no_add *=  pow(($training_data_dictionary[ "class_no_dictionary"][$class_element."_irrevelant_".$key]+1)/($training_data_dictionary[ "class_no_dictionary"][$class_element.'_irrevelant_terms_amount']+count($training_data_dictionary[ 'whole_dictinoary']['dictionary_terms_element'])),$value[1]);
                        }
                    }
                    // $testing_process_data[$origin_file]['log_yes'][$class_element] = log($probability_docs_yes)+ $probability_terms_yes_log;
                    // $testing_process_data[$origin_file]['log_no'][$class_element] =log( $probability_docs_no)+ $probability_terms_no_log;

                    $testing_process_data[$origin_file]['add_yes'][$class_element] =$probability_docs_yes *$probability_terms_yes_add;
                    $testing_process_data[$origin_file]['add_no'][$class_element] =$probability_docs_no *$probability_terms_no_add;
                    //var_dump($probability_docs_yes);
                    //var_dump($probability_docs_no);

                    //var_dump($probability_terms_yes_log);
                    //var_dump($probability_terms_no_log);

                    //var_dump($probability_terms_yes_add);
                    //var_dump($probability_terms_no_add);
                }
                // arsort($testing_process_data[$origin_file]['log_yes']);
                // asort($testing_process_data[$origin_file]['log_no']);
                arsort($testing_process_data[$origin_file]['add_yes']);
                var_dump(key($testing_process_data[$origin_file]['add_yes']));
                $testing_result_data[]=array($origin_file ,key($testing_process_data[$origin_file]['add_yes']));
                asort($testing_process_data[$origin_file]['add_no']);
            }
        }
        return $testing_result_data;

    }
//====================================================文章分类===========Multinomial Model===========end================
 /*
  *   navie_bayes 前期处理
  * */
    public function naive_bayes_prepare(){
        //获取处理过的文件名单
        $dealed_files_array = $this -> get_dealed_files();
        //  var_dump($dealed_files_array);

        //获取全部的文件名单
        $origin_files_array = $this ->get_origin_files();
        $origin_files_array= $origin_files_array[ "file_with_style"];
        //var_dump( $origin_files_array);

        //获取stopword清单
        $stopword_array=  $this -> get_stopword_list();
        //var_dump($stopword_array);

        //获取已有的termsDF
        $termsDF_array=  $this -> get_terms_DF();
        //var_dump($termsDF_array);

        $this->createFolder(dirname(__FILE__) . "\\program_result\\tool_file");
        $handle_file_deal=fopen(dirname(__FILE__) . "\\program_result\\tool_file\\doc_dealed.txt","a");
        //逐个处理文件
        foreach($origin_files_array as $origin_file ){
            //筛选出要处理的文件
            if(!in_array($origin_file,  $dealed_files_array, true)){
                //var_dump($origin_file);
                //读取文件内容
                $file_path=dirname(__FILE__)."\\origin_file\\".$origin_file;
                $file_content_string=file_get_contents($file_path);
                //var_dump( $file_content_string);

                //token
                $file_content_string=  $this ->tokenization($file_content_string);
                //var_dump( $file_content_string);

                //lower and normal
                $file_terms_array=  $this -> lowercasing_normalization($file_content_string);
                //var_dump($file_terms_array);

                //stem
                $file_terms_array=  $this -> stemming( $file_terms_array);
                //var_dump($file_terms_array);

                //计算并保存CF
                $termsDF_array= $this ->save_terms_CF($origin_file,$file_terms_array,$stopword_array,$termsDF_array);
                //将处理过的文件登记
                $line_content = $origin_file."\r\n";
                fwrite( $handle_file_deal,$line_content);
            }
        }
        fclose( $handle_file_deal);
        //计算并保存DF
        $this -> save_terms_DF($termsDF_array);
        $this -> save_terms_DF_homework($termsDF_array);
    }
//====================================================文章分类========================end===============================


//===================================================计算文章相似度======start==========================================
    /*
     *  计算指定的两篇文章的相似度
     * */
    public function get_cosine( $docID1,$docID2,$isTFIDF=1){
        $this->consin_prepare();
        if($isTFIDF==1){
            //获取所有文章的所有单词的TFIDF
            $doc_terms_TFIDF =$this ->get_terms_TFIDF();
            return $this ->cosine_TFIDF( $doc_terms_TFIDF[$docID1], $doc_terms_TFIDF[$docID2]);
        }else{
            //获取所有文章的所有单词的WFIDF
            $doc_terms_WFIDF =$this ->get_terms_WFIDF();
            return $this ->cosine_WFIDF( $doc_terms_WFIDF[$docID1], $doc_terms_WFIDF[$docID2]);
        }
    }
/*
 *  计算文章相似度的前期工作
 * */
public function consin_prepare(){
    //获取处理过的文件名单
    $dealed_files_array = $this -> get_dealed_files();
  //  var_dump($dealed_files_array);

    //获取全部的文件名单
    $origin_files_array = $this ->get_origin_files();
    $origin_files_array= $origin_files_array[ "file_with_style"];
   //var_dump( $origin_files_array);

   //获取stopword清单
    $stopword_array=  $this -> get_stopword_list();
   //var_dump($stopword_array);

    //获取已有的termsDF
    $termsDF_array=  $this -> get_terms_DF();
    //var_dump($termsDF_array);

    $this->createFolder(dirname(__FILE__) . "\\program_result\\tool_file");
    $handle_file_deal=fopen(dirname(__FILE__) . "\\program_result\\tool_file\\doc_dealed.txt","a");
    //逐个处理文件
    foreach($origin_files_array as $origin_file ){
        //筛选出要处理的文件
        if(!in_array($origin_file,  $dealed_files_array, true)){
            //var_dump($origin_file);
            //读取文件内容
            $file_path=dirname(__FILE__)."\\origin_file\\".$origin_file;
            $file_content_string=file_get_contents($file_path);
            //var_dump( $file_content_string);

           //token
            $file_content_string=  $this ->tokenization($file_content_string);
            //var_dump( $file_content_string);

           //lower and normal
            $file_terms_array=  $this -> lowercasing_normalization($file_content_string);
            //var_dump($file_terms_array);

           //stem
            $file_terms_array=  $this -> stemming( $file_terms_array);
           //var_dump($file_terms_array);

            //计算并保存CF
            $termsDF_array= $this ->save_terms_CF($origin_file,$file_terms_array,$stopword_array,$termsDF_array);
            //将处理过的文件登记
            $line_content = $origin_file."\r\n";
            fwrite( $handle_file_deal,$line_content);
        }
    }
    fclose( $handle_file_deal);
    //计算并保存DF
    $this -> save_terms_DF($termsDF_array);
    $this -> save_terms_DF_homework($termsDF_array);

    $file_total= count($origin_files_array);//文章总数
    $termsCF_array=$this -> get_terms_CF();//获取所有文件所有特异单词CF
    //var_dump($termsCF_array);

    //计算并保存TFIDF
    $this->save_terms_TFIDF( $origin_files_array,$termsCF_array,$termsDF_array,$file_total);
    //计算并保存WFIDF
    $this->save_terms_WFIDF( $origin_files_array,$termsCF_array,$termsDF_array,$file_total);
}
//===================================================计算文章相似度========end==========================================

//=======================================================IR基本函数=====================================================
// 将形如 [0] => array([0] =>value1 ,[1]=>value2 ,..)格式的数组保存至指定文件
    public function array_into_file( $file ,$type,$title,$array,$line_element_num){
        $handle_file=fopen(dirname(__FILE__) .$file ,$type);
        $line_content =$title."\r\n";
        fwrite( $handle_file,$line_content);
        foreach( $array as $line ){
            $line_content ="";
            for($i=0;$i<$line_element_num;$i++){
                $line_content = $line_content. $line[$i]."    ";
            }
            $line_content = $line_content."\r\n";
            fwrite( $handle_file,$line_content);
        }
        fclose( $handle_file);
    }

// 创建文件夹
public function createFolder($path)
{
        if (!file_exists($path))
        {
            $this->createFolder(dirname($path));
            mkdir($path, 0777);
        }
}
//获取已经处理过的文件清单
public function get_dealed_files(){
    $file_path=dirname(__FILE__)."\\program_result\\tool_file\\doc_dealed.txt";
    $dealed_files_array = array();
    if(file_exists($file_path)){
        $file_content = file_get_contents($file_path);//读取result_doc_dealed.txt内容
        $file_content = str_replace(array("\r\n", "\r", "\n")," ",$file_content);
        $dealed_files_array=explode(" ",$file_content);
    }
    return  $dealed_files_array;
}
//获取指定文件夹下的所有文件
 public function get_origin_files( ){
     $dir=dirname(__FILE__)."\\origin_file"; //指定文件夹

     $origin_files_array=array();
     $handle=opendir($dir.".");//遍历文件夹下所有文件
     while (false !== ($file = readdir($handle)) )
     {
         if ($file != "." && $file != "..") {
             $origin_files_array[] = $file;
             $origin_only_file_name[] = str_replace('.txt','',$file);
         }
     }
     closedir($handle);
     sort($origin_only_file_name);
     return  array(
         "file_with_style" => $origin_files_array ,
         "file_only_name" => $origin_only_file_name
     );
 }

/* Tokenization 原理：根据句子结构标示符破除句子结构
    将句子结构标示符去掉，如 ，. ! ：; ?
    进阶考虑：r13579@ntu.edu.tw  r13579, ntu, edu, tw 关于 "."的处理问题
* */
public function tokenization($file_content_string){
    $tokenization_array = array(
        //英语标点符号 半角
        "`" => " ",
        "\"" => " ",
        "'" => " ",
        "," => " ",
        "." => " ",
        "?" => " ",
        "!" => " ",
        ":" => " ",
        ";" => " ",
        //英语标点符号 全角
        "，" => " ",
        "．" => " ",
        "？" => " ",
        "！" => " ",
        "：" => " ",
        "；" => " ",
        //中文标点符号 半角与全角
        "，" => " ",
        "。" => " ",
        "？" => " ",
        "！" => " ",
        "：" => " ",
        "；" => " ",
        "、" => " ",
    );
    $file_content_string = strtr($file_content_string, $tokenization_array);//strtr 对大小写敏感
    return $file_content_string;
}
    /*Lowercasing ：将所有大写字母变成小写字母
     进阶考虑：专有名词的大小写匹对准确性问题
     变化参考：http://www.jb51.net/article/49629.htm
     Normalization ：去除() <> [] {} ‘s,并将字符串拆分成单词数组
     进阶考虑：组合词的连接格形式，如 ~ ` - _
     the hold-him-back-and-drag-him-away manner.
   * */
public function lowercasing_normalization($file_content){
        $file_content = strtolower( $file_content);
        $normalization = array(
            //英文半角符号
            " '" => " ",
            "(" => " ",
            ")" => " ",
            "<" => " ",
            ">" => " ",
            "[" => " ",
            "]" => " ",
            "'s" => " ",
            "{" => " ",
            "}" => " ",
            //英文全角符号
            "（" => " ",
            "）" => " ",
            "＜" => " ",
            "＞" => " ",
            "［" => " ",
            "］" => " ",
            "＇ｓ" => " ",
            "｛" => " ",
            "｝" => " ",
            //中文半角与全角符号
            "）" => " ",
            "（" => " ",
            "《" => " ",
            "》" => " ",
            "【" => " ",
            "】" => " ",
            "{" => " ",
            "}" => " ",
            "\""=> " ",
            ";" => " ",
            //数字
            "0" => " ",
            "1" => " ",
            "2" => " ",
            "3" => " ",
            "4" => " ",
            "5" => " ",
            "6" => " ",
            "7" => " ",
            "8" => " ",
            "9" => " ",
            //特殊字符
            "." => " ",
            "？" => " ",
            "!" => " ",
            "~" => " ",
            "@" => " ",
            "#" => " ",
            "$" => " ",
            "%" => " ",
            "^" => " ",
            "&" => " ",
            "*" => " ",
            "=" => " ",
            ":" => " ",
            "''" => " ",
            "—" => " ",
            "_" => " ",
            "-" => " ",
            "\\" => " ",
            ";" => " ",
            "；" => " ",
            "/" => " "
        );
        $file_content = strtr($file_content,$normalization);//strtr 对大小写敏感
        $file_content = str_replace(array("\r\n", "\r", "\n"),'',$file_content);
        $token_array=explode(' ',$file_content);
        return $token_array;
    }
    /*Stemming ：Porter’s algorithm
    算法实现过程：
        第一步，处理复数，以及ed和ing结束的单词。
        第二步，如果单词中包含元音，并且以y结尾，将y改为i。
        第三步，将双后缀的单词映射为单后缀。
        第四步，处理-ic-，-full，-ness等等后缀。
        第五步，在<c>vcvc<v>情形下，去除-ant，-ence等后缀。
        第六步，也就是最后一步，在m()>1的情况下，移除末尾的“e”。
    算法使用说明：
       传入的单词必须是小写
    参考学习网站：
        http://tartarus.org/~martin/PorterStemmer/
        http://snowball.tartarus.org/algorithms/english/stemmer.html
        http://blog.csdn.net/noobzc1/article/details/8902881
     * */
public function stemming($token_array){
    require_once './PorterStemmer.php';
    $p_stemmer = new PorterStemmer();
    $token_stem_array = array();
    foreach($token_array as $token){
        if(!empty($token)){
            $token_stem_array[]= $p_stemmer->Stem( rtrim($token));
        }
    }
    return $token_stem_array;
}

 /* 生成stopword
   思路：搜集网路所有的english版本的stopword list，生成较为全面的stop-word
* */
    public function save_stopword_list(){
        $dir=dirname(__FILE__)."\\stop_words\\english"; //指定文件夹

        $stopword_files_array=array();
        $handle=opendir($dir.".");//遍历文件夹下所有文件
        while (false !== ($file = readdir($handle)) )
        {
            if ($file != "." && $file != "..") {
                $stopword_files_array[]=$file;
            }
        }
        closedir($handle);
       // var_dump( $stopword_files_array); //读取所有stop-word list文件

        $stopwords_english_array= array();
        //逐个处理文件
        foreach( $stopword_files_array as $stopword_files ){

            //读取文件内容
            $file_path=dirname(__FILE__)."\\stop_words\\english\\". $stopword_files;
           //var_dump(  $file_path);

            if(file_exists($file_path)){
                //var_dump(  $file_path);
                $lines=file($file_path);//读取文件内容
                //var_dump($lines);
                foreach ($lines as $line) {
                    if(!in_array( $line, $stopwords_english_array, true)){
                        $stopwords_english_array [] = rtrim($line);
                    }
                }
            }
        }
        $handle_SW=fopen(dirname(__FILE__) . "\\stop_words\\stop_words_english.txt","w");
        ksort($stopwords_english_array);

        foreach( $stopwords_english_array as $stopwords_english ){
            $line_content = $stopwords_english."\r\n";
            fwrite( $handle_SW,$line_content);
        }
        fclose( $handle_SW);
        //var_dump(count( $stopwords_english_array));
    }
/*获取stopword
   思路1：根据stopword list 去除stopword ，为提高准确度，stopword list 尽可能设置很小
   思路2：根据stopword list 设置stopword的weight，在匹配的时候根据权重设置返回结果
* */
public function  get_stopword_list(){
        //读取stopword_list
        $file_path =dirname(__FILE__)."\\stop_words"."\\stop_words_eng.txt";
        $stopwords_en_array= array();
        if(file_exists($file_path)){
            $lines=file($file_path);//读取文件内容
            foreach ($lines as $line) {
                $stopwords_en_array [] = rtrim($line);
            }
        }
        return $stopwords_en_array;
    }
    /*
     *  从文件中获取每个文件每个特异单词的DF
   * */
    public function get_terms_DF(){
        $file_path =dirname(__FILE__)."\\program_result\\tool_file\\doc_term_DF.txt";
        $doc_terms_DF = array();
        if(file_exists($file_path)){
            $file_content=file($file_path);//读取文件内容
            foreach($file_content as $line){
                $line = str_replace(array("\r\n", "\r", "\n"),'',$line);
                $line=explode(" ",$line);
                $doc_terms_DF[$line[0]] = rtrim($line[1]);
            }
            return $doc_terms_DF;
        }
    }
    /*
     *  保存每个特异单词的DF
   * */
    public function save_terms_DF($termDF_array,$sort=1){
        $handle_CF=fopen(dirname(__FILE__) . "\\program_result\\tool_file\\doc_term_DF.txt","w");
        if($sort ==1){
            ksort($termDF_array);
        }
        foreach( $termDF_array as $key=>$value ){
            $line_content = $key." ".$value."\r\n";
            fwrite( $handle_CF,$line_content);
        }
        fclose( $handle_CF);
    }
    /*
*  保存每个文件每个特异单词的CF
* */
    public function save_terms_DF_homework($termDF_array){
        $handle_CF=fopen(dirname(__FILE__) . "\\program_result\\dictionary.txt","w");
        ksort($termDF_array);
        $line_content = "t_index    term   df\r\n";
        fwrite( $handle_CF,$line_content);
        foreach( $termDF_array as $key=>$value ){
            $line_content = hash('crc32',$key)."   ".$key."   ".$value."\r\n";
            fwrite( $handle_CF,$line_content);
        }
        fclose( $handle_CF);
    }
     /*
      *  计算出每个文件每个特异单词的CF，保存在文件中
    * */
    public  function save_terms_CF($origin_file,$file_terms_array,$stopword_array,$termsDF_array){
    $file_name=str_replace('.txt','',$origin_file );//获取docID
    $termsCF_array = array();
    $terms_total=0;
    //逐个单词处理
    foreach($file_terms_array as $file_term ){
        if(!in_array($file_term,$stopword_array,true)){
            //var_dump($file_term);
            if(is_null(@$termsDF_array[$file_term])){
                $termsDF_array[$file_term]=1;
            }else{
                $termsDF_array[$file_term]+=1;
            }
            if(is_null(@$termsCF_array[$file_name][$file_term])){
                $termsCF_array[$file_name][$file_term][]=$file_term;
                $termsCF_array[$file_name][$file_term][]=1;
            }else{
                $termsCF_array[$file_name][$file_term][1]+=1;
            }
            $terms_total+=1;
        }
    }
   // var_dump($termsCF_array);
    $handle_CF=fopen(dirname(__FILE__) . "\\program_result\\tool_file\\doc_term_CF.txt","a");
    foreach( $termsCF_array[$file_name] as $termsCF){
        $line_content = $file_name." ".$termsCF[0]." ".$termsCF[1]."\r\n";
        fwrite( $handle_CF,$line_content);
    }
    $line_content = $file_name."_terms_total ".$terms_total."\r\n";
    fwrite( $handle_CF,$line_content);
    fclose( $handle_CF);
    return $termsDF_array;
}

 /*
  *  从文件中获取每个文件每个特异单词的CF
* */
    public function get_terms_CF(){
        $file_path =dirname(__FILE__)."\\program_result\\tool_file\\doc_term_CF.txt";
        $doc_terms_CF = array();
        if(file_exists($file_path)){
            $file_content=file($file_path);//读取文件内容
            foreach($file_content as $line){
                $line = str_replace(array("\r\n", "\r", "\n"),'',$line);
                $line=explode(" ",$line);
               // var_dump($line);
                if(!strstr($line[0],"_terms_total")){
                    $doc_terms_CF[$line[0]][$line[1]][] = rtrim($line[1]);
                    $doc_terms_CF[$line[0]][$line[1]][] = rtrim($line[2]);
                }else{
                    $doc_terms_CF[$line[0]]= rtrim($line[1]);
                }
            }
            //var_dump( $doc_terms_CF);
            return  $doc_terms_CF;
        }
    }
    /*
     *  计算出每个文件每个特异单词的TF-IDF，并且保存在TXT文件中
     * */
     public function save_terms_TFIDF( $origin_files_array,$termsCF_array,$termsDF_array,$file_total){
         $handle_TFIDF=fopen(dirname(__FILE__) . "\\program_result\\tool_file\\doc_term_TFIDF.txt","a");
         foreach($origin_files_array as $docId){
             $handle=fopen(dirname(__FILE__) . "\\program_result\\".$docId,"w");
             $docId=str_replace('.txt','',$docId );//获取docID
             $docid_terms_total=$termsCF_array[$docId.'_terms_total'];
             $TFIDF_TOTAL =null;
             fwrite( $handle,$docid_terms_total."\r\n");
             fwrite( $handle,"Term TF-IDF\r\n");
             foreach($termsCF_array[$docId] as $termCF){
                 $term_TF=$termCF[1]/$docid_terms_total;
                 $tf_idf_value=$term_TF * log($file_total/$termsDF_array[$termCF[0]]);
                /* $tf_idf_array[$docId][$termCF[0]][]=$termCF[0];
                  $tf_idf_array[$docId][$termCF[0]][]=$tf_idf_value;
                  if( empty($tf_idf_array[$docId]['TFIDF_TOTAL'])){
                     $tf_idf_array[$docId]['TFIDF_TOTAL']=0;
                 }else{
                     $tf_idf_array[$docId]['TFIDF_TOTAL'] += $tf_idf_value*$tf_idf_value;
                 }*/
                 if(is_null($TFIDF_TOTAL)){
                     $TFIDF_TOTAL = 0;
                 }else{
                     $TFIDF_TOTAL += $tf_idf_value*$tf_idf_value;
                 }
                 $line_content=$docId." ".$termCF[0]." ".$tf_idf_value."\r\n";
                 fwrite( $handle_TFIDF,$line_content);
                 fwrite( $handle,$termCF[0]." ".$tf_idf_value."\r\n");
             }
             $line_content=$docId." "."TFIDF_TOTAL"." ". $TFIDF_TOTAL."\r\n";
             fwrite( $handle_TFIDF,$line_content);
             fclose( $handle);
         }
         fclose( $handle_TFIDF);
     }
    /*
  *  计算出每个文件每个特异单词的WF-IDF，并且保存在TXT文件中
  * */
    public function save_terms_WFIDF( $origin_files_array,$termsCF_array,$termsDF_array,$file_total){
        $handle_WFIDF=fopen(dirname(__FILE__) . "\\program_result\\tool_file\\doc_term_WFIDF.txt","a");
        foreach($origin_files_array as $docId){
            $docId=str_replace('.txt','',$docId );//获取docID
            $docid_terms_total=$termsCF_array[$docId.'_terms_total'];
            $WFIDF_TOTAL =null;
            foreach($termsCF_array[$docId] as $termCF){
                $term_TF=$termCF[1]/$docid_terms_total;
                if( $term_TF > 0){
                    $term_WF =1+log( $term_TF) ;
                }else{
                    $term_WF=0;
                }
                $wf_idf_value= $term_WF * log($file_total/$termsDF_array[$termCF[0]]);
             /*   $wf_idf_array[$docId][$termCF[0]][]=$termCF[0];
                $wf_idf_array[$docId][$termCF[0]][]=$wf_idf_value;
                if( empty($tf_idf_array[$docId]['WFIDF_TOTAL'])){
                    $tf_idf_array[$docId]['WFIDF_TOTAL']=0;
                }else{
                    $tf_idf_array[$docId]['WFIDF_TOTAL'] += $wf_idf_value*$wf_idf_value;
                }*/
                if(is_null($WFIDF_TOTAL)){
                    $WFIDF_TOTAL = 0;
                }else{
                    $WFIDF_TOTAL += $wf_idf_value*$wf_idf_value;
                }
                $line_content=$docId." ".$termCF[0]." ".$wf_idf_value."\r\n";
                fwrite( $handle_WFIDF,$line_content);
            }
            $line_content=$docId." "."WFIDF_TOTAL"." ". $WFIDF_TOTAL."\r\n";
            fwrite( $handle_WFIDF,$line_content);
        }
        fclose( $handle_WFIDF);
    }
    /*
      *  读取每个文件每个特异单词的TF-IDF
      * */
    public  function get_terms_TFIDF(){
        $file_path =dirname(__FILE__)."\\program_result\\tool_file\\doc_term_TFIDF.txt";
        $doc_terms_TFIDF = array();
        if(file_exists($file_path)){
            $file_content=file($file_path);//读取文件内容
            foreach($file_content as $line){
                $line = str_replace(array("\r\n", "\r", "\n"),'',$line);
                $line=explode(" ",$line);
                $doc_terms_TFIDF[$line[0]][$line[1]][] = rtrim($line[1]);
                $doc_terms_TFIDF[$line[0]][$line[1]][] = rtrim($line[2]);
            }
          return $doc_terms_TFIDF;
        }
    }
    /*
     *  读取每个文件每个特异单词的WF-IDF
     * */
    public  function get_terms_WFIDF(){
        $file_path =dirname(__FILE__)."\\program_result\\tool_file\\doc_term_WFIDF.txt";
        $doc_terms_WFIDF = array();
        if(file_exists($file_path)){
            $file_content=file($file_path);//读取文件内容
            foreach($file_content as $line){
                $line=explode(" ",$line);
                $doc_terms_WFIDF[$line[0]][$line[1]][] = rtrim($line[1]);
                $doc_terms_WFIDF[$line[0]][$line[1]][] = rtrim($line[2]);
            }
            return $doc_terms_WFIDF;
        }
    }
    /*
  *  计算出两个文件的相似度,采用TFIDF
  * */
    public function cosine_TFIDF($docID1,$docID2){
       // var_dump($docID1);
        $inner_product =0;
        foreach($docID1 as $docID1_terms_IDF){
           if((!@is_null($docID2[ $docID1_terms_IDF[0]]))&&($docID2[ $docID1_terms_IDF[0]]!='TFIDF_TOTAL')){
               $inner_product +=$docID1_terms_IDF[1]*$docID2[ $docID1_terms_IDF[0]][1];
               // print_r($inner_product."   ".$docID1_terms_IDF[1]."    ".$docID2[ $docID1_terms_IDF[0]][1]);
           }
        }
        $cosine =  $inner_product/(sqrt($docID1['TFIDF_TOTAL'][1])*sqrt($docID2['TFIDF_TOTAL'][1]));
        return $cosine;
    }
    /*
*  计算出两个文件的相似度，根据WFIDF
* */
    public function cosine_WFIDF($docID1,$docID2){
        $inner_product =0;
        foreach($docID1 as $docID1_terms_IDF){
            if((!@is_null($docID2[ $docID1_terms_IDF[0]]))&&($docID2[ $docID1_terms_IDF[0]]!='TFIDF_TOTAL')){
                $inner_product +=$docID1_terms_IDF[1]*$docID2[ $docID1_terms_IDF[0]][1];
                // print_r($inner_product."   ".$docID1_terms_IDF[1]."    ".$docID2[ $docID1_terms_IDF[0]][1]);
            }
        }
        $cosine =  $inner_product/(sqrt($docID1['WFIDF_TOTAL'][1])*sqrt($docID2['WFIDF_TOTAL'][1]));
        return $cosine;
    }

} 