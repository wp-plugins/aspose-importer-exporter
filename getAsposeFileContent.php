<?php

/*
 * Including the sdk of php
 */


use Aspose\Cloud\Common\AsposeApp;
use Aspose\Cloud\Common\Product;
use Aspose\Cloud\Common\Utils;
use Aspose\Cloud\Storage\Folder;
use Aspose\Cloud\Pdf\TextEditor;
use Aspose\Cloud\Pdf\Converter as PdfConverter;
use Aspose\Cloud\Words\Extractor;
use Aspose\Cloud\Words\Converter as WordsConverter;


function my_autoloader($class) {
    $allowed_namespace = array('AsposeApp','Product','Folder','Converter','Utils','TextEditor','Extractor');
    $arr = explode('\\', $class);
    if( in_array( $arr['3'] , $allowed_namespace)){
        include 'Aspose_Cloud_SDK_For_PHP-master/src/'. $arr[0] . '/' . $arr[1] . '/' .$arr[2] . '/' . $arr[3] . '.php';
    }

}

spl_autoload_register('my_autoloader');

$path_url = $_SERVER['DOCUMENT_ROOT'];

$arr = explode('/',$_SERVER['SCRIPT_NAME']);

$folder = '';

foreach($arr as $a) {

    if($a != 'wp-content') {

        $folder .= $a . '/';

    } else {

        break;

    }

}

$full_path = $path_url . $folder;

include(''.$full_path.'wp-config.php');
/*
 *  Assign appSID and appKey of your Aspose App
 */
AsposeApp::$appSID = $_REQUEST['appSID'];
AsposeApp::$appKey = $_REQUEST['appKey'];

/*
 * Assign Base Product URL
 */
Product::$baseProductUri = 'http://api.aspose.com/v1.1';
$filename = $_REQUEST['filename'];


$ext = pathinfo($filename, PATHINFO_EXTENSION);

$ext = strtolower($ext);

if($ext == 'pdf' || $ext == 'doc' || $ext == 'docx') {
    $uploadpath = $_REQUEST['uploadpath'];
    $uploadpath = str_replace("\\","/",$uploadpath);
    $uploadpath = $uploadpath . '\\';

    AsposeApp::$outPutLocation = $uploadpath;

    if(!isset($_REQUEST['aspose'])) {

        $folder = new Folder();
        $uploadpath = str_replace("\\","/",$uploadpath);
        $uploadFile = $uploadpath .  $filename;
        $folder->uploadFile($uploadFile, '');
    }

    if($ext == 'pdf'){

        if($_REQUEST['content_type'] == 'text') {
            $filename = trim($filename);
            $func = new TextEditor($filename);

            $output = $func->getText();


            $output_arr = explode('.',$output);
            $content = '';
            foreach($output_arr as $output){
                $content .= '<p>' . $output . '</p>';
            }

        } else {
            $converter = new PdfConverter($filename);
            $converter->saveFormat = 'html';
            $saved_file = $converter->convert();
            $targetdir_arr = explode('\\',$saved_file);

            $dir_count = count($targetdir_arr);

            unset($targetdir_arr[$dir_count]);
            $targetdir_arr[$dir_count-1] = str_replace(".zip","",Utils::getFileName($filename)).'/';

            $targetdir = implode('/',$targetdir_arr);
            Unzip($targetdir,$saved_file);
            $file_name = str_replace(".zip","",$saved_file) . '/' . Utils::getFileName($filename) . '.html';
            $file_name = str_replace("\\","/",$file_name);
            $file_html = file_get_contents($file_name);
            $file_html_folder = Utils::getFileName($filename) . '_files';
            $wp_dir = wp_upload_dir();

            $destination = $wp_dir['url'];

            $file_html = str_replace($file_html_folder,$destination.'/'.Utils::getFileName($filename).'/'.$file_html_folder,$file_html);

            $css_file = file_get_contents($destination.'/'.Utils::getFileName($filename).'/'.$file_html_folder.'/style.css');

            $css_array = parse($css_file);

            preg_match_all('/class="([^"]+)"/i',$file_html,$matches);

            foreach($matches[1] as $key=>$class_name){

                $classes_arr = explode(' ',$class_name);
                $style_text = '';
                if(is_array($classes_arr) && count($classes_arr) > 0){

                    foreach($classes_arr as $c){
                        $key_index = $css_array['.'.$c];

                        if(is_array($css_array['.'.$c])){
                            foreach($css_array['.'.$c] as $style_key=>$style_value) {

                                if($style_key!='' && $style_value !='' && $style_key!='font-family'){
                                    $style_text .= $style_key .':' . $style_value . ';';
                                }
                            }
                        }



                    }
                }
                if($style_text != ''){
                    $replace_string = 'style="'.$style_text.'"';
                    $c_n = 'class="'.$class_name.'"';
                    $file_html = str_replace($c_n,$replace_string,$file_html);
                }
            }
            preg_match_all('/style="([^"]+)" style="([^"]+)"/',$file_html,$match_arr);

            foreach($match_arr['0'] as $key=>$m){
                $file_html = str_replace($m,'style="'.$match_arr['1'][$key] . $match_arr['2'][$key] .'"',$file_html);
            }
            $content = $file_html;
        }

    } else if ($ext == 'doc' || $ext == 'docx') {

        $func = new Extractor($filename);
        $output_arr = $func->getText();
        $content = '';
        foreach($output_arr as $output){
            $content .= '<p>' . $output->Text . '</p>';
        }

    }


    echo $content;
} else {
    echo "Wrong File was selected!";
}

