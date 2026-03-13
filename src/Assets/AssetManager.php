<?php

namespace TAS\Core\Assets;

use \TAS\Core\Interface\IFileSaver;

/**
 * Base class for managing assets for project like Images, and Documents. It is newer version and replacement of UserFile.
 */
class AssetsManager
{
    public static $MAX_FILE_PER_FOLDER = 5000;

    /**
     * Base URL to be attach to the file. 
     *
     * @var string
     */
    public string $BaseUrl = "";

    /**
     * Can be physical path of container/blob name as required.
     *
     * @var string
     */
    public string $BasePath = "";


    private \TAS\Core\DB $db;

    public function __construct(\TAS\Core\DB $_db, string $_baseUrl = "", string $_basePath = "")   
    {
        $this->db = $_db;
        $this->BaseUrl = $_baseUrl;
        $this->BasePath = $_basePath;
    }
    
}
