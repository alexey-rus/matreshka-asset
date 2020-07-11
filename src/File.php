<?php

namespace AlexeyRus\MatreshkaAsset;

class File
{
    /**
     * @var string
     */
    protected $filePath;

    /**
     * @var resource
     */
    protected $handler;

    /**
     * File constructor.
     * @param string $path
     */
    public function __construct(string $path)
    {
        $this->filePath = $path;
    }

    /**
     * @return string
     */
    public function getPath()
    {
        return $this->filePath;
    }

    /**
     * @param $path
     */
    public function setPath($path)
    {
        $this->filePath = $path;
    }

    /**
     * Save to file.
     *
     * @param string $content The minified data
     *
     * @throws \Exception
     */
    public function save(string $content): void
    {
        $folder = dirname($this->getPath());
        if (!is_dir($folder))
            @mkdir($folder, 0777);

        $this->openFileForWriting();

        $this->writeToFile($content);

        @fclose($this->handler);
    }

    /**
     * Attempts to write $content to the file
     *
     * @param string  $content The content to write
     *
     * @throws \Exception
     */
    public function writeToFile(string $content): void
    {
        if (($result = @fwrite($this->handler, $content)) === false || ($result < strlen($content))) {
            throw new \Exception('The file "'.$this->getPath().'" could not be written to. Check your disk space and file permissions.');
        }
    }

    /**
     * Attempts to open file specified by $path for writing.
     *
     * @return resource Specifier for the target file
     *
     * @throws \Exception
     */
    public function openFileForWriting()
    {
        if (($this->handler = @fopen($this->getPath(), 'w')) === false) {
            throw new \Exception('The file "' . $this->getPath() .'" could not be opened for writing. Check if PHP has enough permissions.');
        }

        return $this->handler;
    }

}