function Unzip($dir, $file, $destiny="")
{
    $dir .= DIRECTORY_SEPARATOR;
    $path_file = $file;
    $zip = zip_open($path_file);
    $_tmp = array();
    $count=0;
    if ($zip)
    {
        while ($zip_entry = zip_read($zip))
        {
            $_tmp[$count]["filename"] = zip_entry_name($zip_entry);
            $_tmp[$count]["stored_filename"] = zip_entry_name($zip_entry);
            $_tmp[$count]["size"] = zip_entry_filesize($zip_entry);
            $_tmp[$count]["compressed_size"] = zip_entry_compressedsize($zip_entry);
            $_tmp[$count]["mtime"] = "";
            $_tmp[$count]["comment"] = "";
            $_tmp[$count]["folder"] = dirname(zip_entry_name($zip_entry));
            $_tmp[$count]["index"] = $count;
            $_tmp[$count]["status"] = "ok";
            $_tmp[$count]["method"] = zip_entry_compressionmethod($zip_entry);

            if (zip_entry_open($zip, $zip_entry, "r"))
            {
                $buf = zip_entry_read($zip_entry, zip_entry_filesize($zip_entry));
                if($destiny)
                {
                    $path_file = str_replace("/",DIRECTORY_SEPARATOR, $destiny . zip_entry_name($zip_entry));
                }
                else
                {
                    $path_file = str_replace("/",DIRECTORY_SEPARATOR, $dir . zip_entry_name($zip_entry));
                }
                $new_dir = dirname($path_file);

                // Create Recursive Directory

                @mkdir($new_dir);


                $fp = @fopen($dir . zip_entry_name($zip_entry), "w");
                @fwrite($fp, $buf);
                @fclose($fp);

                @zip_entry_close($zip_entry);
            }

            $count++;
        }

        zip_close($zip);
    }
}

function parse($css){
    //$css = file_get_contents($file);
    preg_match_all( '/(?ims)([a-z0-9\s\.\:#_\-@,]+)\{([^\}]*)\}/', $css, $arr);
    $result = array();
    foreach ($arr[0] as $i => $x){
        $selector = trim($arr[1][$i]);
        $rules = explode(';', trim($arr[2][$i]));
        $rules_arr = array();
        foreach ($rules as $strRule){
            if (!empty($strRule)){
                $rule = explode(":", $strRule);
                $rules_arr[trim($rule[0])] = trim($rule[1]);
            }
        }

        $selectors = explode(',', trim($selector));
        foreach ($selectors as $strSel){
            $result[$strSel] = $rules_arr;
        }
    }
    return $result;
}
