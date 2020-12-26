<?php

namespace Codestacx\Widget;

class Widget extends WidgetAbstract
{

    public function __construct($files = null){
        $this->files = $files;
    }

    public function files(...$files){
        $temp = is_array($files[0]) ? $files[0]:$files;

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
                'extension'=> array_reverse(explode('.',$file->getClientOriginalName()))[0]
            ];
        },$temp);
        return $this;
    }

    /* add the extensions for files upload */
    public function addExtensions(...$extensions): void
    {
        is_array($extensions[0]) ? $this->setExtensions($extensions[0]) :$this->setExtensions($extensions);

    }

    /* remove extensions */
    public function removeExtensions(...$extensions):void{

        $extensions = is_array($extensions[0]) ? $extensions[0]:$extensions;

        $extensions = array_values(array_diff($this->extensions,$extensions));

        $this->setExtensions($extensions);

    }

    /* append extensions */
    public function appendExtensions(...$extensions): void
    {
        $extensions = is_array($extensions[0]) ? $extensions[0]:$extensions;

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
    public function where($dir = null):WidgetAbstract{
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

        $uploadedFilesInformation = array_map(function($file){
            //if file is not able to upload

            $tmpName     = date('ymdgis').$file->name;
            $file->file->move($this->dir,$tmpName);
            return [
                'orginalName' => $file->name,
                'newName'     => $tmpName
            ];
        },$files);

        $this->IS_ONLY      = false;
        $this->IS_EXCEPT    = false;

        return $uploadedFilesInformation;

    }


    public function checkIfDirectoryExist($dir = null){

    }

}
