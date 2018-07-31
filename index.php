<?php 
/* Examiner page template
 * Template Name: content Checker
 * Description : content Checker
 */
require_once 'header.php';

if ( ! function_exists( 'post_exists' ) ) {
    require_once( ABSPATH . 'wp-admin/includes/post.php' );
}

// clear the directory
if (!function_exists('cleanDir')) {
function cleanDir($dir) {

$files = array_diff(scandir($dir), array('.','..'));
foreach ($files as $file) {
(is_dir("$dir/$file")) ? cleanDir("$dir/$file") : unlink("$dir/$file");
}
rmdir($dir);
mkdir($dir, 0700);
echo '<p>Files in '.$dir.' deleted</p>';
}
}
// get post ID by meta key/value
if (!function_exists('get_post_id_by_meta_key_and_value')) {
	function get_post_id_by_meta_key_and_value($key, $value) {
		global $wpdb;
		$meta = $wpdb->get_results("SELECT * FROM `".$wpdb->postmeta."` WHERE meta_key='".$wpdb->escape($key)."' AND meta_value='".$wpdb->escape($value)."'");
		if (is_array($meta) && !empty($meta) && isset($meta[0])) {
			$meta = $meta[0];
		}		
		if (is_object($meta)) {
			return $meta->post_id;
		}
		else {
			return false;
		}
	}
}  

// image to library
function upload_preparation_file($image_url, $post_id)
{
	if(file_exists($_SERVER["DOCUMENT_ROOT"] . $image_url)){
    // upload file from own server...
    // temporary part, need to change for time optimisation
    $get = wp_remote_get(site_url() . $image_url);
    $type = wp_remote_retrieve_header($get, 'content-type');
    if (!$type) return false;

    $mirror = wp_upload_bits(basename(site_url() . $image_url), '', wp_remote_retrieve_body($get));

    $attachment = array(
        'post_title' => basename(site_url() . $image_url),
        'post_mime_type' => $type
    );
    
    // create media file
    $attach_id = wp_insert_attachment($attachment, $mirror['file'], $post_id);

    require_once(ABSPATH . 'wp-admin/includes/image.php');
    $attach_data = wp_generate_attachment_metadata($attach_id, $mirror['file']);

    wp_update_attachment_metadata($attach_id, $attach_data);
    
    // clear old thumbnail
    if(has_post_thumbnail($post_id)){
	wp_delete_attachment ( get_post_thumbnail_id($post_id), true ); 
	}

    set_post_thumbnail($post_id, $attach_id);
    echo '<p>Photo path: "' .$image_url.'"</p>';

    return $attach_id;
}else{
	echo '<p>Photo "'.$image_url.'" don\'t exist...</p>';
}
} // end of photo function


// add metadata to preparats
function addPreparatusMeta($preparatusMetaList, $post_id){
 
 foreach ($preparatusMetaList as $meta => $metaValue) {
 if($metaValue != ''){
 	add_post_meta( $post_id, $meta, $metaValue, true );
 	echo '<p> metadata: ' . $metaValue . '</p>';
 }}
}

// update metadata to preparats
function updatePreparatusMeta($preparatusMetaList, $post_id){
 
 foreach ($preparatusMetaList as $meta => $metaValue) {
 if($metaValue != ''){
 	update_post_meta( $post_id, $meta, $metaValue );
 	echo '<p> metadata: ' . $metaValue . '</p>';
 }}
}

?>
<script src="<?php echo get_template_directory_uri(); ?>/js/main.js"></script>
<link rel="stylesheet" href="<?php echo get_template_directory_uri(); ?>/css/preparatus.css?ver=1.15">
<h3>Предварительная подготовка</h3>
<ul>

	<li><a href="?atxint=1" class="requestIntegration">ATX интеграция</a></li>
	<li><a href="?substanceint=1" class="requestIntegration">интеграция списка веществ</a></li>
</ul>
<h3>Работа с изображениями</h3>
<ul>
	<li><a href="?cleanDir=1" class="requestIntegration">Очистить директорию для загрузки фото</a></li>
</ul>

<form action="" method="post" enctype="multipart/form-data">
    <input type="file" name="attachment" value="обзор">
    <input type="submit" value="загрузить">
</form>

<h3>Интеграция списка товаров</h3>
<ul>
	<li><a href="?preparationsint=1" class="requestIntegration">интеграция препаратов</a></li>
</ul>
<?php

/**
*
*/
// mysqladmin ("-h 194.28.172.92 -u torpedo03_2 -p 111111") create torpedo03_2
$dbRes = mysqli_connect("194.28.172.92", "torpedo03_1", "111111") or die ("No connect to database-home Smile");
mysqli_select_db($dbRes, "torpedo03_1") or die("Could not select database");
$query='SET NAMES utf8';
$res = mysqli_query($dbRes, $query);
$dbintimages =$_SERVER["DOCUMENT_ROOT"] . '/wp-content/uploads/dbintimages/';

