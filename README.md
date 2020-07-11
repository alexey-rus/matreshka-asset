# Matreshka asset

 Combining and managing css and js files.
 
 Basic usage:
 ~~~
use MatreshkaAsset\Asset;
$asset = Asset::getInstance();

//Add js files
$asset->addJs('/js/jquery.js');
$asset->addJs('/js/main.js');
$asset->addJs('/js/page.js');

//Combine resources
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

//Combine resources
try {
    $cssInclude = $asset->renderCss();
} catch (Exception $e) {
    //Handle errors
}

//Display link tag including combined file
echo $cssInclude;
~~~  

 You can set the order for files, for example if you need to include jquery library before any other files:
~~~
 Asset::getInstance()->addJs('/js/jquery.js', -1)
~~~
