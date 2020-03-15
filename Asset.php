<?php
/**
 * Combining assets
 *
 * Please report bugs on https://github.com/alexey-rus/matreshka-asset/issues
 *
 * @author Alexey Usachev <p15623@yandex.ru>
 * @copyright Copyright (c) 2020, Alexey Usachev. All rights reserved
 * @license MIT License
 */

namespace MatreshkaAsset;

/**
 * Class Asset
 * Combining and managing css and js files
 * Basic usage:
 *  Asset::getInstance()->addJs('/js/file.js')
 *  Asset::getInstance()->addCss('/css/file.css')
 * You can set the sorting for files
 *
 * Asset::getInstance()->addJs('/js/file.js', 1)
 */
class Asset
{
    const STATUS_NEW = 'new';
    const STATUS_CHANGED = 'changed';
    const STATUS_UNCHANGED = 'not changed';

    private static $instance = null;

    /**
     * @var array of css files
     */
    protected $css = [];

    /**
     * @var array of js files
     */
    protected $js = [];

    protected $useMinifiedFiles = true;

    protected $optimizeAssets = true;

    protected $basePath = '';

    protected $webPathCSS = '/css/';
    protected $webPathJs = '/js/';

    public static function getInstance(): Asset
    {
        if (static::$instance === null) {
            static::$instance = new static();
        }

        return static::$instance;
    }

    /**
     * Private constructor for implementing singleton
     */
    private function __construct()
    {
    }

    /**
     * Private clone magic method for implementing singleton
     */
    private function __clone()
    {
    }

    /**
     * Private wakeup magic method for implementing singleton
     */
    private function __wakeup()
    {
    }

    /**
     * @return bool
     */
    public function isUseMinifiedFiles(): bool
    {
        return $this->useMinifiedFiles;
    }

    /**
     * @param bool $useMinifiedFiles
     */
    public function setUseMinifiedFiles(bool $useMinifiedFiles): void
    {
        $this->useMinifiedFiles = $useMinifiedFiles;
    }

    /**
     * @return bool
     */
    public function isOptimizeAssets(): bool
    {
        return $this->optimizeAssets;
    }

    /**
     * @param bool $optimizeAssets
     */
    public function setOptimizeAssets(bool $optimizeAssets): void
    {
        $this->optimizeAssets = $optimizeAssets;
    }


    /**
     * @return string
     */
    public function getBasePath(): string
    {
        return $this->basePath;
    }

    /**
     * @param string $basePath
     */
    public function setBasePath(string $basePath): void
    {
        $this->basePath = $basePath;
    }

    /**
     * Add js file to asset
     * @param string $path
     * @param int $sorting Setting order of including files, starting from the lowest
     * @param bool $skip
     * @return bool
     */
    public function addJs(string $path, int $sorting = 0, bool $skip = false): bool
    {
        if (!$path) {
            return false;
        }

        $this->js[$path] = $this->addAsset($path, $sorting, $skip);

        return true;
    }


    /**
     * Add css file to asset
     * @param string $path
     * @param int $sorting Setting sorting of including files, starting from the lowest
     * @param bool $skip
     * @return bool
     */
    public function addCss(string $path, int $sorting = 0, bool $skip = false): bool
    {
        if (!$path) {
            return false;
        }

        $this->css[$path] = $this->addAsset($path, $sorting, $skip);

        return true;
    }

    /**
     * @param string $path
     * @param int $sorting
     * @param bool $skip
     * @return array
     */
    protected function addAsset(string $path, int $sorting = 0, bool $skip = false): array
    {
        $asset = [
            'path' => $path,
            'fullPath' => '',
            'sorting' => $sorting,
            'is_external' => $this->isExternalSource($path),
            'skip' => $skip
        ];
        return $asset;
    }

    /**
     * @return string
     */
    public function renderJs()
    {
        $jsPrioritized = $this->sortAsset($this->js);

        $assetsString = '';
        if ($this->isOptimizeAssets()) {
            $assetToOptimize = [];
            foreach ($jsPrioritized as $jsFile) {
                if (!$jsFile['is_external'] || $jsFile['skip']) {
                    $assetsString .= $this->getJsIncludeHtml($jsFile['path']);
                } else if ($fullPath = $this->getFullPath($jsFile['path'])) {
                    $jsFile['full_path'] = $fullPath;
                    $assetToOptimize[] = $jsFile;
                }
            }
            if (count($assetToOptimize)) {
                $optimizedAssetPath = $this->optimizeAssets($assetToOptimize, 'js');
                $assetsString .= $this->getJsIncludeHtml($optimizedAssetPath);
            }
        } else {
            foreach ($jsPrioritized as $jsFile) {
                $assetsString .= $this->getJsIncludeHtml($jsFile['path']);
            }
        }

        return $assetsString;
    }

