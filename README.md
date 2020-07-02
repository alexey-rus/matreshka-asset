# Matreshka asset

 Combining and managing css and js files.
 
 Basic usage:
 ~~~
 Asset::getInstance()->addJs('/js/file.js')
 Asset::getInstance()->addCss('/css/file.css')
~~~  

 You can set the sorting for files: 
~~~
 Asset::getInstance()->addJs('/js/file.js', 1)
~~~
