# Matreshka asset
 Combining and managing css and js files. 
## Installation
~~~
composer require alexey-rus/matreshka-asset
~~~
## Basic usage:
 ~~~
use MatreshkaAsset\Asset;
$asset = Asset::getInstance();

//Add js files
$asset->addJs('/js/jquery.js');
$asset->addJs('/js/main.js');
$asset->addJs('/js/page.js');

//Combine js resources
try {
    $jsFiles = $asset->combineJs();
} catch (Exception $e) {
    //Handle errors
}

//Display html script tags including combined file
echo $jsFiles; 

//Add css files
$asset->addCss('/css/styles.css');
$asset->addCss('/css/custom.css');

//Combine css resources
try {
    $cssFiles = $asset->combineCss();
} catch (Exception $e) {
    //Handle errors
}

//Display link tag including combined file
echo $cssInclude;
~~~  
## File order
 You can set the order for files (from lowest to highest), for example if you need to include jquery library before any other files:
~~~
 Asset::getInstance()->addJs('/js/jquery.js', -1)
~~~
## Include minified files
If you have minified version for file (e.g. main.js => main.min.js), it will be automatically included instead of original file
~~~
 //It will check if main.min.js file exist
 Asset::getInstance()->addJs('/js/main.js') 
~~~