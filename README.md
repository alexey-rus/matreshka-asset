# Matreshka asset

 Combining and managing css and js files.
 
 Basic usage:
 ~~~
use MatreshkaAsset\Asset;
$asset = Asset::getInstance();
//Add js files
$asset->addJs('/js/jquery-3.4.1.js');
$asset->addJs('/js/owl.carousel.js');
$asset->addJs('/js/jquery.validate.js');

//Combine
try {
    $jsInclude = $asset->renderJs();
} catch (Exception $e) {
    //Handle errors
}
//Display html script tag including combined file
echo $jsInclude; 

//Add css files
$asset->addCss('/css/styles.css');
$asset->addCss('/css/custom.css');
try {
    $cssInclude = $asset->renderCss();
} catch (Exception $e) {
    //Handle errors
}

//Display link tag including combined file
echo $cssInclude;
~~~  

 You can set the sorting for files: 
~~~
 Asset::getInstance()->addJs('/js/file.js', 1)
~~~
