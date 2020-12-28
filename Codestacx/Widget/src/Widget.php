<?php

namespace Codestacx\Widget;

class Widget extends WidgetAbstract
{

    public function __construct($files = null){
        $this->files = $files;
    }

    public function files(...$files){
        $temp = is_array($files[0]) ? $files[0] : $files;

        if(empty($temp)){
            throw new \Exception("Empty files array");
        }


        if(!$this->checkIfAllFilesAreValid($temp)){
            throw new \Exception('Please upload valid files');
        }


        $this->files = array_map(function($file){
            return (object)[
                'file'=>$file,
                'name'=>$file->getClientOriginalName(),
                'extension'=> array_reverse(explode('.',$file->getClientOriginalName()))[0],
                'mime'=>$file->getMimeType(),
                'thumbnail'=>false,
                'round'=>false,
            ];
        },$temp);
        return $this;
    }

    /* add the extensions for files upload */
    public function addExtensions(...$extensions): void
    {
        $extensions = is_array($extensions[0]) ? $extensions[0]: $extensions;
        $this->sanitizeExtensions($extensions);
        $this->setExtensions($extensions);



    }

    /* remove extensions */
    public function removeExtensions(...$extensions):void{

        $extensions = is_array($extensions[0]) ? $extensions[0]:$extensions;
        $this->sanitizeExtensions($extensions);

        $extensions = array_values(array_diff($this->extensions,$extensions));

        $this->setExtensions($extensions);

    }

    /* append extensions */
    public function appendExtensions(...$extensions): void
    {
        $extensions = is_array($extensions[0]) ? $extensions[0]:$extensions;
        $this->sanitizeExtensions($extensions);

        $extensions = array_values(array_unique(array_merge_recursive($this->extensions,$extensions)));

        $this->setExtensions($extensions);

    }

    /* allow only the passed extension */
    public function only(...$extension):WidgetAbstract{
        if($this->IS_EXCEPT){
            throw new \Exception('Either `except` or `only` filter can be applied at a time');
        }
        is_array($extension[0]) ? $this->setOnly($extension[0]) : $this->setOnly($extension);
        return $this;
    }

    /* except only the passed extension */
    public function except(...$extension):WidgetAbstract{

        if($this->IS_ONLY){
            throw new \Exception('Either `except` or `only` filter can be applied at a time');
        }

        is_array($extension[0]) ? $this->setExcept($extension[0]) : $this->setExcept($extension);
        return $this;
    }

    /* set the directory to upload files */
    public function to($dir = null):WidgetAbstract{
        $this->dir = !is_null($dir) ? public_path().'/'.ltrim($dir, '/'):public_path().'/';
        return $this;
    }



    public function upload() :array{

        /* check if there are files */
        if($this->files == null || count($this->files) == 0){
            throw new \Exception('No files provided');
        }


        /* check if the files are valid */


        if(is_null($this->dir)){
            throw new \Exception('No upload directory mentioned');
        }


        $files = $this->getFilesForUpload();


        $uploadedFilesInformation = array_map(function(&$file){
            //if file is not able to upload

            $tmpName     = date('ymdgis').$file->name;

            if($file->thumbnail || $file->round){
                //process first thumbnail & then round it
                $this->processThumbnailAndRoundedImages($file);
                $this->uploadProcessedImage($file,$this->dir.'/'.$tmpName);
            }else{
                //normally upload file
                $file->file->move($this->dir,$tmpName);
            }

            return [
                'orginalName' => $file->name,
                'newName'     => $tmpName
            ];
        },$files);

        $this->IS_ONLY      = false;
        $this->IS_EXCEPT    = false;

        return $uploadedFilesInformation;

    }



    function processRoundImage($file){

        $src = $file->resource;


        $file->resource = $src;

    }
    function processThumbnailAndRoundedImages($file):void{

        $resource  = $this->load($file);    //load the image file

        $original_width    = imagesx($resource);
        $original_height   = imagesy($resource);

        $file->resource    = $resource;
        if($file->thumbnail){
            $thumbnail_width  = $this->getWidth();
            $thumbnail_height = $this->getHeight();

            $modified_image = imagecreatetruecolor($thumbnail_width, $thumbnail_height);
            imagecopyresampled($modified_image, $resource, 0, 0, 0, 0,
                $thumbnail_width, $thumbnail_height, $original_width, $original_height);
            $file->resource = $modified_image;
            if($file->round){
                //if also requested for the rounded image
                $this->processRoundImage($file);
            }
        }else{
           // $file->resource = imagecreatetruecolor($original_width,$original_height);
            $this->processRoundImage($file);
        }

    }
    function getFileName($file): string
    {
        if(is_uploaded_file($file) && !empty($file)){
            return $file->getClientOriginalName();
        }
        // TODO: Implement getFileName() method.
    }

    function toSize($width, $height): WidgetAbstract
    {
        // TODO: Implement toSize() method.
        $this->setWidth($width);
        $this->setHeight($height);

    }


    private function load($file){



        if('image/jpeg' == $file->mime) return imagecreatefromjpeg($file->file);
        if('image/png' == $file->mime)  return imagecreatefrompng($file->file);
        if('image/gif' == $file->mime)  return imagecreatefromgif($file->file);
        if('image/bmp' == $file->mime)  return imagecreatefrombmp($file->file);


    }

    private function uploadProcessedImage($file,$path,$quality = 75){

        //imagepng($file->thumbnail,public_path().'/uploads/images/atif.png');
        if('image/jpeg' == $file->mime) imagejpeg($file->resource,$path);
        if('image/png' == $file->mime)  imagepng($file->resource,$path);
        if('image/gif' == $file->mime)  imagegif($file->resource,$path);
        if('image/bmp' == $file->mime)  imagebmp($file->resource,$path);


    }


    protected function resizeImage($width,$height):WidgetAbstract{


        //iterate on all images
        foreach ($this->files as &$file){

            if(!$this->isValidImageFile($file->mime)){
                throw new \Exception('Only Image type file are converted to thumbnails');
            }

            $modified_image = imagecreatetruecolor($width, $height);
            $resource = $this->load($file);
            imagecopyresampled($modified_image, $resource, 0, 0, 0, 0, $width, $height, $this->getWidth($resource), $this->getHeight($resource));
           // $this->image = $new_image;
            $file->thumbnail = $modified_image;
        }

        return $this;
    }


    public function round(): WidgetAbstract{


        foreach ($this->files as &$file){
            $file->round = $this->isValidImageFile($file->mime) ?? true;
        }


        return $this;
    }

    public function withThumbnail($width,$height):Widget{
        $this->setHeight($height);
        $this->setWidth($width);
        foreach ($this->files as &$file){
            $file->thumbnail = $this->isValidImageFile($file->mime) ?? true;
        }

        return  $this;

    }
    private function isValidImageFile($mimetype){
        return in_array($mimetype,['image/jpeg','image/png','image/gif','image/bmp']) ? true:false;
    }
}
