<?php

namespace BilalMardini\FirebaseNotification;

class Installer
{
    public static function postInstall()
    {
        echo "\033[32m";
        echo "\n";
        echo "
 ____  _ _       _   __  __               _ _       _ 
| __ )(_) | __ _| | |  \/  | __ _ _ __ __| (_)_ __ (_)
|  _ \| | |/ _` | | | |\/| |/ _` | '__/ _` | | '_ \| |
| |_) | | | (_| | | | |  | | (_| | | | (_| | | | | | |
|____/|_|_|\__,_|_| |_|  |_|\__,_|_|  \__,_|_|_| |_|_|

";
        echo "\n";
        echo "*   Thank you for installing MyPackage!  *\n";
        echo "\n";
    }
}