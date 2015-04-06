<?php
$post_ids = $_REQUEST['post_id'];
if(isset($_REQUEST['action']) && $_REQUEST['action'] == 'export') {
    if(isset($_REQUEST['format']) && $_REQUEST['format'] == 'pdf'){

        $post_contents = aspose_import_export_array_builder($post_ids);
        if ( is_array($post_contents) && count($post_contents) > 0 ) {
            $file_name = aspose_import_export_array_to_html($post_contents);
            include_once('asposePdfConverter.php');

       } else {
            wp_die( __('Error exporting post.') );
        }
    } else {
        $post_contents = aspose_import_export_array_builder($post_ids);
        if ( is_array($post_contents) && count($post_contents) > 0 ) {
            $file_name = aspose_import_export_array_to_html($post_contents);
            include_once('asposeDocConverter.php');

        } else {
            wp_die( __('Error exporting post.') );
        }

    }

    
    $file = $result;

    $file_arr = explode('/',$file);

    $file_name = $file_arr[count($file_arr) - 1];

    header ("Content-type: octet/stream");

    header ("Content-disposition: attachment; filename=".$file_name.";");

    header("Content-Length: ".filesize($file));

    readfile($file);

    exit;
}