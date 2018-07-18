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
	if(file_exists($image_url)){
	echo '<p>Your photo don\'t exist</p>';

    $image = $image_url;


    $get = wp_remote_get($image);

    $type = wp_remote_retrieve_header($get, 'content-type');


    if (!$type) {
        return false;
    }

    $mirror = wp_upload_bits(basename($image), '', wp_remote_retrieve_body($get));



    $attachment = array(
        'post_title' => basename($image),
        'post_mime_type' => $type
    );

    $attach_id = wp_insert_attachment($attachment, $mirror['file'], $post_id);

    echo '<br> inner photo: ';
	print_r($attach_id);

    require_once(ABSPATH . 'wp-admin/includes/image.php');

    $attach_data = wp_generate_attachment_metadata($attach_id, $mirror['file']);

    wp_update_attachment_metadata($attach_id, $attach_data);
    
    // clear old thumbnail
    if(has_post_thumbnail($post_id)){
	wp_delete_attachment ( get_post_thumbnail_id($post_id), true ); 
	}

    set_post_thumbnail($post_id, $attach_id);

    return $attach_id;
}else{
	echo '<p>Photo don\'t exist...</p>';
}
}

?>
<script src="/wp-content/themes/medotzovik/main.js"></script>
<style type="text/css">

	#holder {
		font-family: Arial;
		width: 500px;
		margin: 100px auto;
		text-align:center;
	}
	
	#output {
		padding-top: 20px;
		font-size: 11px;
	}

	a.requestIntegration{
		
		padding: 5px 15px;
		color: #fff;
		text-decoration: none;
		display: inline-block;
		margin: 3px 15px;
		border: 1px solid #1E5799;
		border-radius: 5px;

/* Permalink - use to edit and share this gradient: http://colorzilla.com/gradient-editor/#1e5799+0,2989d8+50,7db9e8+100 */
background: #1e5799; /* Old browsers */
background: -moz-linear-gradient(-45deg, #1e5799 0%, #2989d8 50%, #7db9e8 100%); /* FF3.6-15 */
background: -webkit-linear-gradient(-45deg, #1e5799 0%,#2989d8 50%,#7db9e8 100%); /* Chrome10-25,Safari5.1-6 */
background: linear-gradient(135deg, #1e5799 0%,#2989d8 50%,#7db9e8 100%); /* W3C, IE10+, FF16+, Chrome26+, Opera12+, Safari7+ */
filter: progid:DXImageTransform.Microsoft.gradient( startColorstr='#1e5799', endColorstr='#7db9e8',GradientType=1 ); /* IE6-9 fallback on horizontal gradient */
	}
	a.requestIntegration:hover{
		/* Permalink - use to edit and share this gradient: http://colorzilla.com/gradient-editor/#1e5799+0,2989d8+50,7db9e8+100 */
background: #1e5799; /* Old browsers */
background: -moz-linear-gradient(45deg, #1e5799 0%, #2989d8 50%, #7db9e8 100%); /* FF3.6-15 */
background: -webkit-linear-gradient(45deg, #1e5799 0%,#2989d8 50%,#7db9e8 100%); /* Chrome10-25,Safari5.1-6 */
background: linear-gradient(45deg, #1e5799 0%,#2989d8 50%,#7db9e8 100%); /* W3C, IE10+, FF16+, Chrome26+, Opera12+, Safari7+ */
filter: progid:DXImageTransform.Microsoft.gradient( startColorstr='#1e5799', endColorstr='#7db9e8',GradientType=1 ); /* IE6-9 fallback on horizontal gradient */
	}
</style>
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
	ORDER BY ProductID ASC");
 while($productsList_array = mysqli_fetch_array($productsList)){

// variables for change
$preparationsId = '';
$photo_URL = '';
$PregnancyUsing = '';
$ChildInsufUsing = '';
$NonPrescriptionDrug = '';
$substance = '';
$atx = '';
$Composition = '';
$pharmacological_action = '';
$Dosage = '';
$SideEffects = '';
$Indication = '';
$Interaction = '';

echo"<pre>";
//var_dump($productsList_array);
echo '<br>' . $productsList_array['RusName'];



echo '-- '; 	
//var_dump($productsList_array['RegistrationNumber']);

if($productsList_array['RegistrationNumber']){
$preparationsId = $productsList_array['RegistrationNumber'];
}else{$preparationsId = '';}
 
// NonPrescriptionDrug
if($productsList_array['NonPrescriptionDrug'] == 1){
	$NonPrescriptionDrug = 3;
} else {
	$NonPrescriptionDrug = 1;
} 
// Composition
if($productsList_array['Composition'] != ''){
	$Composition = $productsList_array['Composition'];
}



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
 		$PregnancyUsing = 1;
 	} else if($document_array['PregnancyUsing'] == 'Care'){
 		$PregnancyUsing = 2;
 	} else if($document_array['PregnancyUsing'] == 'Can'){
 		$PregnancyUsing = 3;
 	}


// ChildInsufUsing
 	if($document_array['ChildInsufUsing'] == 'Not'){
 		$ChildInsufUsing = 1;
 	} else if($document_array['ChildInsufUsing'] == 'Care'){
 		$ChildInsufUsing = 2;
 	} else if($document_array['ChildInsufUsing'] == 'Can'){
 		$ChildInsufUsing = 3;
 	}

 // pharmacological action
 	if($document_array['PhInfluence'] != ''){
 		$pharmacological_action = $document_array['PhInfluence'];
 	} 
 // Dosage
 	if($document_array['Dosage'] != ''){
 		$Dosage = $document_array['Dosage'];
 	} 
 // SideEffects
 	if($document_array['SideEffects'] != ''){
 		$SideEffects = $document_array['SideEffects'];
 	} 
 // Interaction
 	if($document_array['Interaction'] != ''){
 		$Interaction = $document_array['Interaction'];
 	} 
 // Indication
 	if($document_array['Indication'] != ''){
 		$Indication = $document_array['Indication'];
 	} 

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

// got postID with substance
$substance = post_exists(wp_slash($substance_array['RusName'])); 
//echo '<p>Substance: '. $substance_array['RusName'] . '</p>';
 }

 // get ATX information 
$ATXRequest = "SELECT ATCCode FROM Product_ATC 
where ProductID = '".$productsList_array['ProductID']."'";

$Product_ATC = mysqli_query($dbRes, $ATXRequest);
 while($ATX_array = mysqli_fetch_array($Product_ATC)){

// got postID with substance
$atx = post_exists(wp_slash($ATX_array['ATCCode'])); 
echo '<p>atx: '. $ATX_array['ATCCode'] . '</p>';
 }


// array of preparat
$post_data = array(
	'post_title'    => wp_strip_all_tags( $productsList_array['RusName'] ),
	'post_status'   => 'publish',
	'post_type' => 'preparations'
);


echo '<p>atx: '. $productsList_array['Composition'] . '</p>';




// add new preparation
if(!$isPostExist){
$post_id = wp_insert_post( $post_data );
echo '<br> adding new preparation: ' . $isPostExist;
add_post_meta( $post_id, 'DBRegistrationNumber', $preparationsId, true );

if($PregnancyUsing != ''){
 add_post_meta( $post_id, '_sj_product_pregnancy', $PregnancyUsing, true );
}

if($ChildInsufUsing != ''){
 add_post_meta( $post_id, '_sj_product_children', $ChildInsufUsing, true );
}

if($NonPrescriptionDrug != ''){
 add_post_meta( $post_id, '_sj_product_prescription', $NonPrescriptionDrug, true );
}

if($substance != ''){
 add_post_meta( $post_id, '_sj_substance', $substance, true );
}
if($atx != ''){
 add_post_meta( $post_id, '_sj_atx', $atx, true );
}
if($Composition != ''){
 add_post_meta( $post_id, '_sj_add_info|sj_composition|0|0|value', wp_strip_all_tags($Composition), true );
}
if($pharmacological_action != ''){
	 add_post_meta( $post_id, '_sj_add_info|sj_pharmacological_action|0|0|value', wp_strip_all_tags($pharmacological_action), true );
	}

if($Dosage != ''){
	 add_post_meta( $post_id, '_sj_add_info|sj_method|0|0|value', wp_strip_all_tags($Dosage), true );
	}
if($SideEffects != ''){
	 add_post_meta( $post_id, '_sj_add_info|sj_side_effects|0|0|value', wp_strip_all_tags($SideEffects), true );
	}
if($Indication != ''){
	 add_post_meta( $post_id, '_sj_add_info|sj_indications|0|0|value', wp_strip_all_tags($Indication), true );
	}
if($Interaction != ''){
	 add_post_meta( $post_id, '_sj_add_info|sj_interaction|0|0|value', wp_strip_all_tags($Interaction), true );
	}

if($photo_URL != ''){

	upload_preparation_file('http://otabletkah.ru/wp-content/uploads/dbintimages/'.$photo_URL, $post_id);
//add_post_meta( $post_id, 'photo_URL', 'http://otabletkah.ru/wp-content/uploads/'.$photo_URL, true );
}
}else {
	$post_data['ID'] =$isPostExist;
	wp_update_post($post_data);
	echo '<br> preparation #' . $isPostExist . ' existed';

	update_post_meta( $isPostExist, 'DBRegistrationNumber', $preparationsId );

	if($PregnancyUsing != ''){
	 update_post_meta( $isPostExist, '_sj_product_pregnancy', $PregnancyUsing );
	}

	if($ChildInsufUsing != ''){
	 update_post_meta( $isPostExist, '_sj_product_children', $ChildInsufUsing );
	}

	if($NonPrescriptionDrug != ''){
	 update_post_meta( $isPostExist, '_sj_product_prescription', $NonPrescriptionDrug );
	}

	if($substance != ''){
	 update_post_meta( $isPostExist, '_sj_substance', $substance );
	}

	if($atx != ''){
	 update_post_meta( $isPostExist, '_sj_atx', $atx );
	}

	if($Composition != ''){
	 update_post_meta( $isPostExist, '_sj_add_info|sj_composition|0|0|value', wp_strip_all_tags($Composition) );
	}
	if($pharmacological_action != ''){
	 update_post_meta( $isPostExist, '_sj_add_info|sj_pharmacological_action|0|0|value', wp_strip_all_tags($pharmacological_action) );
	}
	if($Dosage != ''){
	 update_post_meta( $isPostExist, '_sj_add_info|sj_method|0|0|value', wp_strip_all_tags($Dosage) );
	}
	if($SideEffects != ''){
	 update_post_meta( $isPostExist, '_sj_add_info|sj_side_effects|0|0|value', wp_strip_all_tags($SideEffects) );
	}
	if($Indication != ''){
	 update_post_meta( $isPostExist, '_sj_add_info|sj_indications|0|0|value', wp_strip_all_tags($Indication) );
	}
	if($Interaction != ''){
	 update_post_meta( $isPostExist, '_sj_add_info|sj_interaction|0|0|value', wp_strip_all_tags($Interaction) );
	}

	if($photo_URL != ''){
		upload_preparation_file('http://otabletkah.ru/wp-content/uploads/dbintimages/'.$photo_URL, $isPostExist);
		//update_post_meta( $isPostExist, 'photo_URL', 'http://otabletkah.ru/wp-content/uploads/'.$photo_URL );
	}
}

// add marker for DB resurs
update_post_meta( $isPostExist, 'DB_resurs', 'DB1' );
// activity mode
update_post_meta( $isPostExist, 'activityMode', 'active' );

echo"</pre>";
 


 } // end of while
}
##########################
# END of products integration


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
    unlink($dbintimages . $srcFileName);
    echo '<p>File deleted</p>';
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