##########################
# products integration
if($_GET['preparationsint']){
$productsList = mysqli_query($dbRes, "SELECT RusName, RegistrationNumber, ProductID, NonPrescriptionDrug,Composition
	FROM Product GROUP by EngName 
	ORDER BY ProductID ASC limit 40");
 while($productsList_array = mysqli_fetch_array($productsList)){

// array for preparation data
$preparatusMetaList = array();

echo"<pre>";
//var_dump($productsList_array);
echo '<p>' . $productsList_array['RusName'] . '</p>';


   $preparatusMetaList['DBRegistrationNumber']= $productsList_array['RegistrationNumber'];
 
// NonPrescriptionDrug
if($productsList_array['NonPrescriptionDrug'] == 1){
   $preparatusMetaList['_sj_product_prescription']= 3;
} else {
   $preparatusMetaList['_sj_product_prescription']= 1;
} 

   $preparatusMetaList['_sj_add_info|sj_composition|0|0|value']= $productsList_array['Composition'];

// test get pos meta function
$isPostExist =  get_post_id_by_meta_key_and_value('DBRegistrationNumber', $preparationsId);


// get additional document information 
$documentRequest = "SELECT PregnancyUsing, ChildInsufUsing, PhInfluence, Dosage, SideEffects, Indication, Interaction FROM Product_Document 
left join Document on Product_Document.DocumentID = Document.DocumentID
where ProductID = '".$productsList_array['ProductID']."'";

$productDocument = mysqli_query($dbRes, $documentRequest);
 while($document_array = mysqli_fetch_array($productDocument)){

// PregnancyUsing
 	if($document_array['PregnancyUsing'] == 'Not'){
         $preparatusMetaList['_sj_product_pregnancy']= 1;
 	} else if($document_array['PregnancyUsing'] == 'Care'){
         $preparatusMetaList['_sj_product_pregnancy']= 2;
 	} else if($document_array['PregnancyUsing'] == 'Can'){
         $preparatusMetaList['_sj_product_pregnancy']= 3;
 	}


// ChildInsufUsing
 	if($document_array['ChildInsufUsing'] == 'Not'){
         $preparatusMetaList['_sj_product_children']= 1;
 	} else if($document_array['ChildInsufUsing'] == 'Care'){
         $preparatusMetaList['_sj_product_children']= 2;
 	} else if($document_array['ChildInsufUsing'] == 'Can'){
         $preparatusMetaList['_sj_product_children']= 3;
 	}


   $preparatusMetaList['_sj_add_info|sj_pharmacological_action|0|0|value']= $document_array['PhInfluence'];
   $preparatusMetaList['_sj_add_info|sj_method|0|0|value']= $document_array['Dosage'];
   $preparatusMetaList['_sj_add_info|sj_side_effects|0|0|value']= $document_array['SideEffects'];
   $preparatusMetaList['_sj_add_info|sj_indications|0|0|value']= $document_array['Indication'];
   $preparatusMetaList['_sj_add_info|sj_interaction|0|0|value']= $document_array['Interaction'];	
}



// get image information 
$imageRequest = "SELECT Path FROM Product_Picture 
left join Picture on Product_Picture.PictureID = Picture.PictureID
where ProductID = '".$productsList_array['ProductID']."'";

$productImage = mysqli_query($dbRes, $imageRequest);
 while($productsImage_array = mysqli_fetch_array($productImage)){
$photo_URL = str_replace( '\\' ,'/', $productsImage_array['Path']);
 }


// get substance information 
$substanceRequest = "SELECT MoleculeName.RusName FROM Product_MoleculeName 
left join MoleculeName on Product_MoleculeName.MoleculeNameID = MoleculeName.MoleculeNameID
where ProductID = '".$productsList_array['ProductID']."'";

$Product_MoleculeName = mysqli_query($dbRes, $substanceRequest);
while($substance_array = mysqli_fetch_array($Product_MoleculeName)){
   $preparatusMetaList['_sj_substance'] = post_exists(wp_slash($substance_array['RusName']));
}

 // get ATX information 
$ATXRequest = "SELECT ATCCode FROM Product_ATC where ProductID = '".$productsList_array['ProductID']."'";

$Product_ATC = mysqli_query($dbRes, $ATXRequest);
while($ATX_array = mysqli_fetch_array($Product_ATC)){
   $preparatusMetaList['_sj_atx'] = post_exists(wp_slash($ATX_array['ATCCode']));
}

// array of preparat
$post_data = array(
	'post_title'    => wp_strip_all_tags( $productsList_array['RusName'] ),
	'post_status'   => 'publish',
	'post_type' => 'preparations'
);


// add new preparation
if(!$isPostExist){

$post_id = wp_insert_post( $post_data );

addPreparatusMeta($preparatusMetaList, $post_id);

  if($photo_URL != ''){
  upload_preparation_file('/wp-content/uploads/dbintimages/'.$photo_URL, $post_id);
  }
}else {
$post_data['ID'] =$isPostExist;
wp_update_post($post_data);
	echo '<br> preparation #' . $isPostExist . ' existed';

	updatePreparatusMeta($preparatusMetaList, $post_id);
  if($photo_URL != ''){
  upload_preparation_file('/wp-content/uploads/dbintimages/'.$photo_URL, $isPostExist);
  }
}

// add marker for DB resurs
update_post_meta( $isPostExist, 'DB_resurs', 'DB1' );
// activity mode
update_post_meta( $isPostExist, 'activityMode', 'active' );

echo"</pre>";

// remove all data of old loop
unset($preparatusMetaList);

 } // end of while
}
##########################
# END of products integration


##########################
# compare data
if($_GET['compare']){
$compare = mysqli_query($dbRes, "SELECT * FROM Product");
	echo '<h2>Good connection</h2>';
    printf("Select returned %d rows.\n", mysqli_num_rows($compare));
    echo '<pre>';
    print_r(mysqli_fetch_array($compare));
    echo '</pre>';
    mysqli_free_result($compare);

}
##########################
# remove preparats
# remove with all metadata
if($_GET['removepreparatus']){
	// array of preparat

$args = array(
	'numberposts' => 100,
	'post_type' => 'preparations'
);
$recent_posts = wp_get_recent_posts( $args, ARRAY_A );
   echo '<ul>';
foreach( $recent_posts as $recent ){
   wp_delete_attachment ( get_post_thumbnail_id($recent["ID"]), true );
   wp_delete_post($recent["ID"], true);
   echo '<li>' . $recent["post_title"] . ' removed...</li> ';
}
   echo '</ul>';
wp_reset_query();
}

##########################
# substance integration
if($_GET['substanceint']){
$substanceList = mysqli_query($dbRes, "SELECT * FROM MoleculeName");


 while($substanceList_array = mysqli_fetch_array($substanceList)){

 	$isPostExist ='';
 	$isPostExist = post_exists(wp_slash($substanceList_array['RusName']));


 	// array of preparat
$post_data = array(
	'post_title'    => wp_strip_all_tags($substanceList_array['RusName']),
	'post_status'   => 'publish',
	'post_type' => 'substance'
);

// add new preparation
if(!$isPostExist){
$post_id = wp_insert_post( $post_data );
} else{
	echo '<p>Post exist</p>';
	$post_data['ID'] =$isPostExist;
	wp_update_post($post_data);
}
 	echo 'substance: ' . $substanceList_array['RusName'] . ' / ' . $substanceList_array['LatName'] . '<br>';
 }// end of while
}

##########################
# END of substance integration


##########################
# Clear images dir
if($_GET['cleanDir']){
cleanDir($dbintimages);
}
##########################
# END of Clear images dir


##########################
# atx integration
if($_GET['atxint']){
$atxList = mysqli_query($dbRes, "SELECT * FROM ATC  ");

if ( ! function_exists( 'post_exists' ) ) {
    require_once( ABSPATH . 'wp-admin/includes/post.php' );
}

 while($atxList_array = mysqli_fetch_array($atxList)){

 	$isPostExist ='';
 	$isPostExist = post_exists(wp_slash($atxList_array['ATCCode']));


 	// array of preparat
$post_data = array(
	'post_title'    => wp_strip_all_tags($atxList_array['ATCCode']),
	'post_content'    => wp_strip_all_tags($atxList_array['RusName']),
	'post_status'   => 'publish',
	'post_type' => 'atx'
);

// add new preparation
if(!$isPostExist){
$post_id = wp_insert_post( $post_data );
} else{
	echo '<p>atx exist</p>';
	$post_data['ID'] =$isPostExist;
	wp_update_post($post_data);
}
 	echo 'atx: ' . $atxList_array['RusName'] . ' / ' . $atxList_array['ATCCode'] . '<br>';
 }// end of while
}

##########################
# END of atx integration

##########################
# Images archive upload

if (!empty($_FILES['attachment'])) {
    $file = $_FILES['attachment'];

    // собираем путь до нового файла - папка uploads в текущей директории
    // в качестве имени оставляем исходное файла имя во время загрузки в браузере
    $srcFileName = $file['name'];
    $newFilePath = $dbintimages . $srcFileName;

    if (!move_uploaded_file($file['tmp_name'], $newFilePath)) {
        $error = 'Ошибка при загрузке файла';
    } else {


    	$zip = new ZipArchive;
if ($zip->open($dbintimages . $srcFileName) === TRUE) {
    $zip->extractTo($dbintimages);
    $zip->close();
    echo '<p>Done</p>';    
    // remove zip file after unpaching
    if(file_exists($newFilePath)){
    unlink($newFilePath);
    echo '<p>File"'.$srcFileName.'" deleted</p>';
    }
} else {
    echo 'Error';
}

    }

}
##########################
# END of Images archive upload
if ( comments_open() ) { 
	?>
	<aside class="comments-block">
		<?php comments_template(); ?>
	</aside>
	<?php 
}
require_once 'footer.php';