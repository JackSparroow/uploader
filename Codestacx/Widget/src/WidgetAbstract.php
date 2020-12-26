<?php


namespace Codestacx\Widget;


abstract class WidgetAbstract   {


    protected $IS_ONLY = false;
    protected $IS_EXCEPT = false;

    protected $onlyExtensions    =  [];
    protected $exceptExtensions  =  [];
    /* files to upload */
    protected $files = null;

    /* upload directory */
    protected $dir   = null;

    /* extensions allowed to upload */
    protected $extensions = [];

    /**
     * @return null
     */
    public function getFiles()
    {
        return $this->files;
    }

    /**
     * @param null $files
     */
    public function setFiles($files): void
    {
        $this->files = $files;
    }


    /**
     * @return null
     */
    public function getDir()
    {
        return $this->dir;
    }


    public function setDir($dir): void{
        $this->dir = $dir;
    }
    /**
     * @return array
     */
    public function getExtensions(): array
    {
        return $this->extensions;
    }

    public function setExtensions($extensions):void{
        $this->extensions = $extensions;
    }

    /**
     * Sanitize extensions
     */

    public function sanitizeExtensions($extensions){
        if(!is_array($extensions)){
            return [$extensions];
        }
        return $extensions;
    }

    protected function setOnly($extensions){
        $this->onlyExtensions  = $extensions;
        $this->IS_ONLY = true;
    }
    protected function setExcept($extensions){
        $this->exceptExtensions = $extensions;
        $this->IS_EXCEPT = true;
    }


    protected function getFilesForUpload(){
        /*
         * [a,b,c,d]
         * only = a
         * except = a
        */
        $temp = array();
        if($this->IS_ONLY){

            foreach ($this->files as $file){

                //if extension of file exist in only extensions array
                if(in_array($file->extension,$this->onlyExtensions)){
                    array_push($temp,$file);
                }
            }
            return $temp;
        }

        if($this->IS_EXCEPT){
            foreach ($this->files as $file){

                if(!in_array($file->extension,$this->exceptExtensions)){
                    array_push($temp,$file);
                }
            }

            return $temp;
        }

        if(count($this->extensions) > 0){
            foreach ($this->files as $file){

                if(in_array($file->extension,$this->extensions)){
                    array_push($temp,$file);
                }
            }
            return $temp;
        }

        return $this->files;

    }


    protected function checkIfAllFilesAreValid($files){

        foreach ($files as $file){
            if(!is_uploaded_file($file)){
                return false;
            }
        }
        return true;
    }
    abstract function where($dir = null):WidgetAbstract;
    abstract function addExtensions(...$extensions):void;
    abstract function removeExtensions(...$extensions):void;
    abstract function only(...$extension):WidgetAbstract;
    abstract function except(...$extensions):WidgetAbstract;
    abstract function appendExtensions(...$extensions):void;

    abstract function upload():array;



}
