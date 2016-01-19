<?php
/**
 * Created by PhpStorm.
 * User: Happy
 * Date: 2015/9/26
 * Time: 9:58
 */

//依次读取指定文件夹下的所有文件名
$dir=dirname(__FILE__)."\\origin_file"; //指定文件夹
$handle=opendir($dir.".");//遍历文件夹下所有文件
while (false !== ($file = readdir($handle)))
{
    if ($file != "." && $file != "..") {
        $array_file[] =$dir."\\".$file; //输出文件名
    }
}
closedir($handle);

//依次读取指定文件夹下的所有文件的内容
foreach(  $array_file as $file_path){
    $file_name = str_replace($dir."\\",'',$file_path);
    $file_content=file_get_contents($file_path);//读取文件内容
   var_dump( "ORIGIN:  ". $file_content);

   /* Tokenization 原理：根据句子结构标示符破除句子结构
      将句子结构标示符去掉，如 ，. ! ：; ?
      进阶考虑：r13579@ntu.edu.tw  r13579, ntu, edu, tw 关于 "."的处理问题
    * */
    $tokenization_array = array(
        //英语标点符号 半角
        "," => "",
        "." => "",
        "?" => "",
        "!" => "",
        ":" => "",
        ";" => "",
       //英语标点符号 全角
        "，" => "",
        "．" => "",
        "？" => "",
        "！" => "",
        "：" => "",
        "；" => "",

        //中文标点符号 半角与全角
        "，" => "",
        "。" => "",
        "？" => "",
        "！" => "",
        "：" => "",
        "；" => "",
        "、" => "",
    );
    $file_content = strtr($file_content, $tokenization_array);//strtr 对大小写敏感
    //var_dump("TOKEN:  ".  $file_content);

    /*Lowercasing ：将所有大写字母变成小写字母
     进阶考虑：专有名词的大小写匹对准确性问题
     变化参考：http://www.jb51.net/article/49629.htm
    * */
    $file_content = strtolower( $file_content);
    //var_dump("LOWERCASE:  ".  $file_content);

    /*Normalization ：去除() <> [] {} ‘s,并将字符串拆分成单词数组
   进阶考虑：组合词的连接格形式，如 ~ ` - _
    the hold-him-back-and-drag-him-away manner.
  * */
    $normalization = array(
        //英文半角符号
        "(" => " ",
        ")" => " ",
        "<" => " ",
        ">" => " ",
        "[" => " ",
        "]" => " ",
        "'s" => "",
        "{" => " ",
        "}" => " ",
        //英文全角符号
        "（" => " ",
        "）" => " ",
        "＜" => " ",
        "＞" => " ",
        "［" => " ",
        "］" => " ",
        "＇ｓ" => "",
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
        "}" => " "
    );
    $file_content = strtr($file_content,$normalization);//strtr 对大小写敏感
   // var_dump("Normalize:  ".  $file_content);
    $file_content = str_replace(array("\r\n", "\r", "\n"),'',$file_content);
    $token_array=explode(' ',$file_content);
    //var_dump($token_array);

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
    require_once './PorterStemmer.php';
    $p_stemmer = new PorterStemmer();
    foreach($token_array as $token){
        $token_stem_array[]= $p_stemmer->Stem( rtrim($token));
    }
   // $token_stem_array[]= $p_stemmer->Stem( rtrim('news'));//特定单词监测
   //var_dump($token_stem_array);

    /*Stopword removal,并消除重复的关键字
    思路1：根据stopword list 去除stopword ，为提高准确度，stopword list 尽可能设置很小
    思路2：根据stopword list 设置stopword的weight，在匹配的时候根据权重设置返回结果
    * */
    //读取stopword_list
    $stopwords_en_file =dirname(__FILE__)."\\stop_words"."\\stop_words_eng.txt";
    $lines=file($stopwords_en_file);//读取文件内容
    foreach ($lines as $line) {
        $stopwords_en_array [] = rtrim($line);
    }
   //  var_dump($stopwords_en_array);

    //Stopword removal:
    $term_array = array();
   foreach($token_stem_array as $token_stem){
       if(!in_array($token_stem,$stopwords_en_array,true)){
           //distinct
           if(!in_array($token_stem, $term_array,true)){
               $term_array[] = $token_stem;
               $term_with_array[] = $token_stem."\r\n";
           }
       }
   }
   var_dump($term_array);

    /*Save the result as a txt file.
     * */
    file_put_contents(dirname(__FILE__)."\\program_result\\result_".$file_name, $term_with_array);




}
