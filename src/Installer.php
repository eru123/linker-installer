<?php

namespace Linker\Installer;

class Installer {
    public function __construct(){

    }
    public function run(){
        exec("mkdir DIR_".rand());
    }
}