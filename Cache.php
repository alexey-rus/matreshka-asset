<?php
/**
 * Cache for working with assets
 *
 * Please report bugs on https://github.com/alexey-rus/matreshka-asset/issues
 *
 * @author Alexey Usachev <p15623@yandex.ru>
 * @copyright Copyright (c) 2020, Alexey Usachev. All rights reserved
 * @license MIT License
 */
namespace MatreshkaAsset;

class Cache
{


    protected $cachePath = '/cache/';

    /**
     * Cache constructor.
     */
    public function __construct()
    {
        $this->cachePath = __DIR__ . $this->cachePath;
    }

    /**
     * Return md5 for asset
     * @param array $assetList
     * @return string
     */
    public static function getAssetChecksum(array $assetList): string
    {
        $result = [];
        foreach ($assetList as $arAsset) {
            $result[$arAsset['path']] = $arAsset['full_path'];
        }
        ksort($result);
        return md5(implode('_', $result));
    }

    /**
     * @param array $assetList *
     * @param string $optimizedFilePath
     * @param string $type
     * @param string $checksum
     * @return bool
     */
    public function checkAssetChanged(array $assetList, string $optimizedFilePath, string $type, string $checksum = ''): string
    {
        if (!$checksum)
            $checksum = self::getAssetChecksum($assetList);

        $cacheFile = $type . '_' . $checksum . '.php';

        $status = Asset::STATUS_UNCHANGED;
        if (file_exists($this->cachePath . $cacheFile) && file_exists($optimizedFilePath)) {
            $files = $this->loadConfig($this->cachePath . $cacheFile);
            foreach ($assetList as $asset) {

                if (isset($files[$asset['path']])) {
                    $fileMarker = self::getFileMarker($asset['full_path']);
                    if ($files[$asset['path']] != $fileMarker) {
                        $status = Asset::STATUS_CHANGED;
                        break;
                    }
                } else {

                    $status = Asset::STATUS_CHANGED;
                    break;
                }
            }
        } else {
            $status = Asset::STATUS_NEW;
        }

        return $status;
    }

    public function saveAssetsMarker(array $assetList,  string $type, string $checksum = '')
    {
        if (!$checksum)
            $checksum = self::getAssetChecksum($assetList);

        $assetsData = [];
        foreach ($assetList as $asset) {
            $fileMarker = self::getFileMarker($asset['full_path']);
            $assetsData[$asset['path']] = $fileMarker;
        }
        $cacheString = '<?php return ' . var_export($assetsData, true) . ';';

        $cacheFile = $type . '_' . $checksum . '.php';
        $file = new File($this->cachePath . $cacheFile);
        $file->save($cacheString);
    }

    /**
     * @param $filePath
     * @return string
     */
    public static function getFileMarker($filePath) {
        return filemtime($filePath) . filesize($filePath);
    }

    /**

     * @param string $file
     * @return  array
     */
    protected function loadConfig($file)
    {
        return include $file;
    }

}