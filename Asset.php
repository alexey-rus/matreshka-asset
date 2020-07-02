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

    /**
     * @var Asset|null
     */
    private static $instance = null;

    /**
     * @var array of css files
     */
    protected $css = [];

    /**
     * @var array of js files
     */
    protected $js = [];

    /**
     * @var bool
     */
    protected $useMinifiedFiles = true;

    /**
     * @var bool
     */
    protected $optimizeAssets = true;

    /**
     * @var string
     */
    protected $basePath = '';

    /**
     * @var string
     */
    protected $webRoot = '';

    /**
     * @var string
     */
    protected $webPathCSS = '/css/combined/';


    /**
     * @var string
     */
    protected $webPathJs = '/js/combined/';

    /**
     * @return Asset
     */
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
     * @return string
     */
    public function getWebRoot(): string
    {
        return $this->webRoot;
    }

    /**
     * @param string $webRoot
     */
    public function setWebRoot(string $webRoot): void
    {
        $this->webRoot = $webRoot;
    }

    /**
     * @return string
     */
    public function getWebPathJs(): string
    {
        return $this->webPathJs;
    }

    /**
     * @param string $webPathJs
     */
    public function setWebPathJs(string $webPathJs): void
    {
        $this->webPathJs = $webPathJs;
    }

    /**
     * @return string
     */
    public function getWebPathCSS(): string
    {
        return $this->webPathCSS;
    }

    /**
     * @param string $webPathCSS
     */
    public function setWebPathCSS(string $webPathCSS): void
    {
        $this->webPathCSS = $webPathCSS;
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
     * Return path with assets
     * @return string
     */
    public function getBasePath(): string
    {
        return $this->basePath;
    }

    /**
     * Set path with assets
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
     * @throws \Exception
     */
    public function renderJs(): string
    {
        $jsPrioritized = $this->sortAsset($this->js);

        $assetsString = '';
        if ($this->isOptimizeAssets()) {
            $assetToOptimize = [];
            foreach ($jsPrioritized as $jsFile) {
                if ($jsFile['is_external'] || $jsFile['skip']) {
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
     *
     * @return string
     * @throws \Exception
     */
    public function renderCss()
    {
        $cssPrioritized = $this->sortAsset($this->css);

        $assetsString = '';
        if ($this->isOptimizeAssets()) {
            $assetToOptimize = [];
            foreach ($cssPrioritized as $cssFile) {
                if ($cssFile['is_external'] || $cssFile['skip']) {
                    $assetsString .= $this->getCssIncludeHtml($cssFile['path']);
                } else if ($fullPath = $this->getFullPath($cssFile['path'])) {
                    $cssFile['full_path'] = $fullPath;
                    $assetToOptimize[] = $cssFile;
                }
            }

            if (count($assetToOptimize)) {
                $optimizedAssetPath = $this->optimizeAssets($assetToOptimize, 'css');
                $assetsString .= $this->getCssIncludeHtml($optimizedAssetPath);
            }
        } else {
            foreach ($cssPrioritized as $cssFile) {
                $assetsString .= $this->getCssIncludeHtml($cssFile['path']);
            }
        }

        return $assetsString;
    }


    /**
     * Optimize assets
     * @param array $assetToOptimize
     * @param string $type js or css
     * @throws \Exception
     * @return string
     */
    public function optimizeAssets(array $assetToOptimize, string $type): string
    {
        $cache = new Cache();
        $checksum = Cache::getAssetChecksum($assetToOptimize);
        $webPath = $type == 'css' ? $this->getWebPathCSS() : $this->getWebPathJs();
        $optimizedFile = $type . '_' . $checksum . '.' . $type;
        $optimizedFilePath = $this->basePath . $webPath . $optimizedFile;
        $status = $cache->checkAssetChanged($assetToOptimize, $optimizedFilePath, $type, $checksum);
        $assetContent = '';
        if ($status == self::STATUS_CHANGED || $status == self::STATUS_NEW) {
            foreach ($assetToOptimize as $asset) {
                $assetContent .= $this->loadAsset($asset);
            }
            $this->save($assetContent, $optimizedFilePath);

            $cache->saveAssetsMarker($assetToOptimize, $type, $checksum);
        }

        return $this->getWebRoot() . $webPath . $optimizedFile;
    }

    /**
     * Save to file.
     *
     * @param string $content The minified data
     * @throws \Exception
     */
    public function save(string $content, $path): void
    {
       $file = new File($path);
       $file->save($content);
    }


    /**
     * Add html tag for including js file
     * @param $path
     * @return string
     */
    public function getJsIncludeHtml(string $path): string
    {
        return '<script type="text/javascript" src="' . $path . '"></script>' . "\n";
    }

    /**
     * Add html tag for including css file
     * @param string $path
     * @return string
     */
    public function getCssIncludeHtml(string $path): string
    {
        return '<link type="text/css" rel="stylesheet" href="' . $path . '">' . "\n";
    }

    /**
     * Load data.
     *
     * @param array $asset
     *
     * @return string
     */
    protected function loadAsset(array $asset): string
    {
        $assetContent = file_get_contents($asset['full_path']);

        // strip BOM, if any
        if (substr($assetContent, 0, 3) == "\xef\xbb\xbf") {
            $assetContent = substr($assetContent, 3);
        }
        $printPath = str_replace($this->getBasePath(), '', $asset['full_path']);
        $assetContent = "\n/* Start: {$printPath} */\n" . $assetContent . "\n/* End: {$printPath} */\n";

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
            && !preg_match("/(.+)\\.min.(js|css)$/i", $assetPath,)
        ) {
            preg_match("/(.+)\\.(js|css)$/i", $assetPath, $matches);
            if (count($matches) === 3)
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
     * @param string $path     *
     * @return bool
     */
    protected function isExternalSource(string $path): bool
    {
        $parsedPath = parse_url($path);
        return isset($parsedPath['host']);
    }
}