    /**
     * @param array $assetToOptimize
     * @param string $type js or css
     */
    public function optimizeAssets($assetToOptimize, $type)
    {
        $cache = new Cache();
        $checksum = Cache::getAssetChecksum($assetToOptimize);
        $webPath = $type == 'css' ? $this->webPathCSS : $this->webPathJs;
        $optimizedFile = $type . '_' . $checksum . '.' . $type;
        $optimizedFilePath = $this->basePath . $webPath . $optimizedFile;
        $status = $cache->checkAssetChanged($assetToOptimize, $optimizedFilePath, $type, $checksum);
        $assetContent = '';
        if ($status == self::STATUS_CHANGED || $status == self::STATUS_NEW) {
            foreach ($assetToOptimize as $asset) {
                $assetContent .= $this->loadFile($asset['path']);
            }
            $this->save($assetContent, $optimizedFilePath);
        }

        return  $webPath . $optimizedFile;
    }


    /**
     * Save to file.
     *
     * @param string $content The minified data
     * @param string $path    The path to save the minified data to
     *
     * @throws \Exception
     */
    protected function save($content, $path)
    {
        $handler = $this->openFileForWriting($path);

        $this->writeToFile($handler, $content);

        @fclose($handler);
    }

    /**
     * Attempts to write $content to the file specified by $handler. $path is used for printing exceptions.
     *
     * @param resource $handler The resource to write to
     * @param string   $content The content to write
     * @param string   $path    The path to the file (for exception printing only)
     *
     * @throws \Exception
     */
    protected function writeToFile($handler, $content, $path = '')
    {
        if (($result = @fwrite($handler, $content)) === false || ($result < strlen($content))) {
            throw new \Exception('The file "'.$path.'" could not be written to. Check your disk space and file permissions.');
        }
    }

    /**
     * Attempts to open file specified by $path for writing.
     *
     * @param string $path The path to the file
     *
     * @return resource Specifier for the target file
     *
     * @throws \Exception
     */
    protected function openFileForWriting($path)
    {
        if (($handler = @fopen($path, 'w')) === false) {
            throw new \Exception('The file "'.$path.'" could not be opened for writing. Check if PHP has enough permissions.');
        }

        return $handler;
    }


    /**
     * @param $path
     * @return string
     */
    public function getJsIncludeHtml($path): string
    {
        return '<script type="text/javascript" src="' . $path . '"></script>' . "\n";
    }

    public function renderCss()
    {
        $cssPrioritized = $this->sortAsset($this->css);

        $assetsString = '';
        foreach ($cssPrioritized as $cssFile) {
            $assetsString .= $this->getCssIncludeHtml($cssFile['path']);
        }
    }

    public function getCssIncludeHtml($path)
    {
        return '<link type="text/css" rel="stylesheet" href="' . $path . '">' . "\n";
    }

    /**
     * Load data.
     *
     * @param string $data Path to a file
     *
     * @return string
     */
    protected function loadFile($path)
    {
        $assetContent = file_get_contents($path);

        // strip BOM, if any
        if (substr($assetContent, 0, 3) == "\xef\xbb\xbf") {
            $assetContent = substr($assetContent, 3);
        }

        $assetContent = "\n/* Start:" . $path . "*/\n" . $assetContent . "\n/* End */\n";

        return $assetContent;
    }

    /***
     * Returns asset full path
     * @param $assetPath
     * @return null|string
     */
    protected function getFullPath(string $assetPath): ?string
    {
        $paths = array($assetPath);
        //Checking if asset has minified version
        if (
            $this->isUseMinifiedFiles()
            && !preg_match("/(.+)\\.min.(js|css)$/i", $assetPath, $matches)
        ) {
            array_unshift($paths, $matches[1] . ".min." . $matches[2]);
        }

        $fullPath = null;
        $maxMtime = 0;
        foreach ($paths as $path) {
            $filePath = $this->getBasePath() . $path;
            if (file_exists($filePath) && ($mtime = filemtime($filePath)) > $maxMtime && filesize($filePath) > 0) {
                $maxMtime = $mtime;
                $fullPath = $filePath;
            }
        }

        return $fullPath;
    }


    /**
     * Sorting assets by sorting field
     * @param array $files
     * @return array
     */
    protected function sortAsset(array $files): array
    {
        usort($files, function ($a, $b) {
            return $a['sorting'] - $b['sorting'];
        });
        return $files;
    }

    /**
     * Check if the path is a external source
     *
     * @param string $path
     *
     * @return bool
     */
    protected function isExternalSource(string $path): bool
    {
        $parsedPath = parse_url($path);
        return isset($parsedPath['host']);
    }
}